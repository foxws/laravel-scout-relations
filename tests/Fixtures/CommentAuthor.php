<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Tests\Fixtures;

/**
 * Author variant whose searchableRelations points to Comments (non-Searchable).
 */
class CommentAuthor extends Author
{
    protected $table = 'authors';

    public function searchableRelations(): array
    {
        return ['comments'];
    }
}
