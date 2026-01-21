<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request; // Added this import
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ReceiptController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        // This returns the authenticated user's data
//        return response()->json($request->user(), 200);
        return response()->json("User", 200);
    });
    Route::apiResource('settings', SettingController::class);
    Route::post('/settings/certificates', [SettingController::class, 'store']);
    Route::post('/settings/staff', [AuthController::class, 'register']);
    Route::delete('/settings/staff/{id}', [AuthController::class, 'delete']);
    Route::put('/settings/staff/{id}', [AuthController::class, 'update']);
    Route::put('/settings/certificates/{id}', [SettingController::class, 'update']);
    Route::delete('/settings/certificates/{id}', [SettingController::class, 'destroy']);
    Route::get('/receipts', [ReceiptController::class, 'index']);
    Route::put('/receipts/{id}', [ReceiptController::class, 'paid']);


    Route::post('/logout', [AuthController::class, 'logout']);
});