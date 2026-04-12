<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Commands;

use Foxws\ScoutRelations\Concerns\HasSearchableRelations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class ScoutRelationsCommand extends Command
{
    public $signature = 'scout:index-relations {model : The model class to re-index relations for}';

    public $description = 'Re-index all searchable relations for the given model';

    public function handle(): int
    {
        $model = $this->argument('model');

        if (! class_exists($model)) {
            $this->error("Class [{$model}] does not exist.");

            return self::FAILURE;
        }

        if (! in_array(HasSearchableRelations::class, class_uses_recursive($model))) {
            $this->error("Class [{$model}] does not use the HasSearchableRelations trait.");

            return self::FAILURE;
        }

        $instance = new $model;
        $chunkSize = Config::integer('scout-relations.chunk.searchable', 500);
        $total = $instance->newQuery()->count();

        $this->info("Re-indexing relations for [{$model}] ({$total} records)...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $instance->newQuery()->chunkById($chunkSize, function ($models) use ($bar): void {
            foreach ($models as $record) {
                $record->reindexSearchableRelations();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
