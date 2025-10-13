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
        // Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        // Route::get('/projects/{project}/unbilled-users', [AdminController::class, 'showProjectUnbilledUsers'])->name('projects.unbilled_users');
        // Route::put('/shifts/{shift}/mark-billed', [AdminController::class, 'markShiftBilled'])->name('shifts.mark-billed');

        Route::get('/projects/{project}/users', [AdminController::class, 'showProjectUsers'])->name('projects.users');
        Route::post('/projects/{project}/users', [AdminController::class, 'assignUsers'])->name('projects.assign-users');
        Route::delete('/projects/{project}/users/{netid}', [AdminController::class, 'removeUser'])->name('projects.remove-user');

        Route::get('/projects/{project}/manage', [AdminController::class, 'manageProject'])->name('projects.manage');
        Route::get('/shifts', [AdminController::class, 'viewAllShifts'])->name('shifts.index');
        
        Route::get('/users', [AdminController::class, 'viewAllUsers'])->name('users.index');
        Route::post('/users/{user}/toggle-admin', [AdminController::class, 'toggleAdmin'])->name('users.toggle-admin');
    });


    Route::controller(ProjectController::class)->prefix('projects')->name('projects.')->group(function () {
        Route::get('/create', 'create')->name('create')->middleware('admin');
        Route::get('/', 'index')->name('index');
        Route::get('/{project}', 'show')->name('show');

        Route::post('/', 'store')->name('store');
        Route::put('/{project}', 'update')->name('update');
        Route::delete('/{project}', 'destroy')->name('destroy');
    });
        // Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');

        
    Route::controller(ShiftController::class)->prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/', 'index')->name('index'); 
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store'); 
        // Route::get('/{shift}', 'show')->name('show');
        Route::get('/{shift}/edit', 'edit')->name('edit');
        Route::put('/{shift}', 'update')->name('update');
        Route::delete('/{shift}', 'destroy')->name('destroy');
    });
});

Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
// Route::get('/users/show', [UserController::class, 'show'])->name('users.show');