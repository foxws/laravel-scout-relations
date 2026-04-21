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
    private static array $reindexing = [];

    public static function isReindexing(string $class): bool
    {
        return array_key_exists($class, static::$reindexing);
    }

    public static function markReindexing(string $class): void
    {
        static::$reindexing[$class] = true;
    }

    public static function unmarkReindexing(string $class): void
    {
        unset(static::$reindexing[$class]);
    }

    public static function flushReindexingState(): void
    {
        static::$reindexing = [];
    }
}
