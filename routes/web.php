<?php

use App\Http\Controllers\Web\AdminShellController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->group(function (): void {
    Route::get('/', [AdminShellController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/agents', [AdminShellController::class, 'agents'])->name('admin.agents');
    Route::get('/tasks', [AdminShellController::class, 'tasks'])->name('admin.tasks');
    Route::get('/executions', [AdminShellController::class, 'executions'])->name('admin.executions');
    Route::get('/audit', [AdminShellController::class, 'audit'])->name('admin.audit');
});
