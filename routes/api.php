<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\TaskController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/health', static fn () => response()->json([
    'status' => 'ok',
    'app' => config('app.name'),
]));

Route::middleware(['web', 'auth'])->group(function (): void {
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

    Route::prefix('admin')->middleware([
        'role:'.implode(',', [Role::SUPER_ADMIN, Role::ADMIN, Role::OPERATOR, Role::OBSERVER]),
    ])->group(function (): void {
        Route::get('/summary', [AdminController::class, 'summary'])->name('api.admin.summary');
        Route::get('/agents', [AdminController::class, 'agents'])->name('api.admin.agents');
        Route::get('/tasks', [AdminController::class, 'tasks'])->name('api.admin.tasks');
        Route::get('/executions', [AdminController::class, 'executions'])->name('api.admin.executions');
        Route::get('/audit-events', [AdminController::class, 'auditEvents'])->name('api.admin.audit-events');
    });
});
