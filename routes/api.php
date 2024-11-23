<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public Routes
Route::post('/signup', [AuthController::class, 'signUp']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // User Profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/editMyProfile', [UserController::class, 'editMyProfile']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Role-Based Access: User
    Route::middleware(['role:user,admin'])->group(function () {
        // Category Management for Users (Example: View-only access)
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);

         // Product Management for Users (Example: View-only access)
         Route::get('/products', [ProductController::class, 'index']);
         Route::get('/products/{product}', [ProductController::class, 'show']);
    });

    // Role-Based Access: Admin
    Route::middleware(['role:admin'])->group(function () {
        // Full CRUD Access for Admins
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Full CRUD Access for Admins
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    });
});
