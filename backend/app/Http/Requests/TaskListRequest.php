<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskListRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Accept both ?status=todo,done and ?status[]=todo&status[]=done.
        foreach (['status', 'priority'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => array_filter(explode(',', $this->input($key)))]);
            }
        }
    }

    public function rules(): array
    {
        $sortable = collect(Task::SORTABLE)->flatMap(fn ($f) => [$f, "-{$f}"])->all();

        return [
            'status' => ['sometimes', 'array'],
            'status.*' => [Rule::in(Task::STATUSES)],
            'priority' => ['sometimes', 'array'],
            'priority.*' => [Rule::in(Task::PRIORITIES)],
            'assigned_user_id' => ['sometimes', 'integer'],
            'created_by' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string', 'max:100'],
            'due_from' => ['sometimes', 'date_format:Y-m-d'],
            'due_to' => ['sometimes', 'date_format:Y-m-d'],
            'sort' => ['sometimes', Rule::in($sortable)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
