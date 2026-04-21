<?php

declare(strict_types=1);

use Foxws\ScoutRelations\Support\SearchableRelationsState;

afterEach(function () {
    SearchableRelationsState::flushReindexingState();
});

it('reports a class as not reindexing by default', function () {
    expect(SearchableRelationsState::isReindexing('App\Models\Post'))->toBeFalse();
});

it('reports a class as reindexing after marking it', function () {
    SearchableRelationsState::markReindexing('App\Models\Post');

    expect(SearchableRelationsState::isReindexing('App\Models\Post'))->toBeTrue();
});

it('does not affect other classes when marking one', function () {
    SearchableRelationsState::markReindexing('App\Models\Post');

    expect(SearchableRelationsState::isReindexing('App\Models\Author'))->toBeFalse();
});

it('reports a class as not reindexing after unmarking it', function () {
    SearchableRelationsState::markReindexing('App\Models\Post');
    SearchableRelationsState::unmarkReindexing('App\Models\Post');

    expect(SearchableRelationsState::isReindexing('App\Models\Post'))->toBeFalse();
});

it('clears all tracked classes on flush', function () {
    SearchableRelationsState::markReindexing('App\Models\Post');
    SearchableRelationsState::markReindexing('App\Models\Author');

    SearchableRelationsState::flushReindexingState();

    expect(SearchableRelationsState::isReindexing('App\Models\Post'))->toBeFalse()
        ->and(SearchableRelationsState::isReindexing('App\Models\Author'))->toBeFalse();
});

it('unmark is a no-op for a class that was never marked', function () {
    expect(fn () => SearchableRelationsState::unmarkReindexing('App\Models\Post'))->not->toThrow(Throwable::class);
    expect(SearchableRelationsState::isReindexing('App\Models\Post'))->toBeFalse();
});
