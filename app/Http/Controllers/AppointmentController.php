<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingService;
use Illuminate\Support\Facades\DB;

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
                // Get AC types for this booking
                $acTypes = DB::table('booking_actypes')
                    ->where('booking_id', $booking->id)
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
            $booking->delete();

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
            $booking->status = 'Accepted';
            $booking->save();

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
            $acTypes = DB::table('booking_actypes')
                ->where('booking_id', $id)
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
}
