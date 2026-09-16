---
section: Usage
order: 1
---

# Usage

To keep a model's related records in sync, add the `HasSearchableRelations`
trait to it. Then add a `searchableRelations()` method that lists the names
of the relationships you want to watch.

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

The related model — `Post` in this example — must use Laravel Scout's own
`Searchable` trait, just like any other searchable model:

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

With this in place, saving an `Author` with changes, or deleting one,
automatically re-indexes all of that author's `Post` records.

## How it works

The `HasSearchableRelations` trait listens to two Eloquent model events:

| Event | What happens |
| --- | --- |
| `saved` | Related models are re-indexed only if the model actually changed (`wasChanged()` is `true`). Saves with no real changes are skipped. |
| `deleted` | Related models are always re-indexed, so the search index reflects that the parent is gone. |

Related records are re-indexed in chunks, using `chunkById`. If the related
model defines its own `makeAllSearchableUsing()` method, that method is used
while building each chunk, which avoids extra N+1 queries.

The package also guards against infinite loops: if two models watch each
other's relationships, a re-index on one side won't keep triggering the
other back and forth forever.
