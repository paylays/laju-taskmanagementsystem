<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use App\Observers\TaskAttachmentObserver;
use App\Observers\TaskCommentObserver;
use App\Observers\TaskObserver;
use App\Services\ApiCache;
use App\Services\JwtService;
use Illuminate\Database\Eloquent\Model;
use App\Services\ThumbnailGenerator;
use App\Services\VirusScanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JwtService::class, fn () => new JwtService(
            (string) config('jwt.secret'),
            (int) config('jwt.ttl'),
            (string) config('jwt.issuer'),
        ));

        $this->app->bind(ThumbnailGenerator::class, fn () => new ThumbnailGenerator(
            (int) config('attachments.thumbnail_size'),
        ));

        $this->app->bind(VirusScanner::class, fn () => new VirusScanner([
            VirusScanner::EICAR,
            (string) config('attachments.scan_test_marker'),
        ]));
    }

    public function boot(): void
    {
        Auth::viaRequest('jwt', function (Request $request) {
            $token = $request->bearerToken();

            // EventSource cannot send headers, so the stream route alone accepts ?token=.
            if (! $token && $request->routeIs('stream')) {
                $token = $request->query('token');
            }

            if (! is_string($token) || $token === '') {
                return null;
            }

            $claims = app(JwtService::class)->decode($token);
            if (! $claims) {
                return null;
            }

            $request->attributes->set('jwt_claims', $claims);

            return User::find($claims->sub);
        });

        // Turns an accidental N+1 query into an exception everywhere except production.
        Model::preventLazyLoading(! $this->app->isProduction());

        User::saved(fn () => app(ApiCache::class)->bump(ApiCache::USERS));
        User::deleted(fn () => app(ApiCache::class)->bump(ApiCache::USERS));

        Task::observe(TaskObserver::class);
        TaskComment::observe(TaskCommentObserver::class);
        TaskAttachment::observe(TaskAttachmentObserver::class);
    }
}
