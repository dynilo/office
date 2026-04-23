<?php

use App\Http\Controllers\Web\AdminShellController;
use App\Http\Controllers\Web\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::prefix('admin')->middleware('auth')->group(function (): void {
    Route::get('/', [AdminShellController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/agents', [AdminShellController::class, 'agents'])->name('admin.agents');
    Route::get('/tasks', [AdminShellController::class, 'tasks'])->name('admin.tasks');
    Route::get('/executions', [AdminShellController::class, 'executions'])->name('admin.executions');
    Route::get('/audit', [AdminShellController::class, 'audit'])->name('admin.audit');
});
