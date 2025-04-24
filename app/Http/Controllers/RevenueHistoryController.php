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
        $mergedHistory = DB::table('revenue_history as rh')
            ->join('bookings as b', 'rh.booking_id', '=', 'b.id')
            ->join('booking_services as bs', 'b.id', '=', 'bs.booking_id')
            ->select(
                'rh.booking_id',
                'rh.revenue_date',
                'rh.total_revenue', // Keep the original total revenue without aggregating again
                // Get service types from booking_services table
                DB::raw('GROUP_CONCAT(DISTINCT bs.service_type SEPARATOR ", ") as service_types')
            )
            ->groupBy('rh.booking_id', 'rh.revenue_date', 'rh.total_revenue') // Include total_revenue in group by
            ->orderBy('rh.revenue_date', 'desc')
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

            // For each appointment (not for each service), create a revenue record
            foreach ($validatedData['appointments'] as $appointmentId) {
                $booking = Booking::findOrFail($appointmentId);

                // Initialize defaults
                $netRevenue = 0;

                // If detailed information is provided (from Revenue.jsx)
                if (isset($validatedData['appointment_details'])) {
                    foreach ($validatedData['appointment_details'] as $detail) {
                        if ($detail['id'] == $appointmentId) {
                            $netRevenue = $detail['net_revenue'] ?? 0;
                            break;
                        }
                    }
                } else {
                    // Set revenue from the total (for older/simpler implementations)
                    $netRevenue = $validatedData['total_revenue'] / count($validatedData['appointments']);
                }

                // Create only ONE revenue record per appointment regardless of number of services
                $revenueHistory = new RevenueHistory();
                $revenueHistory->revenue_date = $validatedData['revenue_date'];
                $revenueHistory->total_revenue = $netRevenue; // Store net revenue
                $revenueHistory->booking_id = $booking->id;
                $revenueHistory->save();
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
        // Modified to avoid double-counting revenue
        $summary = DB::table('revenue_history as rh')
            ->join('bookings as b', 'rh.booking_id', '=', 'b.id')
            ->join('booking_services as bs', 'b.id', '=', 'bs.booking_id')
            ->select(
                'bs.service_type',
                // Use DISTINCT on booking_id to avoid counting the same booking multiple times
                DB::raw('SUM(DISTINCT rh.total_revenue) as total')
            )
            ->groupBy('bs.service_type')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'summary' => $summary
        ]);
    }
}
