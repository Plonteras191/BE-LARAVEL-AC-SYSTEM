<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\BookingActype;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    // Get available dates (mock data for now)
    public function getAvailableDates(Request $request)
    {
        // For simplicity, let's return all dates in 2025 as available
        $start = $request->input('start', '2025-01-01');
        $end = $request->input('end', '2025-12-31');

        $startDate = \Carbon\Carbon::parse($start);
        $endDate = \Carbon\Carbon::parse($end);

        $dates = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        return response()->json($dates);
    }

    // Create a new booking
    public function store(Request $request)
    {
        // Start a database transaction
        DB::beginTransaction();

        try {
            // Create the main booking record
            $booking = new Booking();
            $booking->name = $request->input('name');
            $booking->phone = $request->input('phone');
            $booking->email = $request->input('email');
            $booking->complete_address = $request->input('completeAddress');
            $booking->status = 'Pending';
            $booking->save();

            // Process each service
            foreach ($request->input('services') as $service) {
                // Create booking service record
                $bookingService = new BookingService();
                $bookingService->booking_id = $booking->id;
                $bookingService->service_type = $service['type'];
                $bookingService->appointment_date = $service['date'];
                $bookingService->save();

                // Create AC type records
                foreach ($service['acTypes'] as $acType) {
                    $bookingAcType = new BookingActype();
                    $bookingAcType->booking_id = $booking->id;
                    $bookingAcType->ac_type = $acType;
                    $bookingAcType->save();
                }
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'bookingId' => $booking->id,
                'message' => 'Booking created successfully'
            ]);

        } catch (\Exception $e) {
            // Roll back the transaction if something goes wrong
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error creating booking: ' . $e->getMessage()
            ], 500);
        }
    }
}
