<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations;

use Foxws\ScoutRelations\Commands\ScoutRelationsCommand;
use Foxws\ScoutRelations\Support\SearchableRelationsState;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ScoutRelationsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-scout-relations')
            ->hasConfigFile()
            ->hasCommand(ScoutRelationsCommand::class);
    }

    public function packageBooted(): void
    {
        $this->app->scoped(SearchableRelationsState::class);
    }
}
