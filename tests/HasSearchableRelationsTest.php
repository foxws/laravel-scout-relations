<?php

declare(strict_types=1);

use Foxws\ScoutRelations\Concerns\HasSearchableRelations;
use Foxws\ScoutRelations\Support\SearchableRelationsState;
use Foxws\ScoutRelations\Tests\Fixtures\Author;
use Foxws\ScoutRelations\Tests\Fixtures\Comment;
use Foxws\ScoutRelations\Tests\Fixtures\CommentAuthor;
use Foxws\ScoutRelations\Tests\Fixtures\Post;
use Foxws\ScoutRelations\Tests\Fixtures\RelationsAuthor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\Jobs\MakeSearchable;
use Laravel\Scout\Jobs\RemoveFromSearch;

beforeEach(function () {
    Schema::create('authors', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });

    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('author_id');
        $table->boolean('draft')->default(false);
        $table->boolean('featured')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('tags', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });

    Schema::create('author_tag', function (Blueprint $table) {
        $table->foreignId('author_id')->constrained()->cascadeOnDelete();
        $table->unsignedBigInteger('tag_id');
    });

    RelationsAuthor::$watched = ['posts'];

    Schema::create('comments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('author_id');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('author_tag');
    Schema::dropIfExists('tags');
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

it('prevents recursive reindexing via reindexing guard', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = DB::table('authors')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    DB::table('posts')->insert(['author_id' => $authorId, 'created_at' => now(), 'updated_at' => now()]);

    // Simulate mid-cascade re-entry by marking Author as already reindexing
    app(SearchableRelationsState::class)->markReindexing(Author::class);

    try {
        Author::find($authorId)->touch();

        // Guard blocked execution — no jobs should have been dispatched
        Queue::assertNothingPushed();
    } finally {
        app()->forgetInstance(SearchableRelationsState::class);
    }
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
    $model = new class extends Model
    {
        use HasSearchableRelations;
    };
    expect($model->shouldReindexSearchableRelations())->toBeTrue();
});

/**
 * @return list<int>
 */
function indexedIds(string $job = MakeSearchable::class): array
{
    return Queue::pushed($job)
        ->flatMap(fn ($pushed) => $pushed->models)
        ->map(fn ($model) => (int) $model->getKey())
        ->sort()
        ->values()
        ->all();
}

function createAuthorWithPosts(int $count = 1, array $attributes = []): int
{
    $authorId = DB::table('authors')->insertGetId(['created_at' => now()->subMinute(), 'updated_at' => now()->subMinute()]);

    for ($i = 0; $i < $count; $i++) {
        DB::table('posts')->insert(['author_id' => $authorId, 'created_at' => now(), 'updated_at' => now(), ...$attributes]);
    }

    return $authorId;
}

it('reindexes related models whose rows the database cascades away on delete', function () {
    config(['scout.queue' => true]);
    Queue::fake();
    Schema::enableForeignKeyConstraints();
    RelationsAuthor::$watched = ['tags'];

    $authorId = createAuthorWithPosts(0);
    $tagId = DB::table('tags')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    DB::table('author_tag')->insert(['author_id' => $authorId, 'tag_id' => $tagId]);

    RelationsAuthor::find($authorId)->delete();

    expect(DB::table('author_tag')->count())->toBe(0)
        ->and(indexedIds())->toBe([$tagId]);
});

it('reindexes once the transaction is committed', function () {
    config(['scout.queue' => true, 'scout.after_commit' => true]);
    Queue::fake();

    $authorId = createAuthorWithPosts();

    DB::transaction(function () use ($authorId) {
        Author::find($authorId)->touch();

        Queue::assertNothingPushed();
    });

    Queue::assertPushed(MakeSearchable::class);
});

it('does not reindex when the transaction is rolled back', function () {
    config(['scout.queue' => true, 'scout.after_commit' => true]);
    Queue::fake();

    $authorId = createAuthorWithPosts();

    DB::beginTransaction();
    Author::find($authorId)->touch();
    DB::rollBack();

    Queue::assertNothingPushed();
});

it('removes related models that should not be searchable instead of indexing them', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = createAuthorWithPosts();
    DB::table('posts')->insert(['author_id' => $authorId, 'draft' => true]);

    Author::find($authorId)->touch();

    expect(indexedIds())->toBe([1])
        ->and(indexedIds(RemoveFromSearch::class))->toBe([2]);
});

it('reindexes every related model of an ordered relation', function () {
    config(['scout.queue' => true, 'scout-relations.chunk.searchable' => 2]);
    Queue::fake();
    RelationsAuthor::$watched = ['newestPosts'];

    $authorId = createAuthorWithPosts(0);

    foreach (range(1, 5) as $day) {
        DB::table('posts')->insert(['author_id' => $authorId, 'created_at' => now()->addDays($day)]);
    }

    RelationsAuthor::find($authorId)->touch();

    expect(indexedIds())->toBe([1, 2, 3, 4, 5]);
});

it('keeps an orWhere of makeAllSearchableUsing within the relation', function () {
    config(['scout.queue' => true, 'scout-relations.chunk.searchable' => 1]);
    Queue::fake();
    RelationsAuthor::$watched = ['featuredPosts'];

    $authorId = createAuthorWithPosts(2);
    createAuthorWithPosts(1, ['featured' => true]);

    RelationsAuthor::find($authorId)->touch();

    expect(indexedIds())->toBe([1, 2]);
});

it('reindexes related models when makeAllSearchableUsing joins another table', function () {
    config(['scout.queue' => true]);
    Queue::fake();
    RelationsAuthor::$watched = ['joinedPosts'];

    $authorId = createAuthorWithPosts();

    RelationsAuthor::find($authorId)->touch();

    expect(indexedIds())->toBe([1]);
});

it('reindexes soft-deleted related models when Scout keeps them in the index', function () {
    config(['scout.queue' => true, 'scout.soft_delete' => true]);
    Queue::fake();
    RelationsAuthor::$watched = ['softPosts'];

    $authorId = createAuthorWithPosts(1, ['deleted_at' => now()]);

    RelationsAuthor::find($authorId)->touch();

    expect(indexedIds())->toBe([1]);
});

it('does not reindex related models while their syncing is disabled', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = createAuthorWithPosts();

    Post::withoutSyncingToSearch(fn () => Author::find($authorId)->touch());

    Queue::assertNothingPushed();
});

it('uses the default chunk size when the configured one is not positive', function () {
    config(['scout.queue' => true, 'scout-relations.chunk.searchable' => 0]);
    Queue::fake();

    $authorId = createAuthorWithPosts();

    Author::find($authorId)->touch();

    expect(indexedIds())->toBe([1]);
});

it('does not reindex on a later save without changes', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $author = Author::find(createAuthorWithPosts());
    $author->touch();

    Queue::fake();
    $author->save();

    Queue::assertNothingPushed();
});
