<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportTasks implements ShouldQueue
{
    use Queueable;

    public const COLUMNS = ['id', 'title', 'status', 'priority', 'assignee', 'creator', 'due_date', 'created_at'];

    public function __construct(public int $exportId) {}

    public function handle(): void
    {
        $export = Export::findOrFail($this->exportId);
        $export->update(['status' => 'processing']);

        $out = fopen('php://temp', 'w+');
        fputcsv($out, self::COLUMNS);
        $rows = 0;

        Task::with(['assignee:id,name', 'creator:id,name'])
            ->filter($export->filters ?? [])
            ->sortBy($export->filters['sort'] ?? null)
            ->lazy(500)
            ->each(function (Task $task) use ($out, &$rows) {
                fputcsv($out, [
                    $task->id,
                    $this->safeCell($task->title),
                    $task->status,
                    $task->priority,
                    $this->safeCell($task->assignee?->name ?? ''),
                    $this->safeCell($task->creator?->name ?? ''),
                    $task->due_date?->format('Y-m-d'),
                    $task->created_at?->toDateTimeString(),
                ]);
                $rows++;
            });

        rewind($out);
        $path = "exports/tasks-{$export->id}.csv";
        Storage::disk(config('attachments.disk'))->put($path, stream_get_contents($out));
        fclose($out);

        $export->update(['status' => 'done', 'file_path' => $path, 'row_count' => $rows]);
    }

    public function failed(Throwable $e): void
    {
        Export::whereKey($this->exportId)->update(['status' => 'failed']);
    }

    // Spreadsheet apps execute cells that start with these characters as formulas.
    private function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
