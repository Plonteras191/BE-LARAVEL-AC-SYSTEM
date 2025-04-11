<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\RevenueHistoryController;

// Booking Routes
Route::get('/getAvailableDates', [BookingController::class, 'getAvailableDates']);
Route::post('/booking', [BookingController::class, 'store']);
// New route for checking specific dates
Route::get('/checkDateAvailability', [BookingController::class, 'checkDateAvailability']);

// Appointment Routes (admin)
Route::get('/appointments', [AppointmentController::class, 'index']);
Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
Route::put('/appointments/{id}', [AppointmentController::class, 'reschedule'])->where('action', 'reschedule');
Route::post('/appointments/{id}/accept', [AppointmentController::class, 'accept']);
Route::post('/appointments/{id}/complete', [AppointmentController::class, 'complete']);

// Revenue History Routes
Route::get('/revenue-history', [RevenueHistoryController::class, 'index']);
Route::post('/revenue-history', [RevenueHistoryController::class, 'store']);
