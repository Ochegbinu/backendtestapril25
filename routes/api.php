<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
    });
    
    Route::middleware('role:Admin')->group(function () {
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
        
        Route::get('/users', [UserController::class, 'index']);

        Route::post('/users', [UserController::class, 'store']);
        
    });
});