<?php

namespace App\Jobs;

use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BulkUpdateTaskStatus implements ShouldQueue
{
    use Queueable;

    /**
     * @param list<int> $taskIds already filtered to tasks the requesting user may update
     */
    public function __construct(public array $taskIds, public string $status) {}

    public function handle(): void
    {
        // Saved one by one (not a mass UPDATE) so the observer emits a real-time event per task.
        Task::whereIn('id', $this->taskIds)
            ->where('status', '!=', $this->status)
            ->each(fn (Task $task) => $task->update(['status' => $this->status]));
    }
}
