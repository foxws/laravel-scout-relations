# Changelog

All notable changes to `laravel-scout-relations` will be documented in this file.

## 2.0.0 - 2026-11-23

This release requires PHP 8.5 or newer and Laravel 13. PHP 8.4 and Laravel 12 are no longer supported, and PHP 8.6 is now tested in CI. Stay on the previous major version if you still run PHP 8.4 or Laravel 12.

Re-indexing related models now behaves like Scout's own observer:

- The related keys are read before a delete, so related models whose rows the database cascades away (such as a pivot table with `ON DELETE CASCADE`) are re-indexed too.
- With `scout.after_commit` enabled, re-indexing inside a transaction waits for the commit, and is skipped on a rollback.
- Related models whose `shouldBeSearchable()` returns `false` are removed from the index instead of indexed.
- Ordered relations (such as `hasMany()->latest()`) no longer skip records. `makeAllSearchableUsing()` is applied as a scope, so an `orWhere()` in it can't reach other parents' records or loop, and joins work.
- `withoutSyncingToSearch()` and `scout.soft_delete` are honoured, a chunk size that isn't positive uses `500`, and a later save without changes no longer re-indexes.
- `scout:index-relations` imports the classes a `morphTo` relation points to.

**Upgrading:** related models that aren't searchable now get a `RemoveFromSearch` job, and with `scout.after_commit` enabled re-indexing happens after the commit. Read the parent null-safely in `toSearchableArray()` (`$this->author?->name`), as related models are re-indexed after it's deleted.

### What's Changed

* Drop PHP 8.4 and add PHP 8.6 by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/19
* Drop Laravel 12, which Pest 5 doesn't support by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/20
* Remove auto-merge and limit the workflows by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/21
* Reindex related models reliably by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/22

**Full Changelog**: https://github.com/foxws/laravel-scout-relations/compare/1.0.4...2.0.0

## 1.0.4 - 2026-10-14

### What's Changed

* Add Laravel Boost tip to README and docs by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/17

**Full Changelog**: https://github.com/foxws/laravel-scout-relations/compare/1.0.3...1.0.4

## 1.0.3 - 2026-10-13

### What's Changed

* Add Laravel Boost skill and fix Artisan command docs by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/16

**Full Changelog**: https://github.com/foxws/laravel-scout-relations/compare/1.0.2...1.0.3

## 1.0.2 - 2026-10-02

### What's Changed

* docs: add the foxws.nl homepage group, a hero lead and a clearer introduction by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/14
* Raise PHPStan to level 8 by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/15

**Full Changelog**: https://github.com/foxws/laravel-scout-relations/compare/1.0.1...1.0.2

## 1.0.1 - 2026-09-16

### What's Changed

* Bump actions/upload-pages-artifact from 3 to 5 by @dependabot[bot] in https://github.com/foxws/laravel-scout-relations/pull/11
* Bump actions/deploy-pages from 4 to 5 by @dependabot[bot] in https://github.com/foxws/laravel-scout-relations/pull/10
* Bump actions/setup-node from 4 to 7 by @dependabot[bot] in https://github.com/foxws/laravel-scout-relations/pull/9
* Bump actions/checkout from 4 to 7 by @dependabot[bot] in https://github.com/foxws/laravel-scout-relations/pull/8
* Bump pnpm/action-setup from 4 to 6 by @dependabot[bot] in https://github.com/foxws/laravel-scout-relations/pull/7
* Bump react from 19.2.8 to 19.3.0 in /website by @dependabot[bot] in https://github.com/foxws/laravel-scout-relations/pull/12
* Migrate docs to laravel-docs front matter and simplify prose by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/13

**Full Changelog**: https://github.com/foxws/laravel-scout-relations/compare/1.0.0...1.0.1

## 1.0.0 - 2026-07-27

### What's Changed

* Bump dependabot/fetch-metadata from 3.0.0 to 3.1.0 by @dependabot[bot] in https://github.com/foxws/laravel-scout-relations/pull/3
* Refactor ScoutRelationsCommand and ServiceProvider; add Tag and TaggedAuthor models by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/5
* Bump actions/checkout from 6 to 7 by @dependabot[bot] in https://github.com/foxws/laravel-scout-relations/pull/4

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/foxws/laravel-scout-relations/pull/3

**Full Changelog**: https://github.com/foxws/laravel-scout-relations/compare/0.0.2...1.0.0

## 0.0.2 - 2026-04-21

### What's Changed

* Refactor HasSearchableRelations for reindexing and add unit tests by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/2

**Full Changelog**: https://github.com/foxws/laravel-scout-relations/compare/0.0.1...0.0.2

## 0.0.1 - 2026-04-12

### What's Changed

* Refactor package structure and enhance Scout Relations functionality by @francoism90 in https://github.com/foxws/laravel-scout-relations/pull/1

### New Contributors

* @francoism90 made their first contribution in https://github.com/foxws/laravel-scout-relations/pull/1

**Full Changelog**: https://github.com/foxws/laravel-scout-relations/commits/0.0.1
