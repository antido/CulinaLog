<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ForumCategoryController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
 * Admin area. Every route needs a signed-in, verified user AND a permission.
 * The permission middleware is the first check; controllers and form
 * requests check the matching policy or permission again before changing anything.
 */
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)
        ->middleware('permission:dashboard.admin')
        ->name('dashboard');

    Route::get('users', [UserController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('users.index');

    Route::middleware('permission:users.manage')->group(function () {
        Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    Route::get('permissions', PermissionController::class)
        ->middleware('permission:permissions.view')
        ->name('permissions.index');

    Route::get('recipes', [RecipeController::class, 'index'])
        ->middleware('permission:recipes.manage')
        ->name('recipes.index');

    Route::resource('categories', CategoryController::class)
        ->except('show')
        ->middleware('permission:categories.manage');

    Route::resource('forum-categories', ForumCategoryController::class)
        ->except('show')
        ->middleware('permission:forum.manage');
});
