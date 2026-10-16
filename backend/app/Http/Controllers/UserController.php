<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ApiCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request, ApiCache $cache): JsonResponse
    {
        $body = $cache->remember(ApiCache::USERS, [], 3600, fn () => UserResource::collection(
            User::orderBy('name')->get()
        )->toResponse($request)->getData(true));

        return response()->json($body);
    }
}
