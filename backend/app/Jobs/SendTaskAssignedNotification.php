<?php

namespace App\Jobs;

use App\Mail\TaskAssignedMail;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendTaskAssignedNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 60];

    public function __construct(public int $taskId, public int $assigneeId) {}

    public function handle(): void
    {
        $task = Task::with('creator')->find($this->taskId);
        $assignee = User::find($this->assigneeId);

        // The task may have been deleted or reassigned while the job waited in the queue.
        if (! $task || ! $assignee || $task->assigned_user_id !== $assignee->id) {
            return;
        }

        Mail::to($assignee)->send(new TaskAssignedMail($task, $assignee));
    }
}
