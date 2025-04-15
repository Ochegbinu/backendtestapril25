<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// protected function schedule(Schedule $schedule)
// {
//     $schedule->job(new SendWeeklyExpenseReport)->weekly()->mondays()->at('8:00');
// }

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Expense routes
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    
    // Routes requiring Manager or Admin role
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
    });
    
    // Routes requiring Admin role
    Route::middleware('role:Admin')->group(function () {
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
        
        // User management routes
        Route::get('/users', [UserController::class, 'index']);

        // Create a new user (POST /users)
        Route::post('/users', [UserController::class, 'store']);
        
    });
});