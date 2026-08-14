---
slug: /
sidebar_position: 1
---

# Introduction

Automatically re-index Scout-searchable related models when an Eloquent
model is saved or deleted.

When a parent model changes (e.g. an `Author`), its related Searchable
models (e.g. `Post`) are automatically queued for re-indexing, keeping your
search index consistent without any manual intervention.

## Requirements

- PHP 8.4+
- Laravel 12+
- [Laravel Scout](https://laravel.com/docs/scout)

Continue to [Installation](./installation.md) to get started.
