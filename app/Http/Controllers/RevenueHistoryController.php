<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RevenueHistory;

class RevenueHistoryController extends Controller
{
    // Get all revenue history records
    public function index()
    {
        $history = DB::table('revenue_history')
            ->orderBy('revenue_date', 'desc')
            ->get();

        $totalAmount = DB::table('revenue_history')->sum('total_revenue');

        return response()->json([
            'history' => $history,
            'totalAmount' => $totalAmount
        ]);
    }

    // Save a new revenue history record
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'revenue_date' => 'required|date',
                'total_revenue' => 'required|numeric|min:0',
                'appointments' => 'required|array'
            ]);

            // Begin transaction
            DB::beginTransaction();

            // Create revenue history record
            $revenueHistory = new RevenueHistory();
            $revenueHistory->revenue_date = $validatedData['revenue_date'];
            $revenueHistory->total_revenue = $validatedData['total_revenue'];
            $revenueHistory->save();

            // Update all appointments' status to reflect they've been processed for revenue
            foreach ($validatedData['appointments'] as $appointmentId) {
                DB::table('bookings')
                    ->where('id', $appointmentId)
                    ->update(['status' => 'Revenue Processed']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'id' => $revenueHistory->id,
                'message' => 'Revenue record saved successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error saving revenue record: ' . $e->getMessage()
            ], 500);
        }
    }
}
