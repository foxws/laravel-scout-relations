<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Tests\Fixtures;

use Foxws\ScoutRelations\Concerns\HasSearchableRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Model whose searchable relation is a morphTo.
 */
class Note extends Model
{
    use HasSearchableRelations;

    protected $guarded = [];

    public function searchableRelations(): array
    {
        return ['notable'];
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
