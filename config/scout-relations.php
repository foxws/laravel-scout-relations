<?php

// config for Foxws/ScoutRelations
return [

    /*
    |--------------------------------------------------------------------------
    | Enable Scout Relations
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable the Scout Relations package.
    | When disabled, no relation syncing will be performed.
    |
    */

    'enabled' => env('SCOUT_RELATIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing relation data into the search engine. This allows you
    | to fine tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'searchable' => env('SCOUT_RELATIONS_CHUNK_SEARCHABLE', 500),
        'unsearchable' => env('SCOUT_RELATIONS_CHUNK_UNSEARCHABLE', 500),
    ],

];
