<?php

namespace App\Observers;

use App\Http\Resources\AttachmentResource;
use App\Models\TaskAttachment;
use App\Models\TaskEvent;
use App\Services\ApiCache;

class TaskAttachmentObserver
{
    public function __construct(private readonly ApiCache $cache) {}

    public function created(TaskAttachment $attachment): void
    {
        // The task list shows attachment counts.
        $this->cache->bump(ApiCache::TASKS);
        $this->record('attachment.created', $attachment);
    }

    public function updated(TaskAttachment $attachment): void
    {
        $this->record('attachment.updated', $attachment);
    }

    public function deleted(TaskAttachment $attachment): void
    {
        $this->cache->bump(ApiCache::TASKS);

        TaskEvent::record('attachment.deleted', [
            'task_id' => $attachment->task_id,
            'attachment_id' => $attachment->id,
        ]);
    }

    private function record(string $type, TaskAttachment $attachment): void
    {
        TaskEvent::record($type, [
            'task_id' => $attachment->task_id,
            'attachment' => (new AttachmentResource($attachment))->resolve(),
        ]);
    }
}
