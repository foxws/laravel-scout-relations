<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Tests\Fixtures;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Author variant whose searchable relations each test picks.
 */
class RelationsAuthor extends Author
{
    /** @var array<int, string> */
    public static array $watched = ['posts'];

    protected $table = 'authors';

    public function searchableRelations(): array
    {
        return static::$watched;
    }

    public function newestPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id')->latest();
    }

    public function softPosts(): HasMany
    {
        return $this->hasMany(SoftPost::class, 'author_id');
    }

    public function featuredPosts(): HasMany
    {
        return $this->hasMany(FeaturedPost::class, 'author_id');
    }

    public function joinedPosts(): HasMany
    {
        return $this->hasMany(JoinedPost::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'author_tag', 'author_id', 'tag_id');
    }
}
