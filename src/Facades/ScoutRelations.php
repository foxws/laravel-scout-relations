<?php

namespace Foxws\ScoutRelations\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Foxws\ScoutRelations\ScoutRelations
 */
class ScoutRelations extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Foxws\ScoutRelations\ScoutRelations::class;
    }
}
