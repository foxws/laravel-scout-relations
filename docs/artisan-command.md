---
section: Usage
order: 2
---

# Artisan command

Use this command to manually re-index every related record of a model's
searchable relations. This works even if automatic syncing is turned off
with the `enabled` config option.

```bash
php artisan scout:index-relations "App\Models\Author"
```

The command looks up the related model classes of the given model's
`searchableRelations()` and runs Scout's `makeAllSearchable()` once for each
distinct class that uses `Searchable`. Relations whose related model isn't
searchable are skipped.

Because each related class is imported once, a relation shared by many
records (such as a `belongsToMany`) isn't pushed to the search engine more
than once per run. Note that this imports every record of those classes,
not only the ones related to existing parents.
