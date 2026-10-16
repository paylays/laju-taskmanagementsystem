<?php

namespace App\Jobs;

use App\Models\TaskAttachment;
use App\Services\AttachmentService;
use App\Services\ThumbnailGenerator;
use App\Services\VirusScanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAttachment implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $attachmentId) {}

    public function handle(AttachmentService $attachments, VirusScanner $scanner, ThumbnailGenerator $thumbnails): void
    {
        $attachment = TaskAttachment::find($this->attachmentId);
        if (! $attachment || $attachment->scan_status !== TaskAttachment::SCAN_PENDING) {
            return;
        }

        $disk = $attachments->disk();
        usleep(config('attachments.scan_delay_ms') * 1000);

        if ($scanner->isInfected($disk->readStream($attachment->file_path))) {
            // Keep the row so the uploader sees why the file vanished, but never keep the bytes.
            $disk->delete($attachment->file_path);
            $attachment->update(['scan_status' => TaskAttachment::SCAN_INFECTED]);

            return;
        }

        $attachment->update([
            'scan_status' => TaskAttachment::SCAN_CLEAN,
            'thumbnail_path' => $attachment->isImage() ? $thumbnails->generate($disk, $attachment->file_path) : null,
        ]);
    }
}
