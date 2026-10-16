<?php

namespace App\Observers;

use App\Http\Resources\CommentResource;
use App\Models\TaskComment;
use App\Models\TaskEvent;
use App\Services\ApiCache;

class TaskCommentObserver
{
    public function __construct(private readonly ApiCache $cache) {}

    public function created(TaskComment $comment): void
    {
        // The task list shows comment counts.
        $this->cache->bump(ApiCache::TASKS);

        TaskEvent::record('comment.created', [
            'task_id' => $comment->task_id,
            'comment' => (new CommentResource($comment->loadMissing('user')))->resolve(),
        ]);
    }
}
