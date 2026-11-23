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
            'author_name' => $this->author?->name, // kept fresh on every re-index, null once the author is deleted
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
| `saved` | Related models are re-indexed only if the save changed the model. Saves with no real changes are skipped. |
| `deleted` | Related models are re-indexed, so the search index reflects that the parent is gone. Their keys are read before the delete, so relations whose rows the database cascades away (such as a pivot table with `ON DELETE CASCADE`) are re-indexed too. |

To skip re-indexing conditionally, override `shouldReindexSearchableRelations()`.
After a delete, `wasChanged()` is `false`, so check for the delete as well:

```php
public function shouldReindexSearchableRelations(): bool
{
    // trashed() exists on models that use SoftDeletes.
    return $this->wasChanged('name') || ! $this->exists || $this->trashed();
}
```

The related models are re-indexed the way Scout's own observer would:

- Inside a transaction, with `scout.after_commit` enabled, re-indexing waits
  until the transaction is committed, and is skipped when it rolls back.
- Related models whose `shouldBeSearchable()` returns `false` are removed from
  the index instead.
- Soft-deleted related models are included when `scout.soft_delete` is enabled.
- Nothing is re-indexed for a related model while `withoutSyncingToSearch()`
  is disabling its syncing.

Related records are re-indexed in chunks of their keys. If the related model
defines its own `makeAllSearchableUsing()` method, it is applied to each
chunk's query as a scope, which avoids extra N+1 queries. Joins and `orWhere()`
calls in it are safe: they can't widen the query beyond the related records.

Creating a model doesn't re-index its relations, as a new model has no
related records yet. A related record whose own foreign key changes, such as a
post moved to another author, is re-indexed by Scout itself, but the author it
moved away from is not: call `reindexSearchableRelations()` on it if its
relations show data of that record.

The package also guards against infinite loops: if two models watch each
other's relationships, a re-index on one side won't keep triggering the
other back and forth forever.
