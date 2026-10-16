<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Response cache keyed by a per-scope version instead of cache tags, so it works on any store.
 * Bumping a version orphans that scope's old entries; they expire on their own TTL.
 */
class ApiCache
{
    public const TASKS = 'tasks';
    public const USERS = 'users';

    public function remember(string $scope, array $params, int $ttlSeconds, Closure $build): mixed
    {
        ksort($params);
        $key = "api:{$scope}:v{$this->version($scope)}:".md5(json_encode($params));

        return Cache::remember($key, $ttlSeconds, $build);
    }

    public function bump(string $scope): void
    {
        $key = "api:{$scope}:version";
        Cache::add($key, 1);
        Cache::increment($key);
    }

    private function version(string $scope): int
    {
        return (int) Cache::get("api:{$scope}:version", 1);
    }
}
