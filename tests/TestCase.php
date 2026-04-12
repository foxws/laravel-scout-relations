<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations\Tests;

use Foxws\ScoutRelations\ScoutRelationsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Foxws\\ScoutRelations\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ScoutServiceProvider::class,
            ScoutRelationsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('scout.driver', 'null');
        config()->set('scout.queue', false);

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
