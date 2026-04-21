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
        return array_key_exists($class, self::$reindexing);
    }

    public static function markReindexing(string $class): void
    {
        self::$reindexing[$class] = true;
    }

    public static function unmarkReindexing(string $class): void
    {
        unset(self::$reindexing[$class]);
    }

    public static function flushReindexingState(): void
    {
        self::$reindexing = [];
    }
}
