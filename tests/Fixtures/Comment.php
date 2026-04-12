<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Plain Eloquent model that does NOT use Laravel\Scout\Searchable.
 * Used to verify that the trait skips non-searchable relations.
 */
class Comment extends Model
{
    protected $guarded = [];
}
