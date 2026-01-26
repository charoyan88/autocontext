<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/docs', function () {
    return view('docs');
})->name('docs');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/project/{project}', [DashboardController::class, 'project'])->name('dashboard.project');
    Route::get('/projects/{project}/dashboard', [DashboardController::class, 'project'])->name('projects.dashboard');

    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/api-keys', [App\Http\Controllers\Web\ApiKeyController::class, 'store'])->name('projects.api-keys.store');
    Route::put('/projects/{project}/api-keys/{apiKey}', [App\Http\Controllers\Web\ApiKeyController::class, 'update'])->name('projects.api-keys.update');
    Route::delete('/projects/{project}/api-keys/{apiKey}', [App\Http\Controllers\Web\ApiKeyController::class, 'destroy'])->name('projects.api-keys.destroy');
    Route::put('/projects/{project}/downstream', [App\Http\Controllers\Web\DownstreamEndpointController::class, 'update'])->name('projects.downstream.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    });
});

require __DIR__ . '/auth.php';
