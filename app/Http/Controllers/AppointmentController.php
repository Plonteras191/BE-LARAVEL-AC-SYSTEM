<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\BookingActype;
use App\Models\Technician;
use App\Models\BookingStatus;
use App\Models\AcType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AppointmentConfirmation;
use App\Mail\AppointmentRejection;

class AppointmentController extends Controller
{
    // Get all appointments (admin view)
    public function index()
    {
        $bookings = Booking::with(['customer', 'status', 'services.acTypes', 'technicians'])->get();
        $formattedBookings = [];

        foreach ($bookings as $booking) {
            $servicesData = [];

            foreach ($booking->services as $service) {
                $acTypeNames = $service->acTypes->pluck('type_name')->toArray();

                $servicesData[] = [
                    'type' => $service->service_type,
                    'date' => $service->appointment_date,
                    'ac_types' => $acTypeNames
                ];
            }

            $formattedBookings[] = [
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
            ];
        }

        return response()->json($formattedBookings);
    }

    // Delete (reject) an appointment
    public function destroy($id)
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

    // Reschedule a service
    public function reschedule(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $serviceName = $request->input('service_name');
            $newDate = $request->input('new_date');

            // Check if the new date doesn't exceed our limit (2 services per day)
            $acceptedStatus = BookingStatus::whereIn('status_name', ['Pending', 'Accepted'])->pluck('id');

            $existingCount = BookingService::whereDate('appointment_date', $newDate)
                ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
                ->whereIn('bookings.status_id', $acceptedStatus)
                ->where('booking_services.booking_id', '!=', $id) // Exclude current booking
                ->count();

            if ($existingCount >= 2) {
                return response()->json([
                    'error' => "Date $newDate is not available. Service limit reached."
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
                'error' => 'Error rescheduling appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    // Accept an appointment
    public function accept(Request $request, $id)
    {
        try {
            $booking = Booking::with(['customer', 'services'])->findOrFail($id);

            // Before accepting, recheck date availability to prevent conflicts
            $acceptedStatus = BookingStatus::whereIn('status_name', ['Pending', 'Accepted'])->pluck('id');

            foreach ($booking->services as $service) {
                $date = $service->appointment_date;

                // Count existing services on this date (excluding this booking)
                $existingCount = BookingService::whereDate('appointment_date', $date)
                    ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
                    ->whereIn('bookings.status_id', $acceptedStatus)
                    ->where('booking_services.booking_id', '!=', $id)
                    ->count();

                if ($existingCount >= 2) {
                    return response()->json([
                        'error' => "Cannot accept booking. Date $date now exceeds service limit."
                    ], 400);
                }
            }

            // Get the accepted status ID
            $acceptedStatusRecord = BookingStatus::where('status_name', 'Accepted')->first();
            if (!$acceptedStatusRecord) {
                return response()->json([
                    'error' => 'Accepted status not found in database'
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
                'id' => $booking->id,
                'status' => $acceptedStatusRecord->status_name,
                'status_id' => $booking->status_id,
                'name' => $booking->customer->name,
                'email' => $booking->customer->email,
                'technicians' => $booking->technicians->pluck('name')->toArray(),
                'message' => 'Appointment accepted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error accepting appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    // Complete an appointment
    public function complete($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Get the completed status ID
            $completedStatus = BookingStatus::where('status_name', 'Completed')->first();
            if (!$completedStatus) {
                return response()->json([
                    'error' => 'Completed status not found in database'
                ], 500);
            }

            $booking->status_id = $completedStatus->id;
            $booking->save();

            // Return the completed appointment data
            return $this->getFormattedBooking($id);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error completing appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    // Assign or update technicians for a booking
    public function assignTechnicians(Request $request, $id)
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
                'error' => 'Error assigning technicians: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get all technicians for dropdown
    public function getTechnicians()
    {
        $technicians = Technician::select('id', 'name')->get();
        return response()->json($technicians);
    }

    // Helper function to assign technicians to a booking
    private function assignTechniciansToBooking($booking, $technicianNames)
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

    // Helper function to get formatted booking data
    private function getFormattedBooking($id)
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
        ]);
    }

    // Helper method to prepare data for email
    private function prepareAppointmentDataForEmail($booking)
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
