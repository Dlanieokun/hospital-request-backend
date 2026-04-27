<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request; // Added this import
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\DashboardController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/request', [SettingController::class, 'requestList']);
Route::get('/patient_date/{id}', [ReceiptController::class, 'getDate']);
Route::post('/receipt_store', [ReceiptController::class, 'storeReceipt']);
Route::get('/receipts/{id}', [ReceiptController::class, 'getReceipt']);
Route::post('/online-ref/{id}', [ReceiptController::class, 'setReceipt']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sync/{id}', [SettingController::class, 'dataMapping']);

    Route::get('/user', function (Request $request) {
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
    Route::get('/certificates', [ReceiptController::class, 'AllReceipt']);
    Route::patch('/receipts/{id}', [ReceiptController::class, 'receiptRelease']);
    Route::get('/dashboard', [DashboardController::class, 'overview']);
    Route::get('/online-list', [ReceiptController::class, 'onlineReceipt']);
    Route::post('/online-paid', [ReceiptController::class, 'onlinePaid']);

    Route::post('/logout', [AuthController::class, 'logout']);
});