<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use PHPUnit\Framework\TestCase;

class TaskPolicyTest extends TestCase
{
    private function user(int $id, string $role = User::ROLE_MEMBER): User
    {
        return (new User)->forceFill(['id' => $id, 'role' => $role]);
    }

    private function task(int $creator, ?int $assignee): Task
    {
        return (new Task)->forceFill(['created_by' => $creator, 'assigned_user_id' => $assignee]);
    }

    public function test_creator_assignee_and_admin_can_update(): void
    {
        $policy = new TaskPolicy;
        $task = $this->task(creator: 1, assignee: 2);

        $this->assertTrue($policy->update($this->user(1), $task));
        $this->assertTrue($policy->update($this->user(2), $task));
        $this->assertTrue($policy->update($this->user(9, User::ROLE_ADMIN), $task));
        $this->assertFalse($policy->update($this->user(3), $task));
    }

    public function test_only_creator_and_admin_can_delete(): void
    {
        $policy = new TaskPolicy;
        $task = $this->task(creator: 1, assignee: 2);

        $this->assertTrue($policy->delete($this->user(1), $task));
        $this->assertTrue($policy->delete($this->user(9, User::ROLE_ADMIN), $task));
        $this->assertFalse($policy->delete($this->user(2), $task));
    }
}
