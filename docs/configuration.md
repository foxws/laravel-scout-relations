---
section: Reference
order: 1
---

# Configuration

Publish the config file with:

```bash
php artisan vendor:publish --tag="scout-relations-config"
```

This creates `config/scout-relations.php`, with the following options:

| Key | Env variable | Default | Description |
|---|---|---|---|
| `enabled` | `SCOUT_RELATIONS_ENABLED` | `true` | Turns automatic relation syncing on or off. |
| `chunk.searchable` | `SCOUT_RELATIONS_CHUNK_SEARCHABLE` | `500` | How many records are processed at a time when calling `searchable()`. |
| `chunk.unsearchable` | `SCOUT_RELATIONS_CHUNK_UNSEARCHABLE` | `500` | Chunk size reserved for future use. |
