---
sidebar_position: 5
---

# Artisan command

To manually force re-indexing of all relations for a model (ignoring the
`enabled` flag), use:

```bash
php artisan scout:index-relations "App\Models\Author"
```

This chunks through every record of the given model and calls
`reindexSearchableRelations()` on each, with a progress bar.
