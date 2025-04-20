<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\BookingActype;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    // Get available dates with proper service limit check
    public function getAvailableDates(Request $request)
    {
        $start = $request->input('start', '2025-01-01');
        $end = $request->input('end', '2025-12-31');
        $global = $request->input('global', 0);

        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        // Generate all dates in the range
        $allDates = [];
        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
            $allDates[] = $date->format('Y-m-d');
        }

        // Get dates with service counts
        $bookedDates = DB::table('booking_services')
            ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', ['Pending', 'Accepted']) // Only count pending and accepted bookings
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->select('appointment_date', DB::raw('count(*) as service_count'))
            ->groupBy('appointment_date')
            ->get();

        // Create a lookup array with date => count
        $dateCountMap = [];
        foreach ($bookedDates as $bookedDate) {
            $dateCountMap[$bookedDate->appointment_date] = $bookedDate->service_count;
        }

        // Filter dates where service count < 2 (our limit)
        $availableDates = [];
        foreach ($allDates as $date) {
            if (!isset($dateCountMap[$date]) || $dateCountMap[$date] < 2) {
                $availableDates[] = $date;
            }
        }

        return response()->json($availableDates);
    }

    public function checkDateAvailability(Request $request)
    {
        $dates = $request->input('dates', []);

        if (empty($dates)) {
            return response()->json([
                'error' => 'No dates provided for checking'
            ], 400);
        }

        $result = [];

        foreach ($dates as $date) {
            // Get count of services on this date
            $count = BookingService::whereDate('appointment_date', $date)
                ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
                ->whereIn('bookings.status', ['Pending', 'Accepted'])
                ->count();

            $result[$date] = [
                'available' => ($count < 2),
                'remaining_slots' => 2 - $count
            ];
        }

        return response()->json([
            'dates' => $result
        ]);
    }

    // Create a new booking with date validation
    public function store(Request $request)
    {
        // Start a database transaction
        DB::beginTransaction();

        try {
            // Validate dates before creating booking
            $serviceDates = array_map(function($service) {
                return $service['date'];
            }, $request->input('services'));

            // Check if any of the requested dates exceed the limit
            foreach ($serviceDates as $date) {
                $existingCount = BookingService::whereDate('appointment_date', $date)
                    ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
                    ->whereIn('bookings.status', ['Pending', 'Accepted'])
                    ->count();

                if ($existingCount >= 2) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => "Date $date is no longer available. Please select another date."
                    ], 400);
                }
            }

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

                // Create AC type records linked to the booking service
                foreach ($service['acTypes'] as $acType) {
                    $bookingAcType = new BookingActype();
                    $bookingAcType->booking_service_id = $bookingService->id; // Updated to use booking_service_id
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
