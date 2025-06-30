<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AdminController;

Route::get('/', [ProjectController::class, 'landing'])->name('landing');

// Route::resource('projects', ProjectController::class)->except(['destroy']);
// Route::delete('/projects/{project}', [ProjectController::class, 'delete'])->name('projects.destroy');

// Route::controller(ShiftController::class)->prefix('shifts')->name('shifts.')->group(function () {
//     Route::get('/', 'index')->name('index');
//     Route::get('/create', 'create')->name('create');
//     Route::post('/', 'store')->name('store');
//     Route::get('/{shift}', 'show')->name('show');
// });

// Route::prefix('admin')->name('admin.')->group(function () {
//     Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
//     Route::get('/projects/{project}/unbilled-users', [AdminController::class, 'showProjectUnbilledUsers'])->name('projects.unbilled_users');
// });




Route::middleware(['auth'])->group(function () {
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

    Route::controller(ShiftController::class)->prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/', 'index')->name('index'); 
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store'); 
        Route::get('/{shift}', 'show')->name('show');
    });
});

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::delete('/projects/{project}', [ProjectController::class, 'delete'])->name('projects.destroy')->middleware(['auth']);

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/projects/{project}/unbilled-users', [AdminController::class, 'showProjectUnbilledUsers'])->name('projects.unbilled_users');
});

Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');