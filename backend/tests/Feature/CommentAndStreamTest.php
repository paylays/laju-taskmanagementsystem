<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentAndStreamTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_is_created_and_listed_oldest_first(): void
    {
        $user = User::factory()->create(['name' => 'Rina']);
        $task = Task::factory()->create();

        $this->actingAsJwt($user)->postJson("/api/tasks/{$task->id}/comments", ['comment' => '  First  '])
            ->assertCreated()
            ->assertJsonPath('data.comment', 'First')
            ->assertJsonPath('data.user.name', 'Rina');
        $this->actingAsJwt($user)->postJson("/api/tasks/{$task->id}/comments", ['comment' => 'Second']);

        $this->actingAsJwt($user)->getJson("/api/tasks/{$task->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.comment', 'First')
            ->assertJsonPath('data.1.comment', 'Second');
    }

    public function test_empty_comment_is_rejected(): void
    {
        $task = Task::factory()->create();

        $this->actingAsJwt(User::factory()->create())->postJson("/api/tasks/{$task->id}/comments", ['comment' => ''])
            ->assertUnprocessable();
    }

    public function test_fresh_stream_starts_at_the_latest_event(): void
    {
        $user = User::factory()->create();
        Task::factory()->create();
        $latest = TaskEvent::max('id');

        $body = $this->get('/api/stream?token='.$this->tokenFor($user))->assertOk()->streamedContent();

        $this->assertStringContainsString("id: {$latest}\nevent: ready", $body);
        $this->assertStringNotContainsString('event: task.created', $body);
    }

    public function test_stream_replays_events_after_last_event_id(): void
    {
        $user = User::factory()->create();
        $before = (int) TaskEvent::max('id');

        $task = Task::factory()->create(['created_by' => $user->id]);
        $this->actingAsJwt($user)->postJson("/api/tasks/{$task->id}/comments", ['comment' => 'live']);

        $response = $this->get('/api/stream?token='.$this->tokenFor($user), ['Last-Event-ID' => (string) $before]);

        $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
        $body = $response->streamedContent();
        $this->assertStringContainsString('event: task.created', $body);
        $this->assertStringContainsString('event: comment.created', $body);
        $this->assertStringContainsString('"comment":"live"', $body);
    }

    public function test_query_string_token_only_works_for_the_stream(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->getJson("/api/tasks?token={$token}")->assertUnauthorized();
        $this->get('/api/stream')->assertUnauthorized();
    }
}
