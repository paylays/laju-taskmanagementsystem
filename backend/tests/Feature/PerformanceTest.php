<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function taskQueries(callable $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();

        return collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from `tasks`'))->count();
    }

    public function test_identical_task_list_requests_are_served_from_cache(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['created_by' => $user->id]);

        $first = $this->taskQueries(fn () => $this->actingAsJwt($user)->getJson('/api/tasks?status=todo,in_progress,done')->assertOk());
        $second = $this->taskQueries(fn () => $this->actingAsJwt($user)->getJson('/api/tasks?status=todo,in_progress,done')->assertOk());

        $this->assertGreaterThan(0, $first);
        $this->assertSame(0, $second);
    }

    public function test_cache_is_invalidated_by_task_and_comment_changes(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $user->id, 'title' => 'Before']);
        $this->actingAsJwt($user)->getJson('/api/tasks')->assertJsonPath('data.0.title', 'Before');

        $this->actingAsJwt($user)->putJson("/api/tasks/{$task->id}", ['title' => 'After']);
        $this->actingAsJwt($user)->getJson('/api/tasks')->assertJsonPath('data.0.title', 'After');

        $this->actingAsJwt($user)->postJson("/api/tasks/{$task->id}/comments", ['comment' => 'hi']);
        $this->actingAsJwt($user)->getJson('/api/tasks')->assertJsonPath('data.0.comments_count', 1);
    }

    public function test_user_list_cache_sees_new_users(): void
    {
        $user = User::factory()->create(['name' => 'Ana']);
        $this->actingAsJwt($user)->getJson('/api/users')->assertJsonCount(1, 'data');

        User::factory()->create(['name' => 'Budi']);

        $this->actingAsJwt($user)->getJson('/api/users')->assertJsonCount(2, 'data');
    }

    public function test_unchanged_json_answers_304_to_a_matching_etag(): void
    {
        $user = User::factory()->create();

        $first = $this->actingAsJwt($user)->getJson('/api/users')->assertOk();
        $etag = $first->headers->get('ETag');
        $this->assertNotEmpty($etag);
        $this->assertStringContainsString('no-cache', $first->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $first->headers->get('Cache-Control'));

        $this->actingAsJwt($user)->getJson('/api/users', ['If-None-Match' => $etag])
            ->assertStatus(304)
            ->assertContent('');
    }

    public function test_lane_query_has_a_status_then_created_at_index(): void
    {
        $indexed = collect(Schema::getIndexes('tasks'))->pluck('columns')->map(fn ($c) => implode(',', $c));

        $this->assertContains('status,created_at', $indexed);
    }
}
