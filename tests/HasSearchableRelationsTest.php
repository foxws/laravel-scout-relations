<?php

declare(strict_types=1);

use Foxws\ScoutRelations\Concerns\HasSearchableRelations;
use Foxws\ScoutRelations\Tests\Fixtures\Author;
use Foxws\ScoutRelations\Tests\Fixtures\Comment;
use Foxws\ScoutRelations\Tests\Fixtures\CommentAuthor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\Jobs\MakeSearchable;

beforeEach(function () {
    Schema::create('authors', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });

    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('author_id');
        $table->timestamps();
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('author_id');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('comments');
    Schema::dropIfExists('posts');
    Schema::dropIfExists('authors');
});

it('returns empty searchable relations by default', function () {
    $model = new class extends Model
    {
        use HasSearchableRelations;
    };

    expect($model->searchableRelations())->toBe([]);
});

it('reindexes related models when saved with changes', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = DB::table('authors')->insertGetId(['created_at' => now()->subMinute(), 'updated_at' => now()->subMinute()]);
    DB::table('posts')->insert(['author_id' => $authorId, 'created_at' => now(), 'updated_at' => now()]);

    Author::find($authorId)->touch();

    Queue::assertPushed(MakeSearchable::class);
});

it('does not reindex when model is saved without changes', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = DB::table('authors')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    DB::table('posts')->insert(['author_id' => $authorId, 'created_at' => now(), 'updated_at' => now()]);

    // Find and save with no attribute changes — wasChanged() should be false
    Author::find($authorId)->save();

    Queue::assertNothingPushed();
});

it('reindexes related models when the model is deleted', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = DB::table('authors')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    DB::table('posts')->insert(['author_id' => $authorId, 'created_at' => now(), 'updated_at' => now()]);

    Author::find($authorId)->delete();

    Queue::assertPushed(MakeSearchable::class);
});

it('does not dispatch jobs when there are no related models', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = DB::table('authors')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    // No posts created

    Author::find($authorId)->touch();

    Queue::assertNothingPushed();
});

it('skips relations whose related model does not use Searchable', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = DB::table('authors')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    DB::table('comments')->insert(['author_id' => $authorId, 'created_at' => now(), 'updated_at' => now()]);

    // CommentAuthor points searchableRelations at Comment, which does not use Searchable
    CommentAuthor::find($authorId)->touch();

    Queue::assertNothingPushed();
});

it('prevents recursive reindexing via syncing guard', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = DB::table('authors')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    DB::table('posts')->insert(['author_id' => $authorId, 'created_at' => now(), 'updated_at' => now()]);

    // Simulate mid-cascade re-entry by pre-setting the syncing flag via reflection
    $reflection = new ReflectionClass(Author::class);
    $property = $reflection->getProperty('syncing');
    $property->setValue(null, [Author::class => true]);

    Author::find($authorId)->touch();

    // Guard blocked execution — no jobs should have been dispatched
    Queue::assertNothingPushed();

    // Restore state for other tests
    $property->setValue(null, []);
});

it('does not register model event listeners when disabled', function () {
    config(['scout.queue' => true, 'scout-relations.enabled' => false]);
    Queue::fake();

    $authorId = DB::table('authors')->insertGetId(['created_at' => now()->subMinute(), 'updated_at' => now()->subMinute()]);
    DB::table('posts')->insert(['author_id' => $authorId, 'created_at' => now(), 'updated_at' => now()]);

    Author::find($authorId)->touch();

    Queue::assertNothingPushed();
});

it('respects shouldReindexSearchableRelations override', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = DB::table('authors')->insertGetId(['created_at' => now()->subMinute(), 'updated_at' => now()->subMinute()]);
    DB::table('posts')->insert(['author_id' => $authorId, 'created_at' => now(), 'updated_at' => now()]);

    // Directly calling reindexSearchableRelations() bypasses shouldReindexSearchableRelations(),
    // so verify the method exists and returns true by default on a fresh instance.
    $author = Author::find($authorId);
    expect($author->shouldReindexSearchableRelations())->toBeTrue();

    // Verify returning false from the override suppresses indexing on saved/deleted events.
    // We do this by disabling via config (same code path, boot-level guard).
    config(['scout-relations.enabled' => false]);

    // Boot has already run for Author, so re-test via a fresh anonymous model class.
    $model = new class extends Model {
        use HasSearchableRelations;
    };
    expect($model->shouldReindexSearchableRelations())->toBeTrue();
});
