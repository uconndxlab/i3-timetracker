<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('cas.auth')->group(function () {
    Route::get('/', [DashboardController::class, 'landing'])->name('landing');
    Route::get('/logout', [AdminController::class, 'logout'])->name('logout');

    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/projects/{project}/users', [AdminController::class, 'assignUsers'])->name('projects.assign-users');
        Route::delete('/projects/{project}/users/{netid}', [AdminController::class, 'removeUser'])->name('projects.remove-user');
        Route::post('/projects/{project}/mark-remaining-billed', [AdminController::class, 'markProjectRemainingBilled'])->name('projects.mark-remaining-billed');
        Route::post('/projects/{project}/batch-update-shifts', [AdminController::class, 'batchUpdateShifts'])->name('projects.batch-update-shifts');
        Route::get('/projects/{project}', [DashboardController::class, 'viewProjectOverview'])->name('projects.show');
        Route::get('/users/{user:netid}', [DashboardController::class, 'viewUserLanding'])->name('users.dashboard');
        Route::post('/users/{user}/toggle-admin', [AdminController::class, 'toggleAdmin'])->name('users.toggle-admin');
    });

    Route::controller(ProjectController::class)->prefix('projects')->name('projects.')->group(function () {
        Route::post('/', 'store')->name('store')->middleware('admin');
        Route::post('/sync-memberships', 'syncMemberships')->name('sync-memberships');
        Route::post('/{project}/join', 'join')->name('join');
        Route::delete('/{project}/leave', 'leave')->name('leave');
        Route::put('/{project}', 'update')->name('update')->middleware('admin');
    });

    Route::controller(ShiftController::class)->prefix('shifts')->name('shifts.')->group(function () {
        Route::post('/', 'store')->name('store');
        Route::post('/bulk-entered', 'bulkUpdateEntered')->name('bulk-update-entered');
        Route::post('/{shift}/entered', 'updateEntered')->name('update-entered');
        Route::put('/{shift}', 'update')->name('update');
        Route::delete('/{shift}', 'destroy')->name('destroy');
    });
});
