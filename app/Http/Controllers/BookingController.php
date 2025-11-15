<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\BookingService;
use App\Models\BookingActype;
use App\Models\AcType;
use App\Models\BookingStatus;
use App\Models\Technician;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AppointmentConfirmation;
use App\Mail\AppointmentRejection;
use Carbon\Carbon;

class BookingController extends Controller
{
    // Define booking limit as a constant for easy management
    const DAILY_BOOKING_LIMIT = 5;

    // Get available dates with proper booking limit check (5 customers per date)
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

        // Get dates with DISTINCT booking counts (unique customers per date)
        $bookedDates = DB::table('booking_services')
            ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
            ->join('booking_statuses', 'bookings.status_id', '=', 'booking_statuses.id')
            ->whereIn('booking_statuses.status_name', ['Pending', 'Accepted'])
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->select('appointment_date', DB::raw('COUNT(DISTINCT bookings.id) as booking_count'))
            ->groupBy('appointment_date')
            ->get();

        // Create a lookup array with date => count
        $dateCountMap = [];
        foreach ($bookedDates as $bookedDate) {
            // Normalize the row to an array to avoid type issues with analyzers or different result shapes
            $row = is_array($bookedDate) ? $bookedDate : (array) $bookedDate;
            $appointmentDate = $row['appointment_date'] ?? null;
            $bookingCount = isset($row['booking_count']) ? (int) $row['booking_count'] : 0;
            if ($appointmentDate !== null) {
                $dateCountMap[$appointmentDate] = $bookingCount;
            }
        }

        // Filter dates where booking count < 5 (our limit for unique customers)
        $availableDates = [];
        foreach ($allDates as $date) {
            if (!isset($dateCountMap[$date]) || $dateCountMap[$date] < self::DAILY_BOOKING_LIMIT) {
                $availableDates[] = $date;
            }
        }

