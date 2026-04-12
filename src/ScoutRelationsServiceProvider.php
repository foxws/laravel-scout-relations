<?php

declare(strict_types=1);

namespace Foxws\ScoutRelations;

use Foxws\ScoutRelations\Commands\ScoutRelationsCommand;
use Foxws\ScoutRelations\Concerns\HasSearchableRelations;
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
        $this->callAfterResolving('octane', function (): void {
            /** @phpstan-ignore-next-line */
            \Laravel\Octane\Facades\Octane::listen(
                \Laravel\Octane\Events\RequestReceived::class,
                fn () => HasSearchableRelations::flushSyncingState(),
            );
        });
    }
}
