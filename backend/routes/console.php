<?php

use App\Models\TaskEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TaskEvent::where('created_at', '<', now()->subHours(config('realtime.event_retention_hours')))->delete();
    DB::table('revoked_tokens')->where('expires_at', '<', now())->delete();
})->hourly()->name('prune-events-and-revoked-tokens');
