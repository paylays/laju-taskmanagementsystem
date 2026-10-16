<?php

namespace App\Services;

use App\Jobs\ProcessAttachment;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    public function store(Task $task, UploadedFile $file, User $uploader): TaskAttachment
    {
        $mime = (string) $file->getMimeType();
        $type = $this->categoryFor($mime);

        if ($type === null) {
            throw ValidationException::withMessages([
                'file' => "Files of type {$mime} are not allowed.",
            ]);
        }

        $maxKb = config("attachments.types.{$type}.max_kb");
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'file' => sprintf('%s files must be %d MB or smaller.', ucfirst($type), $maxKb / 1024),
            ]);
        }

        $extension = config("attachments.types.{$type}.mimes")[$mime];
        $path = $file->storeAs("attachments/{$task->id}", Str::uuid().'.'.$extension, config('attachments.disk'));

        $attachment = $task->attachments()->create([
            'uploaded_by' => $uploader->id,
            'file_name' => $this->cleanName($file->getClientOriginalName()),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $mime,
            'scan_status' => TaskAttachment::SCAN_PENDING,
        ]);

        ProcessAttachment::dispatch($attachment->id);

        return $attachment;
    }

    public function delete(TaskAttachment $attachment): void
    {
        $this->disk()->delete(array_filter([$attachment->file_path, $attachment->thumbnail_path]));
        $attachment->delete();
    }

    public function categoryFor(string $mime): ?string
    {
        foreach (config('attachments.types') as $type => $rules) {
            if (array_key_exists($mime, $rules['mimes'])) {
                return $type;
            }
        }

        return null;
    }

    public function disk(): Filesystem
    {
        return Storage::disk(config('attachments.disk'));
    }

    // The original name is only ever echoed back in Content-Disposition, never used as a path.
    private function cleanName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F\/\\\\"]/u', '', basename($name)) ?? '';
        $name = Str::limit(trim($name), 200, '');

        return $name !== '' ? $name : 'file';
    }
}
