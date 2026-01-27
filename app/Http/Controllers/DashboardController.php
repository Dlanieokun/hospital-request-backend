<?php

namespace App\Http\Controllers;

use App\Models\Receipts;

class DashboardController extends Controller
{
    public function overview()
    {
        // Count of pending payments
        $paymentCount = Receipts::where('status', 'Pending')->count();

        // Count of paid receipts
        $paidCount = Receipts::where('status', 'paid')->count();

        // Total paid amount
        $totalPaid = Receipts::where('status', 'paid')->sum('paid') ?? 0;

        // Latest 5 per status_request
        $pending = Receipts::with('certificateRequests.details')
            ->where('status', 'Pending')
            ->limit(5)
            ->get();

        $request = Receipts::with('certificateRequests.details')
            ->where('status_request', 'request')
            ->limit(5)
            ->get();

        $release = Receipts::with('certificateRequests.details')
            ->where('status_request', 'release')
            ->limit(5)
            ->get();

        // Final response payload
        $overview = [
            'payment_count' => $paymentCount,
            'paid_count'    => $paidCount,
            'total_paid'    => $totalPaid,
            'pending'       => $pending,
            'request'       => $request,
            'release'       => $release,
        ];

        return response()->json($overview, 200);
    }
}
