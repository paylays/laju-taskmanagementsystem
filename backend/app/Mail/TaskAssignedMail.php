<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TaskAssignedMail extends Mailable
{
    public function __construct(public Task $task, public User $assignee) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Assigned to you: {$this->task->title}");
    }

    public function content(): Content
    {
        return new Content(text: 'mail.task-assigned');
    }
}
