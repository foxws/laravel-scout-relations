# Laravel Scout Relations

[![Latest Version on Packagist](https://img.shields.io/packagist/v/foxws/laravel-scout-relations.svg?style=flat-square)](https://packagist.org/packages/foxws/laravel-scout-relations)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/foxws/laravel-scout-relations/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/foxws/laravel-scout-relations/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/foxws/laravel-scout-relations.svg?style=flat-square)](https://packagist.org/packages/foxws/laravel-scout-relations)

Automatically re-index Scout-searchable related models when an Eloquent model is saved or deleted.

When a parent model changes (e.g. an `Author`), its related Searchable models (e.g. `Post`) are automatically queued for re-indexing, keeping your search index consistent without any manual intervention.

See the [full documentation](https://foxws.github.io/laravel-scout-relations/) (or browse [`docs/`](docs) directly): [Installation](docs/installation.md), [Usage](docs/usage.md), [Configuration](docs/configuration.md), [Artisan Command](docs/artisan-command.md).

## Requirements

- PHP 8.5+
- Laravel 13+
- [Laravel Scout](https://laravel.com/docs/scout)

## Installation

Install the package via Composer:

```bash
composer require foxws/laravel-scout-relations
```

> [!TIP]
> Let your AI agent do the hard parts. This package ships a [Laravel Boost](https://laravel.com/docs/boost) skill, `scout-relations-development`, that teaches your agent how to install, configure and use Laravel Scout Relations. Install Boost and select the skill when prompted:
>
> ```bash
> composer require laravel/boost --dev
> php artisan boost:install
> ```
>
> Already using Boost? Run `php artisan boost:update --discover` to pick up the skill. Then just ask, for example: *"Re-index an author's posts in Scout whenever the author is saved."*

## Usage

Add the `HasSearchableRelations` trait to any Eloquent model whose changes should trigger re-indexing of related models. Then override `searchableRelations()` to return the relationship names to watch.

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
            'author_name' => $this->author?->name, // kept fresh on every re-index, null once the author is deleted
        ];
    }
}
```

Now whenever an `Author` is saved with changes or deleted, all of its `Post` records are automatically re-indexed.

## How it works

The trait hooks into Eloquent's `saved` and `deleted` model events:

- **`saved`** — re-indexes relations only when the save changed the model, avoiding unnecessary indexing on no-op saves.
- **`deleted`** — re-indexes relations so the search index reflects the parent's removal. The related keys are read before the delete, so relations whose rows the database cascades away (such as a pivot table with `ON DELETE CASCADE`) are re-indexed too.

The related models are re-indexed the way Scout's own observer would:

- Inside a transaction, with `scout.after_commit` enabled, re-indexing waits until the transaction is committed, and is skipped when it rolls back.
- Related models whose `shouldBeSearchable()` returns `false` are removed from the index instead.
- Soft-deleted related models are included when `scout.soft_delete` is enabled.
- Nothing is re-indexed for a related model while `withoutSyncingToSearch()` is disabling its syncing.

Re-indexing is performed in chunks of related keys. If the related model defines `makeAllSearchableUsing()`, it is applied to each chunk's query as a scope, preventing N+1 queries. Joins and `orWhere()` calls in it are safe: they can't widen the query beyond the related models.

A per-class re-entry guard prevents infinite cascades when mutual relationships exist.

## Configuration

Publish the config file with:

```bash
php artisan vendor:publish --tag="scout-relations-config"
```

Available options in `config/scout-relations.php`:

| Key | Env variable | Default | Description |
|---|---|---|---|
| `enabled` | `SCOUT_RELATIONS_ENABLED` | `true` | Disable all automatic relation syncing |
| `chunk.searchable` | `SCOUT_RELATIONS_CHUNK_SEARCHABLE` | `500` | Chunk size for `searchable()` calls; `500` when not positive |
| `chunk.unsearchable` | `SCOUT_RELATIONS_CHUNK_UNSEARCHABLE` | `500` | Chunk size reserved for future use |

## Artisan command

To manually force re-indexing of all relations for a model (ignoring the `enabled` flag), use:

```bash
php artisan scout:index-relations "App\Models\Author"
```

This runs Scout's `makeAllSearchable()` once for each distinct searchable model class referenced by the model's `searchableRelations()`, so a relation shared by many records is only imported once. For a `morphTo` relation, those are the classes its records point to.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [francoism90](https://github.com/foxws)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
