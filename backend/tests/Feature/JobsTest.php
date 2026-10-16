<?php

namespace Tests\Feature;

use App\Jobs\BulkUpdateTaskStatus;
use App\Jobs\ExportTasks;
use App\Jobs\SendTaskAssignedNotification;
use App\Mail\TaskAssignedMail;
use App\Models\Export;
use App\Models\Task;
use App\Models\TaskEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_email_goes_to_the_assignee(): void
    {
        $assignee = User::factory()->create();
        $task = Task::factory()->create(['assigned_user_id' => $assignee->id]);
        Mail::fake();

        (new SendTaskAssignedNotification($task->id, $assignee->id))->handle();

        Mail::assertSent(TaskAssignedMail::class, fn ($mail) => $mail->hasTo($assignee->email)
            && $mail->envelope()->subject === "Assigned to you: {$task->title}");
    }

    public function test_assignment_email_is_skipped_if_task_was_reassigned_meanwhile(): void
    {
        $first = User::factory()->create();
        $task = Task::factory()->create(['assigned_user_id' => User::factory()->create()->id]);
        Mail::fake();

        (new SendTaskAssignedNotification($task->id, $first->id))->handle();

        Mail::assertNothingSent();
    }

    public function test_bulk_update_changes_status_and_emits_one_event_per_task(): void
    {
        $tasks = Task::factory()->count(3)->create(['status' => 'todo']);
        $before = TaskEvent::count();

        (new BulkUpdateTaskStatus($tasks->pluck('id')->all(), 'done'))->handle();

        $this->assertSame(3, Task::where('status', 'done')->count());
        $this->assertSame($before + 3, TaskEvent::count());
        $this->assertSame(3, TaskEvent::where('type', 'task.updated')->count());
    }

    public function test_export_writes_filtered_csv_and_neutralises_formulas(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Task::factory()->create(['title' => '=HYPERLINK("http://evil")', 'status' => 'todo']);
        Task::factory()->create(['title' => 'Plain', 'status' => 'todo']);
        Task::factory()->create(['title' => 'Finished', 'status' => 'done']);

        $export = Export::create(['user_id' => $user->id, 'status' => 'pending', 'filters' => ['status' => ['todo']]]);
        (new ExportTasks($export->id))->handle();

        $export->refresh();
        $this->assertSame('done', $export->status);
        $this->assertSame(2, $export->row_count);

        $csv = Storage::disk('local')->get($export->file_path);
        $this->assertStringStartsWith(implode(',', ExportTasks::COLUMNS), $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Finished', $csv);
    }

    public function test_export_endpoints_are_private_to_their_owner(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        Task::factory()->count(2)->create();

        $id = $this->actingAsJwt($owner)->postJson('/api/exports', ['status' => 'todo,in_progress,done'])
            ->assertStatus(202)
            ->assertJsonPath('status', 'done')
            ->json('id');

        $download = $this->actingAsJwt($owner)->get("/api/exports/{$id}/download")->assertOk();
        $this->assertStringContainsString('text/csv', $download->headers->get('Content-Type'));

        $this->actingAsJwt(User::factory()->create())->getJson("/api/exports/{$id}")->assertNotFound();
    }
}
