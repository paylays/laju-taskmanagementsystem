<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskListRequest;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Jobs\BulkUpdateTaskStatus;
use App\Models\Task;
use App\Services\ApiCache;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(TaskListRequest $request, ApiCache $cache): JsonResponse
    {
        $filters = $request->validated();

        // Every signed-in user sees the same list for the same filters, so one cached copy
        // serves all the boards that refetch after a live event.
        $body = $cache->remember(ApiCache::TASKS, $request->query(), 60, function () use ($filters, $request) {
            $tasks = Task::query()
                ->with(['assignee:id,name', 'creator:id,name'])
                ->withCount(['attachments', 'comments'])
                ->filter($filters)
                ->sortBy($filters['sort'] ?? null)
                ->paginate($filters['per_page'] ?? 15)
                ->withQueryString();

            return TaskResource::collection($tasks)->toResponse($request)->getData(true);
        });

        return response()->json($body);
    }

    public function store(TaskRequest $request): JsonResponse
    {
        $task = Task::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return $this->present($task)->response()->setStatusCode(201);
    }

    public function show(Task $task): TaskResource
    {
        $task->load(['attachments' => fn ($q) => $q->latest('uploaded_at')]);

        return $this->present($task);
    }

    public function update(TaskRequest $request, Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $task->update($request->validated());

        return $this->present($task);
    }

    public function destroy(Task $task, AttachmentService $attachments): Response
    {
        Gate::authorize('delete', $task);

        // Rows cascade in the database, but the files on disk have to be removed by hand.
        $task->attachments->each(fn ($attachment) => $attachments->delete($attachment));
        $task->delete();

        return response()->noContent();
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
            'status' => ['required', Rule::in(Task::STATUSES)],
        ]);

        $allowed = Task::whereIn('id', $data['ids'])->get()
            ->filter(fn (Task $task) => Gate::allows('update', $task))
            ->pluck('id')
            ->all();

        if ($allowed) {
            BulkUpdateTaskStatus::dispatch($allowed, $data['status']);
        }

        return response()->json([
            'queued' => $allowed,
            'skipped' => array_values(array_diff($data['ids'], $allowed)),
        ], 202);
    }

    private function present(Task $task): TaskResource
    {
        $task->load(['assignee:id,name', 'creator:id,name'])->loadCount(['attachments', 'comments']);

        return new TaskResource($task);
    }
}
