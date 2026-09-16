---
section: Usage
order: 2
---

# Artisan command

Use this command to manually re-index every related record for all
instances of a model. This works even if automatic syncing is turned off
with the `enabled` config option.

```bash
php artisan scout:index-relations "App\Models\Author"
```

This command goes through every record of the given model in chunks, calls
`reindexSearchableRelations()` on each one, and shows a progress bar while it
runs.
