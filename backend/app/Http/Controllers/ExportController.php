<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskListRequest;
use App\Jobs\ExportTasks;
use App\Models\Export;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function store(TaskListRequest $request): JsonResponse
    {
        $export = Export::create([
            'user_id' => $request->user()->id,
            'status' => 'pending',
            'filters' => collect($request->validated())->except(['page', 'per_page'])->all(),
        ]);

        ExportTasks::dispatch($export->id);

        return response()->json($this->present($export->refresh()), 202);
    }

    public function show(Request $request, Export $export): JsonResponse
    {
        abort_unless($export->user_id === $request->user()->id, 404);

        return response()->json($this->present($export));
    }

    public function download(Request $request, Export $export): StreamedResponse|JsonResponse
    {
        abort_unless($export->user_id === $request->user()->id, 404);

        if ($export->status !== 'done') {
            return response()->json(['message' => 'The export is not ready yet.'], 409);
        }

        return Storage::disk(config('attachments.disk'))->download(
            $export->file_path,
            "lajur-tasks-{$export->created_at->format('Ymd-His')}.csv",
            ['Content-Type' => 'text/csv'],
        );
    }

    private function present(Export $export): array
    {
        return [
            'id' => $export->id,
            'status' => $export->status,
            'row_count' => $export->row_count,
            'created_at' => $export->created_at?->toIso8601String(),
        ];
    }
}
