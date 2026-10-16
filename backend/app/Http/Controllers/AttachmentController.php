<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttachmentResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments) {}

    public function store(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $maxKb = collect(config('attachments.types'))->max('max_kb');
        $request->validate([
            'file' => ['required', 'file', "max:{$maxKb}"],
        ]);

        $attachment = $this->attachments->store($task, $request->file('file'), $request->user());

        return (new AttachmentResource($attachment->refresh()))->response()->setStatusCode(201);
    }

    public function download(TaskAttachment $attachment): StreamedResponse|JsonResponse
    {
        if ($attachment->scan_status === TaskAttachment::SCAN_PENDING) {
            return response()->json(['message' => 'This file is still being scanned. Try again in a moment.'], 409);
        }

        if ($attachment->scan_status === TaskAttachment::SCAN_INFECTED) {
            return response()->json(['message' => 'This file failed the virus scan and was removed.'], 410);
        }

        return $this->attachments->disk()->download($attachment->file_path, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function thumbnail(TaskAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->thumbnail_path, 404);

        return $this->attachments->disk()->response($attachment->thumbnail_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function destroy(TaskAttachment $attachment): Response
    {
        Gate::authorize('delete', $attachment);

        $this->attachments->delete($attachment);

        return response()->noContent();
    }
}
