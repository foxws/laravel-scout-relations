<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;

/**
 * Post whose makeAllSearchableUsing() joins another table.
 */
class JoinedPost extends Post
{
    protected $table = 'posts';

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->join('authors', 'authors.id', '=', 'posts.author_id')->select('posts.*');
    }
}
