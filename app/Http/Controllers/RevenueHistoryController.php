<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Revenue;
use App\Models\Booking;

class RevenueHistoryController extends Controller
{
    /**
     * Get all revenue history records with merged services per booking
     */
    public function index(Request $request)
    {
        // Get all revenue entries with booking and service information
        $mergedHistory = DB::table('revenue as r')
            ->join('bookings as b', 'r.booking_id', '=', 'b.id')
            ->join('customers as c', 'b.customer_id', '=', 'c.id')
            ->join('booking_services as bs', 'b.id', '=', 'bs.booking_id')
            ->join('booking_statuses as bst', 'b.status_id', '=', 'bst.id')
            ->select(
                'r.id as revenue_id',
                'r.booking_id',
                'r.revenue_date',
                'r.total_revenue',
                'r.created_at',
                'c.name as customer_name',
                'c.phone as customer_phone',
                'c.email as customer_email',
                'bst.status_name',
                // Get service types from booking_services table
                DB::raw('GROUP_CONCAT(DISTINCT bs.service_type SEPARATOR ", ") as service_types'),
                DB::raw('GROUP_CONCAT(DISTINCT bs.appointment_date SEPARATOR ", ") as appointment_dates')
            )
            ->groupBy(
                'r.id', 'r.booking_id', 'r.revenue_date', 'r.total_revenue', 'r.created_at',
                'c.name', 'c.phone', 'c.email', 'bst.status_name'
            )
            ->orderBy('r.revenue_date', 'desc')
            ->get();

        // Calculate total amount across all records
        $totalAmount = Revenue::sum('total_revenue');

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

            $createdRecords = [];

            // For each appointment, create or update a revenue record
            foreach ($validatedData['appointments'] as $appointmentId) {
                $booking = Booking::with(['customer', 'services', 'status'])->findOrFail($appointmentId);

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

                // Check if revenue record already exists for this booking
                $existingRevenue = Revenue::where('booking_id', $booking->id)->first();

                if ($existingRevenue) {
                    // Update existing record
                    $existingRevenue->revenue_date = $validatedData['revenue_date'];
                    $existingRevenue->total_revenue = $netRevenue;
                    $existingRevenue->save();
                    $createdRecords[] = $existingRevenue;
                } else {
                    // Create new revenue record
                    $revenue = new Revenue();
                    $revenue->revenue_date = $validatedData['revenue_date'];
                    $revenue->total_revenue = $netRevenue;
                    $revenue->booking_id = $booking->id;
                    $revenue->save();
                    $createdRecords[] = $revenue;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Revenue records saved successfully',
                'total_revenue' => $validatedData['total_revenue'],
                'records_created' => count($createdRecords)
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
        $summary = DB::table('revenue as r')
            ->join('bookings as b', 'r.booking_id', '=', 'b.id')
            ->join('booking_services as bs', 'b.id', '=', 'bs.booking_id')
            ->select(
                'bs.service_type',
                DB::raw('SUM(r.total_revenue) as total_revenue'),
                DB::raw('COUNT(DISTINCT r.booking_id) as total_bookings'),
                DB::raw('AVG(r.total_revenue) as average_revenue')
            )
            ->groupBy('bs.service_type')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return response()->json([
            'summary' => $summary
        ]);
    }

    /**
     * Get revenue summary by date range
     */
    public function getRevenueSummaryByDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $summary = Revenue::whereBetween('revenue_date', [$request->start_date, $request->end_date])
            ->selectRaw('
                DATE(revenue_date) as date,
                SUM(total_revenue) as total_revenue,
                COUNT(*) as total_bookings,
                AVG(total_revenue) as average_revenue
            ')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $totalRevenue = Revenue::whereBetween('revenue_date', [$request->start_date, $request->end_date])
            ->sum('total_revenue');

        return response()->json([
            'summary' => $summary,
            'total_revenue' => $totalRevenue,
            'date_range' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date
            ]
        ]);
    }

    /**
     * Get detailed revenue information for a specific booking
     */
    public function show($bookingId)
    {
        try {
            $revenue = Revenue::with(['booking.customer', 'booking.services.acTypes', 'booking.technicians', 'booking.status'])
                ->where('booking_id', $bookingId)
                ->firstOrFail();

            // Format services data
            $servicesData = [];
            foreach ($revenue->booking->services as $service) {
                $acTypeNames = $service->acTypes->pluck('type_name')->toArray();
                $servicesData[] = [
                    'type' => $service->service_type,
                    'date' => $service->appointment_date,
                    'ac_types' => $acTypeNames
                ];
            }

            return response()->json([
                'revenue' => [
                    'id' => $revenue->id,
                    'booking_id' => $revenue->booking_id,
                    'revenue_date' => $revenue->revenue_date,
                    'total_revenue' => $revenue->total_revenue,
                    'created_at' => $revenue->created_at,
                ],
                'booking' => [
                    'id' => $revenue->booking->id,
                    'status' => $revenue->booking->status->status_name,
                    'created_at' => $revenue->booking->created_at,
                ],
                'customer' => [
                    'name' => $revenue->booking->customer->name,
                    'phone' => $revenue->booking->customer->phone,
                    'email' => $revenue->booking->customer->email,
                    'address' => $revenue->booking->customer->complete_address,
                ],
                'services' => $servicesData,
                'technicians' => $revenue->booking->technicians->pluck('name')->toArray()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Revenue record not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update an existing revenue record
     */
    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'revenue_date' => 'required|date',
                'total_revenue' => 'required|numeric|min:0'
            ]);

            $revenue = Revenue::findOrFail($id);
            $revenue->revenue_date = $validatedData['revenue_date'];
            $revenue->total_revenue = $validatedData['total_revenue'];
            $revenue->save();

            return response()->json([
                'success' => true,
                'message' => 'Revenue record updated successfully',
                'revenue' => $revenue
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error updating revenue record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a revenue record
     */
    public function destroy($id)
    {
        try {
            $revenue = Revenue::findOrFail($id);
            $bookingId = $revenue->booking_id;
            $revenue->delete();

            return response()->json([
                'success' => true,
                'message' => 'Revenue record deleted successfully',
                'booking_id' => $bookingId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error deleting revenue record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue statistics
     */
    public function getRevenueStatistics()
    {
        $totalRevenue = Revenue::sum('total_revenue');
        $totalBookings = Revenue::count();
        $averageRevenue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;

        // Revenue by month for the current year
        $monthlyRevenue = Revenue::selectRaw('
                MONTH(revenue_date) as month,
                YEAR(revenue_date) as year,
                SUM(total_revenue) as total,
                COUNT(*) as bookings
            ')
            ->whereYear('revenue_date', date('Y'))
            ->groupBy('year', 'month')
            ->orderBy('month')
            ->get();

        // Top service types by revenue
        $topServices = DB::table('revenue as r')
            ->join('bookings as b', 'r.booking_id', '=', 'b.id')
            ->join('booking_services as bs', 'b.id', '=', 'bs.booking_id')
            ->select(
                'bs.service_type',
                DB::raw('SUM(r.total_revenue) as total_revenue'),
                DB::raw('COUNT(DISTINCT r.booking_id) as bookings')
            )
            ->groupBy('bs.service_type')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'statistics' => [
                'total_revenue' => $totalRevenue,
                'total_bookings' => $totalBookings,
                'average_revenue' => round($averageRevenue, 2),
                'monthly_revenue' => $monthlyRevenue,
                'top_services' => $topServices
            ]
        ]);
    }
}
