<?php

use Illuminate\Support\Facades\Route;
use App\Models\Shift;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;

Route::middleware('cas.auth')->group(function () {
    Route::get('/', [AdminController::class, 'landing'])->name('landing');
    Route::get('/logout', [AdminController::class, 'logout'])->name('logout');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/projects/{project}/unbilled-users', [AdminController::class, 'showProjectUnbilledUsers'])->name('projects.unbilled_users');
        Route::put('/shifts/{shift}/mark-billed', [AdminController::class, 'markShiftBilled'])->name('shifts.mark-billed');

        Route::get('/projects/{project}/users', [AdminController::class, 'showProjectUsers'])->name('projects.users');
        Route::post('/projects/{project}/users', [AdminController::class, 'assignUsers'])->name('projects.assign-users');
        Route::delete('/projects/{project}/users/{netid}', [AdminController::class, 'removeUser'])->name('projects.remove-user');
    });

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');

    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'delete'])->name('projects.destroy');


    Route::controller(ShiftController::class)->prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/', 'index')->name('index'); 
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store'); 
        Route::get('/{shift}', 'show')->name('show');
    });
});

Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::get('/users/show', [UserController::class, 'show'])->name('users.show');