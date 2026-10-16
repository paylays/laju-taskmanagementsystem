<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function index(Task $task): AnonymousResourceCollection
    {
        return CommentResource::collection(
            $task->comments()->with('user:id,name')->oldest()->oldest('id')->get()
        );
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => trim($data['comment']),
        ]);

        return (new CommentResource($comment->load('user:id,name')))->response()->setStatusCode(201);
    }
}
