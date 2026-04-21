<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Concerns;

use Closure;
use Foxws\ScoutRelations\Support\SearchableRelationsState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Laravel\Scout\Searchable;

trait HasSearchableRelations
{
    public static function bootHasSearchableRelations(): void
    {
        if (! Config::boolean('scout-relations.enabled', true)) {
            return;
        }

        static::saved(function (Model $model): void {
            if ($model->wasChanged() && $model->shouldReindexSearchableRelations()) {
                $model->reindexSearchableRelations();
            }
        });

        static::deleted(function (Model $model): void {
            if ($model->shouldReindexSearchableRelations()) {
                $model->reindexSearchableRelations();
            }
        });
    }

    public function shouldReindexSearchableRelations(): bool
    {
        return true;
    }

    public function reindexSearchableRelations(): void
    {
        $class = static::class;

        if (SearchableRelationsState::isReindexing($class)) {
            return;
        }

        SearchableRelationsState::markReindexing($class);

        try {
            foreach ($this->searchableRelations() as $relation) {
                $this->reindexSearchableRelation($relation);
            }
        } finally {
            SearchableRelationsState::unmarkReindexing($class);
        }
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
     *
     * Applies makeAllSearchableUsing (if defined) to the relation query before
     * streaming, mirroring Scout's bulk import behaviour and preventing N+1
     * queries when toSearchableArray() accesses eager-loaded relationships.
     */
    protected function reindexSearchableRelation(string $relation): void
    {
        $query = $this->{$relation}();
        $related = $query->getRelated();

        if (! in_array(Searchable::class, class_uses_recursive($related))) {
            return;
        }

        if (method_exists($related, 'makeAllSearchableUsing')) {
            Closure::bind(
                fn ($q) => $this->makeAllSearchableUsing($q),
                $related,
                get_class($related),
            )($query->getQuery());
        }

        $chunkSize = Config::integer('scout-relations.chunk.searchable', 500);

        $query->chunkById($chunkSize, fn ($chunk) => $chunk->searchable());
    }
}
