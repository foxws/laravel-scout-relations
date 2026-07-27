<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Tests\Fixtures;

/**
 * Author variant whose searchableRelations points at a belongsToMany
 * relation, used to verify a related record shared across multiple
 * owning records is only re-indexed once per bulk run.
 */
class TaggedAuthor extends Author
{
    protected $table = 'authors';

    public function searchableRelations(): array
    {
        return ['tags'];
    }
}
