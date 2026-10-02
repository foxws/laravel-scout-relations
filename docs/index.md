---
title: Introduction
metadata:
  role: Search
  group: search
  eyebrow: "Laravel Scout · Auto Re-index · Relations"
  desc: "Keep related Laravel Scout search indexes fresh automatically."
  lead: "Save an author, and their posts are re-indexed too. Related data stays fresh in your Scout index without writing observers."
  requires: "PHP ^8.4"
  laravel: "12.x / 13.x"
  licence: MIT
  used_by:
    - name: Stry
      desc: "A self-hosted video streaming app."
      href: "https://github.com/francoism90/stry"
    - name: foxws.nl
      desc: "This site."
      href: "https://foxws.nl"
---

# Introduction

Your search index often holds data from related models. For example, each post in the index includes its author's name. When the author changes their name, Laravel Scout doesn't know those posts need updating.

This package does it for you. When you save or delete a model, it also re-indexes the related models you list:

```php
use Foxws\ScoutRelations\Concerns\HasSearchableRelations;

class Author extends Model
{
    use HasSearchableRelations;

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

Now saving an `Author` re-indexes all of their posts. You don't need to write observers or jobs.

## Features

- Re-index related models when a model is saved or deleted.
- Skip the work when a save didn't change anything.
- Re-index in chunks, through Scout's own queue when you've turned it on.
- Re-index a model's related records from the command line with `scout:index-relations`.

## Requirements

- PHP 8.4 or higher
- Laravel 12 or higher
- [Laravel Scout](https://laravel.com/docs/scout)

## Installation

```bash
composer require foxws/laravel-scout-relations
```

## Learn more

- [Usage](usage.md): setting it up, and how it works.
- [Artisan command](artisan-command.md)
- [Configuration](configuration.md)
