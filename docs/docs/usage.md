---
sidebar_position: 3
---

# Usage

Add the `HasSearchableRelations` trait to any Eloquent model whose changes
should trigger re-indexing of related models. Then override
`searchableRelations()` to return the relationship names to watch.

```php
use Foxws\ScoutRelations\Concerns\HasSearchableRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    use HasSearchableRelations;

    /**
     * Relationships whose models should be re-indexed when this model changes.
     *
     * @return array<int, string>
     */
    public function searchableRelations(): array
    {
        return ['posts'];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```

The related `Post` model must use Laravel Scout's `Searchable` trait:

```php
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    public function toSearchableArray(): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'author_name' => $this->author->name, // kept fresh on every re-index
        ];
    }
}
```

Now whenever an `Author` is saved with changes or deleted, all of its `Post`
records are automatically re-indexed.

## How it works

The trait hooks into Eloquent's `saved` and `deleted` model events:

- **`saved`** — re-indexes relations only when `wasChanged()` is `true`, avoiding unnecessary indexing on no-op saves.
- **`deleted`** — re-indexes relations unconditionally so the search index reflects the parent's removal.

Re-indexing is performed in chunks via `chunkById`. If the related model
defines `makeAllSearchableUsing()`, it is applied to the chunk query,
preventing N+1 queries.

A per-class re-entry guard prevents infinite cascades when mutual
relationships exist.
