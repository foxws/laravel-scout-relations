<?php

declare(strict_types=1);

use Foxws\ScoutRelations\Support\SearchableRelationsState;

// Each test gets a fresh scoped instance via the Orchestra test application boot.

it('reports a class as not reindexing by default', function () {
    $state = app(SearchableRelationsState::class);

    expect($state->isReindexing('App\Models\Post'))->toBeFalse();
});

it('reports a class as reindexing after marking it', function () {
    $state = app(SearchableRelationsState::class);
    $state->markReindexing('App\Models\Post');

    expect($state->isReindexing('App\Models\Post'))->toBeTrue();
});

it('does not affect other classes when marking one', function () {
    $state = app(SearchableRelationsState::class);
    $state->markReindexing('App\Models\Post');

    expect($state->isReindexing('App\Models\Author'))->toBeFalse();
});

it('reports a class as not reindexing after unmarking it', function () {
    $state = app(SearchableRelationsState::class);
    $state->markReindexing('App\Models\Post');
    $state->unmarkReindexing('App\Models\Post');

    expect($state->isReindexing('App\Models\Post'))->toBeFalse();
});

it('clears all tracked classes when the scoped instance is refreshed', function () {
    $state = app(SearchableRelationsState::class);
    $state->markReindexing('App\Models\Post');
    $state->markReindexing('App\Models\Author');

    // Simulate Octane request boundary
    app()->forgetInstance(SearchableRelationsState::class);
    $fresh = app(SearchableRelationsState::class);

    expect($fresh->isReindexing('App\Models\Post'))->toBeFalse()
        ->and($fresh->isReindexing('App\Models\Author'))->toBeFalse();
});

it('unmark is a no-op for a class that was never marked', function () {
    $state = app(SearchableRelationsState::class);

    expect(fn () => $state->unmarkReindexing('App\Models\Post'))->not->toThrow(Throwable::class);
    expect($state->isReindexing('App\Models\Post'))->toBeFalse();
});
