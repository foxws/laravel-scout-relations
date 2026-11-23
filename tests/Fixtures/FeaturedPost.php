<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;

/**
 * Post whose makeAllSearchableUsing() adds an orWhere.
 */
class FeaturedPost extends Post
{
    protected $table = 'posts';

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->where('draft', false)->orWhere('featured', true);
    }
}
