<?php

namespace Tests\Feature;

use App\Jobs\BulkUpdateTaskStatus;
use App\Jobs\SendTaskAssignedNotification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_list_is_paginated(): void
    {
        Task::factory()->count(20)->create(['created_by' => $this->user->id]);

        $this->actingAsJwt($this->user)->getJson('/api/tasks?per_page=5&page=2')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.last_page', 4);
    }

    public function test_list_filters_by_status_priority_assignee_and_search(): void
    {
        $other = User::factory()->create();
        $make = fn (array $attrs) => Task::factory()->create([...$attrs, 'created_by' => $this->user->id]);

        $match = $make(['title' => 'Rotate S3 keys', 'status' => 'todo', 'priority' => 'high', 'assigned_user_id' => $other->id]);
        $make(['title' => 'Rotate S3 keys', 'status' => 'done', 'priority' => 'high', 'assigned_user_id' => $other->id]);
        $make(['title' => 'Rotate S3 keys', 'status' => 'todo', 'priority' => 'low', 'assigned_user_id' => $other->id]);
        $make(['title' => 'Write docs', 'status' => 'todo', 'priority' => 'high', 'assigned_user_id' => $other->id]);
        $make(['title' => 'Rotate S3 keys', 'status' => 'todo', 'priority' => 'high', 'assigned_user_id' => null]);

        $this->actingAsJwt($this->user)
            ->getJson("/api/tasks?status=todo,in_progress&priority=high&assigned_user_id={$other->id}&search=s3")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        Task::factory()->create(['title' => '100% done', 'created_by' => $this->user->id]);
        Task::factory()->create(['title' => '100 items', 'created_by' => $this->user->id]);

        $this->actingAsJwt($this->user)->getJson('/api/tasks?search='.urlencode('100%'))
            ->assertJsonCount(1, 'data');
    }

    public function test_list_sorts_by_priority_weight_not_alphabetically(): void
    {
        foreach (['medium', 'high', 'low'] as $priority) {
            Task::factory()->create(['priority' => $priority, 'created_by' => $this->user->id]);
        }

        $response = $this->actingAsJwt($this->user)->getJson('/api/tasks?sort=-priority')->assertOk();

        $this->assertSame(['high', 'medium', 'low'], array_column($response->json('data'), 'priority'));
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $this->actingAsJwt($this->user)->getJson('/api/tasks?status=archived&sort=password')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status.0', 'sort']);
    }

    public function test_create_task_sets_creator_and_defaults(): void
    {
        $this->actingAsJwt($this->user)->postJson('/api/tasks', [
            'title' => 'Prepare demo',
            'due_date' => '2026-12-01',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Prepare demo')
            ->assertJsonPath('data.status', 'todo')
            ->assertJsonPath('data.priority', 'medium')
            ->assertJsonPath('data.created_by', $this->user->id)
            ->assertJsonPath('data.due_date', '2026-12-01');

        $this->assertDatabaseHas('tasks', ['title' => 'Prepare demo', 'created_by' => $this->user->id]);
    }

    public function test_create_validates_input(): void
    {
        $this->actingAsJwt($this->user)->postJson('/api/tasks', [
            'status' => 'archived',
            'assigned_user_id' => 999999,
            'due_date' => '01/12/2026',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status', 'assigned_user_id', 'due_date']);
    }

    public function test_assigning_someone_else_queues_an_email(): void
    {
        Queue::fake();
        $assignee = User::factory()->create();

        $this->actingAsJwt($this->user)
            ->postJson('/api/tasks', ['title' => 'Review PR', 'assigned_user_id' => $assignee->id])
            ->assertCreated();

        Queue::assertPushed(SendTaskAssignedNotification::class, fn ($job) => $job->assigneeId === $assignee->id);
    }

    public function test_assigning_yourself_does_not_queue_an_email(): void
    {
        Queue::fake();

        $this->actingAsJwt($this->user)
            ->postJson('/api/tasks', ['title' => 'Note to self', 'assigned_user_id' => $this->user->id])
            ->assertCreated();

        Queue::assertNotPushed(SendTaskAssignedNotification::class);
    }

    public function test_assignee_can_update_status(): void
    {
        $task = Task::factory()->create(['assigned_user_id' => $this->user->id, 'status' => 'todo']);

        $this->actingAsJwt($this->user)->putJson("/api/tasks/{$task->id}", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.title', $task->title);
    }

    public function test_unrelated_member_cannot_update_or_delete(): void
    {
        $task = Task::factory()->create();

        $this->actingAsJwt($this->user)->putJson("/api/tasks/{$task->id}", ['status' => 'done'])->assertForbidden();
        $this->actingAsJwt($this->user)->deleteJson("/api/tasks/{$task->id}")->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => $task->status]);
    }

    public function test_admin_can_delete_any_task(): void
    {
        $task = Task::factory()->create();

        $this->actingAsJwt(User::factory()->admin()->create())->deleteJson("/api/tasks/{$task->id}")->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_missing_task_returns_json_404_without_model_name(): void
    {
        $this->actingAsJwt($this->user)->getJson('/api/tasks/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not found.']);
    }

    public function test_bulk_status_only_queues_tasks_the_user_may_change(): void
    {
        Queue::fake();
        $mine = Task::factory()->count(2)->create(['created_by' => $this->user->id]);
        $notMine = Task::factory()->create();

        $this->actingAsJwt($this->user)->postJson('/api/tasks/bulk-status', [
            'ids' => [...$mine->pluck('id'), $notMine->id],
            'status' => 'done',
        ])
            ->assertStatus(202)
            ->assertJsonPath('skipped', [$notMine->id]);

        Queue::assertPushed(BulkUpdateTaskStatus::class, fn ($job) => $job->taskIds === $mine->pluck('id')->all() && $job->status === 'done');
    }
}
