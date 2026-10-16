<?php

namespace Tests\Feature;

use App\Jobs\ProcessAttachment;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->user = User::factory()->create();
        $this->task = Task::factory()->create(['created_by' => $this->user->id]);
    }

    /**
     * A real file on disk, so the MIME type is sniffed from its bytes
     * (Laravel's fake files report a type based on the name instead).
     */
    private function realUpload(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'lajur');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function upload(UploadedFile $file)
    {
        return $this->actingAsJwt($this->user)->post("/api/tasks/{$this->task->id}/attachments", ['file' => $file]);
    }

    public function test_image_upload_is_stored_scanned_and_thumbnailed(): void
    {
        $response = $this->upload(UploadedFile::fake()->image('whiteboard.jpg', 1200, 800))
            ->assertCreated()
            ->assertJsonPath('data.file_name', 'whiteboard.jpg')
            ->assertJsonPath('data.mime_type', 'image/jpeg')
            ->assertJsonPath('data.scan_status', 'clean')
            ->assertJsonPath('data.has_thumbnail', true);

        $attachment = TaskAttachment::findOrFail($response->json('data.id'));

        Storage::disk('local')->assertExists($attachment->file_path);
        Storage::disk('local')->assertExists($attachment->thumbnail_path);
        $this->assertStringStartsWith("attachments/{$this->task->id}/", $attachment->file_path);
        $this->assertStringEndsWith('.jpg', $attachment->file_path);
        $this->assertNotSame('whiteboard.jpg', basename($attachment->file_path));

        $thumbnail = Storage::disk('local')->get($attachment->thumbnail_path);
        [$width, $height] = getimagesizefromstring($thumbnail);
        $this->assertSame([320, 213], [$width, $height]);
        $this->assertStringContainsString("\xFF\xC2", $thumbnail, 'Thumbnail should be a progressive JPEG (SOF2 marker)');
    }

    public function test_documents_are_accepted_by_content(): void
    {
        $this->upload($this->realUpload('notes.txt', "meeting notes\nship on friday"))
            ->assertCreated()
            ->assertJsonPath('data.mime_type', 'text/plain')
            ->assertJsonPath('data.has_thumbnail', false);
    }

    public function test_executable_renamed_to_png_is_rejected(): void
    {
        $this->upload($this->realUpload('innocent.png', "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xff\xff\x00\x00".str_repeat("\x00", 64)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('task_attachments', 0);
    }

    public function test_type_specific_size_limit_is_enforced(): void
    {
        $this->upload(UploadedFile::fake()->create('huge.png', 11 * 1024, 'image/png'))
            ->assertUnprocessable()
            ->assertJsonPath('errors.file.0', 'Image files must be 10 MB or smaller.');
    }

    public function test_upload_requires_a_file(): void
    {
        $this->actingAsJwt($this->user)->postJson("/api/tasks/{$this->task->id}/attachments", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_infected_file_is_removed_and_cannot_be_downloaded(): void
    {
        $id = $this->upload($this->realUpload('invoice.txt', 'hello LAJUR-SIMULATED-VIRUS'))
            ->assertCreated()
            ->assertJsonPath('data.scan_status', 'infected')
            ->json('data.id');

        Storage::disk('local')->assertMissing(TaskAttachment::find($id)->file_path);
        $this->actingAsJwt($this->user)->getJson("/api/attachments/{$id}/download")->assertStatus(410);
    }

    public function test_download_waits_for_the_scan(): void
    {
        Queue::fake();

        $id = $this->upload(UploadedFile::fake()->image('a.png'))->json('data.id');

        Queue::assertPushed(ProcessAttachment::class);
        $this->actingAsJwt($this->user)->getJson("/api/attachments/{$id}/download")->assertStatus(409);
    }

    public function test_clean_file_downloads_with_original_name(): void
    {
        $id = $this->upload($this->realUpload('Q4 plan.txt', 'plan'))->json('data.id');

        $response = $this->actingAsJwt($this->user)->get("/api/attachments/{$id}/download")->assertOk();

        $this->assertStringContainsString('Q4 plan.txt', $response->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('plan', $response->streamedContent());
    }

    public function test_member_who_cannot_edit_the_task_cannot_upload_or_delete(): void
    {
        $id = $this->upload(UploadedFile::fake()->image('a.png'))->json('data.id');
        $stranger = User::factory()->create();

        $this->actingAsJwt($stranger)->post("/api/tasks/{$this->task->id}/attachments", ['file' => UploadedFile::fake()->image('b.png')])
            ->assertForbidden();
        $this->actingAsJwt($stranger)->deleteJson("/api/attachments/{$id}")->assertForbidden();
    }

    public function test_delete_removes_row_file_and_thumbnail(): void
    {
        $id = $this->upload(UploadedFile::fake()->image('a.png'))->json('data.id');
        $attachment = TaskAttachment::find($id);

        $this->actingAsJwt($this->user)->deleteJson("/api/attachments/{$id}")->assertNoContent();

        $this->assertDatabaseMissing('task_attachments', ['id' => $id]);
        Storage::disk('local')->assertMissing($attachment->file_path);
        Storage::disk('local')->assertMissing($attachment->thumbnail_path);
    }

    public function test_deleting_a_task_removes_its_files(): void
    {
        $id = $this->upload(UploadedFile::fake()->image('a.png'))->json('data.id');
        $path = TaskAttachment::find($id)->file_path;

        $this->actingAsJwt($this->user)->deleteJson("/api/tasks/{$this->task->id}")->assertNoContent();

        Storage::disk('local')->assertMissing($path);
    }
}
