<?php

namespace App\Observers;

use App\Http\Resources\TaskResource;
use App\Jobs\SendTaskAssignedNotification;
use App\Models\Task;
use App\Models\TaskEvent;
use App\Services\ApiCache;

class TaskObserver
{
    public function __construct(private readonly ApiCache $cache) {}

    public function created(Task $task): void
    {
        $this->cache->bump(ApiCache::TASKS);
        TaskEvent::record('task.created', ['task' => $this->serialize($task)]);
        $this->notifyAssignee($task);
    }

    public function updated(Task $task): void
    {
        $this->cache->bump(ApiCache::TASKS);
        TaskEvent::record('task.updated', ['task' => $this->serialize($task)]);

        if ($task->wasChanged('assigned_user_id')) {
            $this->notifyAssignee($task);
        }
    }

    public function deleted(Task $task): void
    {
        $this->cache->bump(ApiCache::TASKS);
        TaskEvent::record('task.deleted', ['task_id' => $task->id]);
    }

    private function notifyAssignee(Task $task): void
    {
        // Assigning a task to yourself is not news.
        if ($task->assigned_user_id && $task->assigned_user_id !== auth()->id()) {
            SendTaskAssignedNotification::dispatch($task->id, $task->assigned_user_id);
        }
    }

    private function serialize(Task $task): array
    {
        $task->loadMissing(['assignee', 'creator'])->loadCount(['attachments', 'comments']);

        return (new TaskResource($task))->resolve();
    }
}
