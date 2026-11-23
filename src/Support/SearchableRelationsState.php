<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Support;

use Illuminate\Database\Eloquent\Model;
use WeakMap;

class SearchableRelationsState
{
    /**
     * Tracks which model classes are currently mid-cascade to prevent re-entry.
     *
     * @var array<class-string, bool>
     */
    private array $reindexing = [];

    /**
     * The models that are being saved with changes.
     *
     * @var WeakMap<Model, bool>
     */
    private WeakMap $changing;

    /**
     * The related keys of the models that are being deleted, read before the
     * database can cascade the rows away.
     *
     * @var WeakMap<Model, list<array{model: Model, keys: list<mixed>}>>
     */
    private WeakMap $deleting;

    public function __construct()
    {
        $this->changing = new WeakMap;
        $this->deleting = new WeakMap;
    }

    /**
     * @param  class-string  $class
     */
    public function isReindexing(string $class): bool
    {
        return array_key_exists($class, $this->reindexing);
    }

    /**
     * @param  class-string  $class
     */
    public function markReindexing(string $class): void
    {
        $this->reindexing[$class] = true;
    }

    /**
     * @param  class-string  $class
     */
    public function unmarkReindexing(string $class): void
    {
        unset($this->reindexing[$class]);
    }

    public function markChanging(Model $model, bool $changing): void
    {
        $this->changing[$model] = $changing;
    }

    public function pullChanging(Model $model): bool
    {
        $changing = $this->changing[$model] ?? false;

        unset($this->changing[$model]);

        return $changing;
    }

    /**
     * @param  list<array{model: Model, keys: list<mixed>}>  $relations
     */
    public function rememberDeleting(Model $model, array $relations): void
    {
        $this->deleting[$model] = $relations;
    }

    /**
     * @return list<array{model: Model, keys: list<mixed>}>|null
     */
    public function pullDeleting(Model $model): ?array
    {
        $relations = $this->deleting[$model] ?? null;

        unset($this->deleting[$model]);

        return $relations;
    }
}
