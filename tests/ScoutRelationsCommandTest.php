<?php

declare(strict_types=1);

use Foxws\ScoutRelations\Tests\Fixtures\Author;
use Foxws\ScoutRelations\Tests\Fixtures\Post;
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
});

afterEach(function () {
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
