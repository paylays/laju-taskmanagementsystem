<?php

namespace App\Policies;

use App\Models\TaskAttachment;
use App\Models\User;

class TaskAttachmentPolicy
{
    public function delete(User $user, TaskAttachment $attachment): bool
    {
        return $user->isAdmin()
            || $attachment->uploaded_by === $user->id
            || $attachment->task->created_by === $user->id;
    }
}
