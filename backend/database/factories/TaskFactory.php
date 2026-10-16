<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(Task::STATUSES),
            'priority' => fake()->randomElement(Task::PRIORITIES),
            'assigned_user_id' => null,
            'created_by' => User::factory(),
            'due_date' => fake()->optional()->dateTimeBetween('-1 week', '+3 weeks')?->format('Y-m-d'),
        ];
    }
}
