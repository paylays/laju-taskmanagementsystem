<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Everyone signed in can read every task (small-team tool).
 * Changing a task is limited to admins, its creator, and its assignee; deleting to admins and the creator.
 */
class TaskPolicy
{
    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || $task->created_by === $user->id
            || $task->assigned_user_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->created_by === $user->id;
    }
}
