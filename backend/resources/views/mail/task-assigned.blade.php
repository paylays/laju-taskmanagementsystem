Hi {{ $assignee->name }},

{{ $task->creator?->name ?? 'Someone' }} assigned you a task in Lajur.

Title:    {{ $task->title }}
Priority: {{ $task->priority }}
Due:      {{ $task->due_date?->format('j M Y') ?? 'no due date' }}

{{ $task->description }}
