---
sidebar_position: 4
---

# Configuration

Publish the config file with:

```bash
php artisan vendor:publish --tag="scout-relations-config"
```

Available options in `config/scout-relations.php`:

| Key | Env variable | Default | Description |
|---|---|---|---|
| `enabled` | `SCOUT_RELATIONS_ENABLED` | `true` | Disable all automatic relation syncing |
| `chunk.searchable` | `SCOUT_RELATIONS_CHUNK_SEARCHABLE` | `500` | Chunk size for `searchable()` calls |
| `chunk.unsearchable` | `SCOUT_RELATIONS_CHUNK_UNSEARCHABLE` | `500` | Chunk size reserved for future use |
