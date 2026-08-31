<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\EntraController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [EntraController::class, 'redirect'])
    ->name('login');

Route::get('/signin-oidc', [EntraController::class, 'callback'])
    ->name('entra.callback');

Route::middleware('entra.auth')->group(function () {
    Route::get('/', [DashboardController::class, 'landing'])->name('landing');
    Route::get('/annual', [DashboardController::class, 'annual'])->name('annual');
    Route::post('/annual/view-preference', [DashboardController::class, 'updateAnnualCalView'])->name('annual.view-preference');
    Route::get('/logout', [AdminController::class, 'logout'])->name('logout');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/projects/{project}/users', [AdminController::class, 'assignUsers'])->name('projects.assign-users');
        Route::delete('/projects/{project}/users/{netid}', [AdminController::class, 'removeUser'])->name('projects.remove-user');
        Route::post('/projects/{project}/mark-remaining-billed', [AdminController::class, 'markProjectRemainingBilled'])->name('projects.mark-remaining-billed');
        Route::post('/projects/{project}/batch-update-shifts', [AdminController::class, 'batchUpdateShifts'])->name('projects.batch-update-shifts');
        Route::get('/projects/{project}', [DashboardController::class, 'viewProjectOverview'])->name('projects.show');
        Route::get('/users/{user:netid}', [DashboardController::class, 'viewUserLanding'])->name('users.dashboard');
        Route::get('/users/{user:netid}/annual', [DashboardController::class, 'viewUserAnnual'])->name('users.annual');
        Route::post('/users/{user:netid}/product', [AdminController::class, 'updateProduct'])->name('users.product');
        Route::post('/projects/{project}/honeycrisp', [AdminController::class, 'updateHoneycrispProject'])->name('projects.honeycrisp');
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
        Route::post('/{shift}/billed', 'updateBilled')->name('update-billed')->middleware('admin');
        Route::put('/{shift}', 'update')->name('update');
        Route::delete('/{shift}', 'destroy')->name('destroy');
    });
});
