<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_meets_the_minimum_sample_data(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(5, User::count());
        $this->assertGreaterThanOrEqual(15, Task::count());
        $this->assertGreaterThanOrEqual(10, TaskComment::count());
        $this->assertSame(1, User::where('role', 'admin')->count());
    }

    public function test_deleting_a_task_cascades_to_comments_and_attachments(): void
    {
        $task = Task::factory()->create();
        TaskComment::create(['task_id' => $task->id, 'user_id' => $task->created_by, 'comment' => 'x']);
        TaskAttachment::create([
            'task_id' => $task->id, 'file_name' => 'a.txt', 'file_path' => 'attachments/a.txt',
            'file_size' => 1, 'mime_type' => 'text/plain',
        ]);

        $task->delete();

        $this->assertDatabaseCount('task_comments', 0);
        $this->assertDatabaseCount('task_attachments', 0);
    }

    public function test_deleting_an_assignee_unassigns_their_tasks(): void
    {
        $assignee = User::factory()->create();
        $task = Task::factory()->create(['assigned_user_id' => $assignee->id]);

        $assignee->delete();

        $this->assertNull($task->fresh()->assigned_user_id);
    }

    public function test_task_list_filters_are_backed_by_indexes(): void
    {
        $indexed = collect(Schema::getIndexes('tasks'))->pluck('columns')->map(fn ($c) => implode(',', $c));

        foreach (['assigned_user_id,status', 'status,due_date', 'priority', 'created_by', 'created_at'] as $columns) {
            $this->assertContains($columns, $indexed, "Missing index on tasks({$columns})");
        }
    }
}
