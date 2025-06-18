<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\RevenueHistoryController;
use App\Http\Controllers\AuthController;

// Auth Routes //
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.admin');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth.admin');

// Booking Routes
Route::get('/getAvailableDates', [BookingController::class, 'getAvailableDates']);
Route::post('/booking', [BookingController::class, 'store']);
Route::get('/checkDateAvailability', [BookingController::class, 'checkDateAvailability']);

// Protected Admin Routes
Route::middleware('auth.admin')->group(function () {
    // Appointment Routes (admin)
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
    Route::put('/appointments/{id}/reschedule', [AppointmentController::class, 'reschedule']);
    Route::post('/appointments/{id}/accept', [AppointmentController::class, 'accept']);
    Route::post('/appointments/{id}/complete', [AppointmentController::class, 'complete']);

    // Technician management routes
    Route::get('/appointments/technicians', [AppointmentController::class, 'getTechnicians']);
    Route::post('/appointments/{id}/assign-technician', [AppointmentController::class, 'assignTechnician']);
    Route::post('/appointments/{id}/remove-technician', [AppointmentController::class, 'removeTechnician']);

    // Revenue History Routes
    Route::get('/revenue-history', [RevenueHistoryController::class, 'index']);
    Route::post('/revenue-history', [RevenueHistoryController::class, 'store']);
    Route::get('/revenue-history/service-summary', [RevenueHistoryController::class, 'getServiceRevenueSummary']);
});
