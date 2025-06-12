<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AdminController;

Route::get('/', [ProjectController::class, 'landing'])->name('landing');

Route::resource('projects', ProjectController::class)->except(['destroy']);
Route::delete('/projects/{project}', [ProjectController::class, 'delete'])->name('projects.destroy');

Route::controller(ShiftController::class)->prefix('shifts')->name('shifts.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{shift}', 'show')->name('show');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/projects/{project}/unbilled-users', [AdminController::class, 'showProjectUnbilledUsers'])->name('projects.unbilled_users');
});

Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('/login', [LoginController::class, 'submitLogin'])->name('login.submit');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/login/cas', [LoginController::class, 'casLogin'])->name('cas.login');


// // Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
// // Route::get('/shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
// // Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
// // Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');

// // Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
// // Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
// // Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
// // Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
// // Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
// // Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
// // Route::delete('/projects/{project}', [ProjectController::class, 'delete'])->name('projects.destroy');

