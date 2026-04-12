<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Commands;

use Illuminate\Console\Command;

class ScoutRelationsCommand extends Command
{
    public $signature = 'laravel-scout-relations';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
