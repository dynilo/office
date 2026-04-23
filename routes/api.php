<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/health', static fn () => response()->json([
    'status' => 'ok',
    'app' => config('app.name'),
]));

Route::prefix('agents')->group(function (): void {
    Route::get('/', [AgentController::class, 'index']);
    Route::post('/', [AgentController::class, 'store']);
    Route::get('/{agent}', [AgentController::class, 'show']);
    Route::patch('/{agent}', [AgentController::class, 'update']);
    Route::patch('/{agent}/activate', [AgentController::class, 'activate']);
    Route::patch('/{agent}/deactivate', [AgentController::class, 'deactivate']);
});

Route::prefix('tasks')->group(function (): void {
    Route::get('/', [TaskController::class, 'index']);
    Route::post('/', [TaskController::class, 'store']);
    Route::get('/{task}', [TaskController::class, 'show']);
});

Route::prefix('documents')->group(function (): void {
    Route::post('/ingest', [DocumentController::class, 'store']);
    Route::post('/{document}/extract-knowledge', [DocumentController::class, 'extractKnowledge']);
});
