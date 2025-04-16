<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RevenueHistory;
use App\Models\Booking;

class RevenueHistoryController extends Controller
{
    /**
     * Get all revenue history records with merged services per booking
     */
    public function index(Request $request)
    {
        // Get all revenue history entries, properly merged by booking
        $mergedHistory = DB::table('revenue_history')
            ->select(
                'booking_id',
                'revenue_date',
                DB::raw('SUM(total_revenue) as total_revenue'),
                // Get service types, ensuring they're combined properly
                DB::raw('GROUP_CONCAT(DISTINCT service_type SEPARATOR ", ") as service_types')
            )
            ->groupBy('booking_id', 'revenue_date')
            ->orderBy('revenue_date', 'desc')
            ->get();

        // Calculate total amount across all records
        $totalAmount = DB::table('revenue_history')->sum('total_revenue');

        return response()->json([
            'history' => $mergedHistory,
            'totalAmount' => $totalAmount
        ]);
    }

    /**
     * Save new revenue history records
     */
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'revenue_date' => 'required|date',
                'total_revenue' => 'required|numeric|min:0',
                'total_discount' => 'sometimes|numeric|min:0',
                'appointments' => 'required|array',
                'appointment_details' => 'sometimes|array'
            ]);

            // Begin transaction
            DB::beginTransaction();

            // For each appointment, create a revenue record
            foreach ($validatedData['appointments'] as $index => $appointmentId) {
                $booking = Booking::findOrFail($appointmentId);

                // Initialize defaults
                $grossRevenue = 0;
                $netRevenue = 0;
                $serviceTypes = [];

                // If detailed information is provided (from Revenue.jsx)
                if (isset($validatedData['appointment_details'])) {
                    foreach ($validatedData['appointment_details'] as $detail) {
                        if ($detail['id'] == $appointmentId) {
                            $grossRevenue = $detail['gross_revenue'] ?? 0;
                            $netRevenue = $detail['net_revenue'] ?? 0;

                            // Get service info from the booking
                            try {
                                $bookingServices = DB::table('booking_services')
                                    ->where('booking_id', $booking->id)
                                    ->get();

                                foreach ($bookingServices as $service) {
                                    $serviceTypes[] = $service->service_type;
                                }
                            } catch (\Exception $e) {
                                // If there's an error getting services, try other methods
                                if ($booking->services) {
                                    $services = json_decode($booking->services, true);
                                    if (is_array($services)) {
                                        foreach ($services as $service) {
                                            $serviceTypes[] = $service['type'] ?? 'Unknown Service';
                                        }
                                    }
                                }
                            }

                            break;
                        }
                    }
                } else {
                    // Set revenue from the total (for older/simpler implementations)
                    $netRevenue = $validatedData['total_revenue'] / count($validatedData['appointments']);
                }

                // Create revenue record
                $revenueHistory = new RevenueHistory();
                $revenueHistory->revenue_date = $validatedData['revenue_date'];
                $revenueHistory->total_revenue = $netRevenue; // Store net revenue
                $revenueHistory->booking_id = $booking->id;
                $revenueHistory->service_type = implode(', ', array_unique($serviceTypes));
                $revenueHistory->save();

                // Remove the status update that was causing the issue
                // $booking->status = 'Processed';
                // $booking->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Revenue records saved successfully',
                'total_revenue' => $validatedData['total_revenue']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error saving revenue record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue summary by service type
     */
    public function getServiceRevenueSummary()
    {
        $summary = DB::table('revenue_history')
            ->whereNotNull('service_type')
            ->where('service_type', '!=', '')
            ->select('service_type', DB::raw('SUM(total_revenue) as total'))
            ->groupBy('service_type')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'summary' => $summary
        ]);
    }
}
