<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:api')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    Route::get('users', [UserController::class, 'index']);

    Route::post('tasks/bulk-status', [TaskController::class, 'bulkStatus']);
    Route::apiResource('tasks', TaskController::class);

    Route::get('tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('tasks/{task}/comments', [CommentController::class, 'store']);

    Route::post('tasks/{task}/attachments', [AttachmentController::class, 'store']);
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download']);
    Route::get('attachments/{attachment}/thumbnail', [AttachmentController::class, 'thumbnail']);
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);

    Route::post('exports', [ExportController::class, 'store']);
    Route::get('exports/{export}', [ExportController::class, 'show']);
    Route::get('exports/{export}/download', [ExportController::class, 'download']);

    Route::get('stream', StreamController::class)->name('stream');
});
