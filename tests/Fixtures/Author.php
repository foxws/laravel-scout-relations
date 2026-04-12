<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Tests\Fixtures;

use Foxws\ScoutRelations\Concerns\HasSearchableRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    use HasSearchableRelations;

    protected $guarded = [];

    public function searchableRelations(): array
    {
        return ['posts'];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
