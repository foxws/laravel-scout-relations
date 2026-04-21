<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Support;

class SearchableRelationsState
{
    /**
     * Tracks which model classes are currently mid-cascade to prevent re-entry.
     *
     * @var array<class-string, bool>
     */
    private array $reindexing = [];

    public function isReindexing(string $class): bool
    {
        return array_key_exists($class, $this->reindexing);
    }

    public function markReindexing(string $class): void
    {
        $this->reindexing[$class] = true;
    }

    public function unmarkReindexing(string $class): void
    {
        unset($this->reindexing[$class]);
    }
}
