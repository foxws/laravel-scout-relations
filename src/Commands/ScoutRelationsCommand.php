<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Commands;

use Foxws\ScoutRelations\Concerns\HasSearchableRelations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Laravel\Scout\Searchable;

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

        $relatedClasses = $this->searchableRelatedClasses(new $model);

        if ($relatedClasses === []) {
            $this->components->info("No searchable relations found for [{$model}].");

            return self::SUCCESS;
        }

        $chunkSize = Config::integer('scout-relations.chunk.searchable', 500);

        foreach ($relatedClasses as $relatedClass) {
            $this->components->task("Re-indexing [{$relatedClass}]", function () use ($relatedClass, $chunkSize): void {
                $relatedClass::makeAllSearchable($chunkSize);
            });
        }

        $this->components->info('Done.');

        return self::SUCCESS;
    }

    /**
     * Resolve the distinct, searchable related model classes referenced by the
     * given model's searchable relations.
     *
     * Re-indexing is performed once per related class (via the class's own
     * full-table Scout import) rather than once per owning record, so a
     * relation shared across many parents (e.g. belongsToMany) isn't pushed
     * to the search engine more than once per bulk run.
     *
     * @return array<int, class-string>
     */
    protected function searchableRelatedClasses(object $instance): array
    {
        $classes = [];

        foreach ($instance->searchableRelations() as $relation) {
            $related = $instance->{$relation}()->getRelated();

            if (! in_array(Searchable::class, class_uses_recursive($related))) {
                continue;
            }

            $classes[get_class($related)] = true;
        }

        return array_keys($classes);
    }
}