        return response()->json([
            'success' => true,
            'available_dates' => $availableDates
        ]);
    }

    public function checkDateAvailability(Request $request)
    {
        $dates = $request->input('dates', []);

        if (empty($dates)) {
            return response()->json([
                'success' => false,
                'message' => 'No dates provided for checking'
            ], 400);
        }

        $result = [];

        foreach ($dates as $date) {
            // Get count of DISTINCT bookings on this date (unique customers)
            $count = DB::table('booking_services')
                ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
                ->join('booking_statuses', 'bookings.status_id', '=', 'booking_statuses.id')
                ->whereIn('booking_statuses.status_name', ['Pending', 'Accepted'])
                ->whereDate('appointment_date', $date)
                ->distinct('bookings.id')
                ->count('bookings.id');

            $result[$date] = [
                'available' => ($count < self::DAILY_BOOKING_LIMIT),
                'remaining_slots' => self::DAILY_BOOKING_LIMIT - $count
            ];
        }

        return response()->json([
            'success' => true,
            'dates' => $result
        ]);
    }

    // Create a new booking with date validation
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'completeAddress' => 'required|string',
            'services' => 'required|array|min:1',
            'services.*.type' => 'required|string|max:50',
            'services.*.date' => 'required|date',
            'services.*.acTypes' => 'required|array|min:1'
        ]);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Get unique dates from services (in case multiple services on same date)
            $serviceDates = array_unique(array_map(function($service) {
                return $service['date'];
            }, $request->input('services')));

            // Check if any of the requested dates exceed the booking limit (5 customers per date)
            foreach ($serviceDates as $date) {
                $existingBookingCount = DB::table('booking_services')
                    ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
                    ->join('booking_statuses', 'bookings.status_id', '=', 'booking_statuses.id')
                    ->whereIn('booking_statuses.status_name', ['Pending', 'Accepted'])
                    ->whereDate('appointment_date', $date)
                    ->distinct('bookings.id')
                    ->count('bookings.id');

                if ($existingBookingCount >= self::DAILY_BOOKING_LIMIT) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => "Date $date is no longer available. Please select another date."
                    ], 400);
                }
            }

            // Always create a new customer record to ensure correct name display
            // Or find existing customer with exact name and phone match
            $existingCustomer = Customer::where('phone', $request->input('phone'))
                                      ->where('name', $request->input('name'))
                                      ->first();

            if ($existingCustomer) {
                // Update existing customer's information if found
                $existingCustomer->update([
                    'email' => $request->input('email'),
                    'complete_address' => $request->input('completeAddress')
                ]);
                $customer = $existingCustomer;
            } else {
                // Create new customer record
                $customer = Customer::create([
                    'name' => $request->input('name'),
                    'phone' => $request->input('phone'),
                    'email' => $request->input('email'),
                    'complete_address' => $request->input('completeAddress')
                ]);
            }

            // Get the "Pending" status (assuming it exists with id 1, or create it)
            $pendingStatus = BookingStatus::firstOrCreate([
                'status_name' => 'Pending'
            ]);

            // Create the main booking record
            $booking = new Booking();
            $booking->customer_id = $customer->id;
            $booking->status_id = $pendingStatus->id;
            $booking->save();

            // Process each service
            foreach ($request->input('services') as $service) {
                // Create booking service record
                $bookingService = new BookingService();
                $bookingService->booking_id = $booking->id;
                $bookingService->service_type = $service['type'];
                $bookingService->appointment_date = $service['date'];
                $bookingService->save();

                // Process AC types for this service
                foreach ($service['acTypes'] as $acTypeName) {
                    // Find or create the AC type
                    $acType = AcType::firstOrCreate([
                        'type_name' => $acTypeName
                    ]);

                    // Create the booking AC type relationship
                    $bookingAcType = new BookingActype();
                    $bookingAcType->booking_service_id = $bookingService->id;
                    $bookingAcType->ac_type_id = $acType->id;
                    $bookingAcType->save();
                }
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'bookingId' => $booking->id,
                'customerId' => $customer->id,
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

    // Get booking details with all relationships
    public function show($id)
    {
        try {
            $booking = Booking::with([
                'customer',
                'status',
                'services.acTypes',
                'technicians',
                'revenue'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }
    }

    // Update booking status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        try {
            $booking = Booking::findOrFail($id);

            $status = BookingStatus::where('status_name', $request->input('status'))->first();

            if (!$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status'
                ], 400);
            }

            $booking->status_id = $status->id;
            $booking->save();

            return response()->json([
                'success' => true,
                'message' => 'Booking status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating booking status: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get all bookings with filters
    public function index(Request $request)
    {
        $query = Booking::with(['customer', 'status', 'services.acTypes']);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->whereHas('status', function($q) use ($request) {
                $q->where('status_name', $request->input('status'));
            });
        }

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereHas('services', function($q) use ($request) {
                $q->whereBetween('appointment_date', [
                    $request->input('start_date'),
                    $request->input('end_date')
                ]);
            });
        }

        // Filter by customer if provided
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'bookings' => $bookings
        ]);
    }

    // Helper method to get bookings by date for debugging
    public function getBookingsByDate(Request $request)
    {
        $date = $request->input('date');

        if (!$date) {
            return response()->json([
                'success' => false,
                'message' => 'Date parameter is required'
            ], 400);
        }

        $bookings = DB::table('booking_services')
            ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
            ->join('customers', 'bookings.customer_id', '=', 'customers.id')
            ->join('booking_statuses', 'bookings.status_id', '=', 'booking_statuses.id')
            ->whereDate('appointment_date', $date)
            ->whereIn('booking_statuses.status_name', ['Pending', 'Accepted'])
            ->select(
                'customers.name',
                'customers.phone',
                'booking_services.service_type',
                'booking_services.appointment_date',
                'booking_statuses.status_name',
                'bookings.id as booking_id'
            )
            ->get();

        $uniqueBookings = $bookings->groupBy('booking_id')->count();

        return response()->json([
            'success' => true,
            'date' => $date,
            'total_bookings' => $uniqueBookings,
            'total_services' => $bookings->count(),
            'available_slots' => self::DAILY_BOOKING_LIMIT - $uniqueBookings,
            'bookings' => $bookings
        ]);
    }

    /**
     * Accept an appointment
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function accept(Request $request, $id): JsonResponse
    {
        try {
            $booking = Booking::with(['customer', 'services'])->findOrFail($id);

            // Before accepting, recheck date availability to prevent conflicts
            $acceptedStatus = BookingStatus::whereIn('status_name', ['Pending', 'Accepted'])->pluck('id');

            foreach ($booking->services as $service) {
                $date = $service->appointment_date;

                // Count existing bookings on this date (excluding this booking) - count unique customers
                $existingCount = DB::table('booking_services')
                    ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
                    ->whereIn('bookings.status_id', $acceptedStatus)
                    ->whereDate('appointment_date', $date)
                    ->where('booking_services.booking_id', '!=', $id)
                    ->distinct('bookings.id')
                    ->count('bookings.id');

                if ($existingCount >= self::DAILY_BOOKING_LIMIT) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot accept booking. Date $date now exceeds booking limit."
                    ], 400);
                }
            }

            // Get the accepted status ID
            $acceptedStatusRecord = BookingStatus::where('status_name', 'Accepted')->first();
            if (!$acceptedStatusRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accepted status not found in database'
                ], 500);
            }

            $booking->status_id = $acceptedStatusRecord->id;
            $booking->save();

            // Handle technician assignment if provided
            $technicianNames = $request->input('technician_names', []);
            if (!empty($technicianNames)) {
                $this->assignTechniciansToBooking($booking, $technicianNames);
            }

            // Send confirmation email
            try {
                // Prepare data for email
                $appointmentData = $this->prepareAppointmentDataForEmail($booking);

                // Send email to customer
                if ($booking->customer->email) {
                    Mail::to($booking->customer->email)->send(new AppointmentConfirmation($appointmentData));
                }

            } catch (\Exception $emailError) {
                Log::error('Failed to send confirmation email: ' . $emailError->getMessage());
            }

            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'status' => $acceptedStatusRecord->status_name,
                    'status_id' => $booking->status_id,
                    'name' => $booking->customer->name,
                    'email' => $booking->customer->email,
                    'technicians' => $booking->technicians->pluck('name')->toArray()
                ],
                'message' => 'Appointment accepted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error accepting appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete (reject) an appointment
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $booking = Booking::with(['customer', 'services.acTypes'])->findOrFail($id);

            // Prepare data for email before changing status
            $appointmentData = $this->prepareAppointmentDataForEmail($booking);

            // Get the rejected status ID (assuming you have a 'Rejected' status)
            $rejectedStatus = BookingStatus::where('status_name', 'Rejected')->first();
            if (!$rejectedStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rejected status not found in database'
                ], 500);
            }

            // Update status to rejected
            $booking->status_id = $rejectedStatus->id;
            $booking->save();

            // Send rejection email
            try {
                if ($booking->customer->email) {
                    Mail::to($booking->customer->email)->send(new AppointmentRejection($appointmentData));
                }
            } catch (\Exception $emailError) {
                // Log the error but don't fail the request
                Log::error('Failed to send rejection email: ' . $emailError->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Appointment rejected successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reschedule a service
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function reschedule(Request $request, $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            $serviceName = $request->input('service_name');
            $newDate = $request->input('new_date');

            // Check if the new date doesn't exceed our limit (5 customers per day)
            $acceptedStatus = BookingStatus::whereIn('status_name', ['Pending', 'Accepted'])->pluck('id');

            $existingCount = DB::table('booking_services')
                ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
                ->whereIn('bookings.status_id', $acceptedStatus)
                ->whereDate('appointment_date', $newDate)
                ->where('booking_services.booking_id', '!=', $id) // Exclude current booking
                ->distinct('bookings.id')
                ->count('bookings.id');

            if ($existingCount >= self::DAILY_BOOKING_LIMIT) {
                return response()->json([
                    'success' => false,
                    'message' => "Date $newDate is not available. Booking limit reached."
                ], 400);
            }

            // Update the service date
            BookingService::where('booking_id', $id)
                ->where('service_type', $serviceName)
                ->update(['appointment_date' => $newDate]);

            // Return updated booking details
            return $this->getFormattedBooking($id);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rescheduling appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete an appointment
     *
     * @param int $id
     * @return JsonResponse
     */
    public function complete($id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);

            // Get the completed status ID
            $completedStatus = BookingStatus::where('status_name', 'Completed')->first();
            if (!$completedStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Completed status not found in database'
                ], 500);
            }

            $booking->status_id = $completedStatus->id;
            $booking->save();

            // Return the completed appointment data
            return $this->getFormattedBooking($id);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error completing appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign or update technicians for a booking
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function assignTechnicians(Request $request, $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            $technicianNames = $request->input('technician_names', []);

            $this->assignTechniciansToBooking($booking, $technicianNames);

            return response()->json([
                'success' => true,
                'message' => 'Technicians assigned successfully',
                'technicians' => $booking->fresh()->technicians->pluck('name')->toArray()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning technicians: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all technicians for dropdown
     *
     * @return JsonResponse
     */
    public function getTechnicians(): JsonResponse
    {
        $technicians = Technician::select('id', 'name')->get();
        return response()->json($technicians);
    }

    /**
     * Helper function to assign technicians to a booking
     *
     * @param Booking $booking
     * @param array $technicianNames
     * @return void
     */
    private function assignTechniciansToBooking(Booking $booking, array $technicianNames): void
    {
        if (empty($technicianNames)) {
            return;
        }

        // Clear existing technician assignments
        $booking->technicians()->detach();

        $technicianIds = [];
        foreach ($technicianNames as $name) {
            $name = trim($name);
            if (!empty($name)) {
                // Find or create technician
                $technician = Technician::firstOrCreate(['name' => $name]);
                $technicianIds[] = $technician->id;
            }
        }

        // Assign technicians to booking
        if (!empty($technicianIds)) {
            $booking->technicians()->attach($technicianIds);
        }
    }

    /**
     * Helper function to get formatted booking data
     *
     * @param int $id
     * @return JsonResponse
     */
    private function getFormattedBooking($id): JsonResponse
    {
        $booking = Booking::with(['customer', 'status', 'services.acTypes', 'technicians'])->findOrFail($id);
        $servicesData = [];

        foreach ($booking->services as $service) {
            $acTypeNames = $service->acTypes->pluck('type_name')->toArray();

            $servicesData[] = [
                'type' => $service->service_type,
                'date' => $service->appointment_date,
                'ac_types' => $acTypeNames
            ];
        }

        return response()->json([
            'success' => true,
            'booking' => [
                'id' => $booking->id,
                'name' => $booking->customer->name,
                'phone' => $booking->customer->phone,
                'email' => $booking->customer->email,
                'complete_address' => $booking->customer->complete_address,
                'status' => $booking->status->status_name,
                'status_id' => $booking->status_id,
                'technicians' => $booking->technicians->pluck('name')->toArray(),
                'services' => json_encode($servicesData),
                'created_at' => $booking->created_at
            ]
        ]);
    }

    /**
     * Helper method to prepare data for email
     *
     * @param Booking $booking
     * @return array
     */
    private function prepareAppointmentDataForEmail(Booking $booking): array
    {
        $formattedServices = [];

        foreach ($booking->services as $service) {
            $acTypeNames = $service->acTypes->pluck('type_name')->toArray();

            $formattedServices[] = [
                'type' => $service->service_type,
                'date' => $service->appointment_date,
                'ac_types' => $acTypeNames
            ];
        }

        return [
            'id' => $booking->id,
            'name' => $booking->customer->name,
            'phone' => $booking->customer->phone,
            'email' => $booking->customer->email,
            'address' => $booking->customer->complete_address,
            'services' => $formattedServices,
            'technicians' => $booking->technicians->pluck('name')->toArray()
        ];
    }
}
