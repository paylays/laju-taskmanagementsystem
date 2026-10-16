<?php

namespace App\Http\Controllers;

use App\Models\TaskEvent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSE feed of task_events. Each response closes after realtime.hold_seconds; EventSource
 * reconnects by itself and resumes from Last-Event-ID, so closing early loses nothing.
 */
class StreamController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        // "0" is a valid resume point (the feed was empty), so test for presence, not truthiness.
        $resumeFrom = $request->header('Last-Event-ID') ?? $request->query('last_event_id');
        $lastId = $resumeFrom === null ? null : (int) $resumeFrom;
        $hold = (int) config('realtime.hold_seconds');
        $retry = (int) config('realtime.retry_ms');

        return response()->stream(function () use ($lastId, $hold, $retry) {
            $this->send("retry: {$retry}\n\n");

            // A fresh client only wants changes from now on, not the whole history.
            if ($lastId === null) {
                $lastId = (int) TaskEvent::max('id');
                $this->send("id: {$lastId}\nevent: ready\ndata: {}\n\n");
            }

            $deadline = time() + $hold;

            while (true) {
                $events = TaskEvent::where('id', '>', $lastId)->orderBy('id')->limit(200)->get();

                foreach ($events as $event) {
                    $this->send("id: {$event->id}\nevent: {$event->type}\ndata: ".json_encode($event->payload)."\n\n");
                    $lastId = $event->id;
                }

                if (time() >= $deadline || connection_aborted()) {
                    break;
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function send(string $chunk): void
    {
        echo $chunk;

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
