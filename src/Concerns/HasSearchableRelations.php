<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Concerns;

use Foxws\ScoutRelations\Support\SearchableRelationsState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Laravel\Scout\ModelObserver;
use Laravel\Scout\Searchable;

trait HasSearchableRelations
{
    public static function bootHasSearchableRelations(): void
    {
        if (! Config::boolean('scout-relations.enabled', true)) {
            return;
        }

        // wasChanged() still holds the changes of an earlier save, so check for changes before saving.
        static::saving(function (Model $model): void {
            app(SearchableRelationsState::class)->markChanging($model, $model->exists && $model->isDirty());
        });

        static::saved(function (Model $model): void {
            if (! app(SearchableRelationsState::class)->pullChanging($model)) {
                return;
            }

            if ($model->shouldReindexSearchableRelations()) {
                $model->reindexSearchableRelations();
            }
        });

        // A database cascade may remove the related rows, so read their keys before deleting.
        static::deleting(function (Model $model): void {
            app(SearchableRelationsState::class)->rememberDeleting($model, $model->searchableRelationKeys());
        });

        static::deleted(function (Model $model): void {
            $relations = app(SearchableRelationsState::class)->pullDeleting($model);

            if ($relations === null || ! $model->shouldReindexSearchableRelations()) {
                return;
            }

            $model->indexSearchableRelations($relations);
        });
    }

    public function shouldReindexSearchableRelations(): bool
    {
        return true;
    }

    public function reindexSearchableRelations(): void
    {
        $this->indexSearchableRelations($this->searchableRelationKeys());
    }

    /**
     * Return the relationship names whose models should be re-indexed
     * after this model is saved or deleted.
     *
     * @return array<int, string>
     */
    public function searchableRelations(): array
    {
        return [];
    }

    /**
     * Re-index all related models for the given relationship name.
     */
    protected function reindexSearchableRelation(string $relation): void
    {
        $this->indexSearchableRelations($this->searchableRelationKeys([$relation]));
    }

    /**
     * The keys of the related models, per searchable relation.
     *
     * @param  array<int, string>|null  $relations
     * @return list<array{model: Model, keys: list<mixed>}>
     */
    protected function searchableRelationKeys(?array $relations = null): array
    {
        $keys = [];

        foreach ($relations ?? $this->searchableRelations() as $name) {
            /** @var Relation<Model, Model, mixed> $relation */
            $relation = $this->{$name}();
            $related = $relation->getRelated();

            if (! in_array(Searchable::class, class_uses_recursive($related), true)) {
                continue;
            }

            if (static::keepsTrashedInSearch($related)) {
                $relation->withTrashed();
            }

            $keys[] = [
                'model' => $related,
                'keys' => $relation
                    ->reorder()
                    ->pluck($related->getQualifiedKeyName())
                    ->unique()
                    ->values()
                    ->all(),
            ];
        }

        return $keys;
    }

    /**
     * Index the related models once the transaction, if any, is committed.
     *
     * @param  list<array{model: Model, keys: list<mixed>}>  $relations
     */
    protected function indexSearchableRelations(array $relations): void
    {
        $relations = array_values(array_filter(
            $relations,
            fn (array $relation): bool => $relation['keys'] !== [] && ! ModelObserver::syncingDisabledFor($relation['model']),
        ));

        if ($relations === []) {
            return;
        }

        $connection = $this->getConnection();

        if ((bool) Config::get('scout.after_commit', false) && $connection->transactionLevel() > 0) {
            $connection->afterCommit(fn () => $this->sendSearchableRelations($relations));

            return;
        }

        $this->sendSearchableRelations($relations);
    }

    /**
     * @param  list<array{model: Model, keys: list<mixed>}>  $relations
     */
    protected function sendSearchableRelations(array $relations): void
    {
        $state = app(SearchableRelationsState::class);

        if ($state->isReindexing(static::class)) {
            return;
        }

        $state->markReindexing(static::class);

        try {
            foreach ($relations as $relation) {
                foreach (array_chunk($relation['keys'], static::searchableRelationChunkSize()) as $keys) {
                    /** @var Collection<int, Model> $models */
                    $models = $this->searchableRelationQuery($relation['model'], $keys)->get();

                    [$searchable, $unsearchable] = $models->partition(fn (Model $model): bool => (bool) $model->shouldBeSearchable());

                    $searchable->values()->searchable();
                    $unsearchable->values()->unsearchable();
                }
            }
        } finally {
            $state->unmarkReindexing(static::class);
        }
    }

    /**
     * Applies makeAllSearchableUsing() (if defined) as a scope, mirroring Scout's
     * bulk import and preventing N+1 queries when toSearchableArray() accesses
     * eager-loaded relationships. As a scope, Eloquent keeps any orWhere() it
     * adds apart from the keys.
     *
     * @param  list<mixed>  $keys
     * @return Builder<Model>
     */
    protected function searchableRelationQuery(Model $related, array $keys): Builder
    {
        $query = $related->newQuery()->whereKey($keys);

        if (static::keepsTrashedInSearch($related)) {
            $query->withTrashed();
        }

        return $query->withGlobalScope(
            'scout-relations',
            function (Builder $builder) use ($related): void {
                (function () use ($builder): void {
                    $this->makeAllSearchableUsing($builder);
                })->call($related);
            },
        );
    }

    protected static function keepsTrashedInSearch(Model $related): bool
    {
        return (bool) Config::get('scout.soft_delete', false)
            && in_array(SoftDeletes::class, class_uses_recursive($related), true);
    }

    protected static function searchableRelationChunkSize(): int
    {
        $size = Config::integer('scout-relations.chunk.searchable', 500);

        return $size > 0 ? $size : 500;
    }
}
