<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Tests\Fixtures;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftPost extends Post
{
    use SoftDeletes;

    protected $table = 'posts';
}
