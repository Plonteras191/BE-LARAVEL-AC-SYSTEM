<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\BookingActype;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmation;
use App\Mail\AppointmentRejection;

class AppointmentController extends Controller
{
    // Get all appointments (admin view)
    public function index()
    {
        $bookings = Booking::all();
        $formattedBookings = [];

        foreach ($bookings as $booking) {
            $services = BookingService::where('booking_id', $booking->id)->get();
            $servicesData = [];

            foreach ($services as $service) {
                // Get AC types for this specific service
                $acTypes = BookingActype::where('booking_service_id', $service->id)
                    ->pluck('ac_type')
                    ->toArray();

                $servicesData[] = [
                    'type' => $service->service_type,
                    'date' => $service->appointment_date,
                    'ac_types' => $acTypes
                ];
            }

            $formattedBookings[] = [
                'id' => $booking->id,
                'name' => $booking->name,
                'phone' => $booking->phone,
                'email' => $booking->email,
                'complete_address' => $booking->complete_address,
                'status' => $booking->status,
                'services' => json_encode($servicesData)
            ];
        }

        return response()->json($formattedBookings);
    }

    // Delete (reject) an appointment
    public function destroy($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Prepare data for email before changing status
            $appointmentData = $this->prepareAppointmentDataForEmail($booking);

            // Soft reject - just update status instead of deleting
            // This keeps our database records but frees up the service slots
            $booking->status = 'Rejected';
            $booking->save();

            // Send rejection email
            try {
                Mail::to($booking->email)->send(new AppointmentRejection($appointmentData));
            } catch (\Exception $emailError) {
                // Log the error but don't fail the request
                // You might want to add proper logging here
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
            $existingCount = BookingService::whereDate('appointment_date', $newDate)
                ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
                ->whereIn('bookings.status', ['Pending', 'Accepted'])
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
    public function accept($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Before accepting, recheck date availability to prevent conflicts
            $services = BookingService::where('booking_id', $id)->get();
            foreach ($services as $service) {
                $date = $service->appointment_date;

                // Count existing services on this date (excluding this booking)
                $existingCount = BookingService::whereDate('appointment_date', $date)
                    ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
                    ->whereIn('bookings.status', ['Pending', 'Accepted'])
                    ->where('booking_services.booking_id', '!=', $id)
                    ->count();

                if ($existingCount >= 2) {
                    return response()->json([
                        'error' => "Cannot accept booking. Date $date now exceeds service limit."
                    ], 400);
                }
            }

            $booking->status = 'Accepted';
            $booking->save();

            // Send confirmation email
            try {
                // Prepare data for email
                $appointmentData = $this->prepareAppointmentDataForEmail($booking);

                // Send email to customer
                Mail::to($booking->email)->send(new AppointmentConfirmation($appointmentData));

            } catch (\Exception $emailError) {
                // Log the error but don't fail the request
                // Add proper logging here
            }

            return response()->json([
                'id' => $booking->id,
                'status' => $booking->status,
                'name' => $booking->name,
                'email' => $booking->email,
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
            $booking->status = 'Completed';
            $booking->save();

            // Return the completed appointment data
            return $this->getFormattedBooking($id);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error completing appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper function to get formatted booking data
    private function getFormattedBooking($id)
    {
        $booking = Booking::findOrFail($id);
        $services = BookingService::where('booking_id', $id)->get();
        $servicesData = [];

        foreach ($services as $service) {
            // Get AC types for this specific service
            $acTypes = BookingActype::where('booking_service_id', $service->id)
                ->pluck('ac_type')
                ->toArray();

            $servicesData[] = [
                'type' => $service->service_type,
                'date' => $service->appointment_date,
                'ac_types' => $acTypes
            ];
        }

        return response()->json([
            'id' => $booking->id,
            'name' => $booking->name,
            'phone' => $booking->phone,
            'email' => $booking->email,
            'complete_address' => $booking->complete_address,
            'status' => $booking->status,
            'services' => json_encode($servicesData)
        ]);
    }

    // Helper method to prepare data for email
    private function prepareAppointmentDataForEmail($booking)
    {
        $services = BookingService::where('booking_id', $booking->id)->get();
        $formattedServices = [];

        foreach ($services as $service) {
            $acTypes = BookingActype::where('booking_service_id', $service->id)
                ->pluck('ac_type')
                ->toArray();

            $formattedServices[] = [
                'type' => $service->service_type,
                'date' => $service->appointment_date,
                'ac_types' => $acTypes
            ];
        }

        return [
            'id' => $booking->id,
            'name' => $booking->name,
            'phone' => $booking->phone,
            'email' => $booking->email,
            'address' => $booking->complete_address,
            'services' => $formattedServices
        ];
    }
}
