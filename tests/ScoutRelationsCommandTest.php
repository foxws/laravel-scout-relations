<?php

declare(strict_types=1);

use Foxws\ScoutRelations\Tests\Fixtures\Author;
use Foxws\ScoutRelations\Tests\Fixtures\Post;
use Foxws\ScoutRelations\Tests\Fixtures\TaggedAuthor;
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

    Schema::create('tags', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });

    Schema::create('author_tag', function (Blueprint $table) {
        $table->unsignedBigInteger('author_id');
        $table->unsignedBigInteger('tag_id');
    });
});

afterEach(function () {
    Schema::dropIfExists('author_tag');
    Schema::dropIfExists('tags');
    Schema::dropIfExists('posts');
    Schema::dropIfExists('authors');
});

it('re-indexes relations for all records of the given model', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $authorId = DB::table('authors')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    DB::table('posts')->insert(['author_id' => $authorId, 'created_at' => now(), 'updated_at' => now()]);

    $this->artisan('scout:index-relations', ['model' => Author::class])
        ->assertSuccessful();

    Queue::assertPushed(MakeSearchable::class);
});

it('fails when the given class does not exist', function () {
    $this->artisan('scout:index-relations', ['model' => 'App\\Models\\NonExistent'])
        ->assertFailed();
});

it('fails when the given class does not use HasSearchableRelations', function () {
    $this->artisan('scout:index-relations', ['model' => Post::class])
        ->assertFailed();
});

it('succeeds with no records and dispatches no jobs', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $this->artisan('scout:index-relations', ['model' => Author::class])
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('re-indexes a shared many-to-many relation only once across owning records', function () {
    config(['scout.queue' => true]);
    Queue::fake();

    $firstAuthorId = DB::table('authors')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    $secondAuthorId = DB::table('authors')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    $tagId = DB::table('tags')->insertGetId(['created_at' => now(), 'updated_at' => now()]);

    DB::table('author_tag')->insert([
        ['author_id' => $firstAuthorId, 'tag_id' => $tagId],
        ['author_id' => $secondAuthorId, 'tag_id' => $tagId],
    ]);

    $this->artisan('scout:index-relations', ['model' => TaggedAuthor::class])
        ->assertSuccessful();

    // The tag is attached to two authors, but re-indexing dedupes by
    // related class, so it must only be pushed to the search engine once.
    Queue::assertPushed(MakeSearchable::class, 1);
});
