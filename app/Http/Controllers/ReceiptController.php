<?php

namespace App\Http\Controllers;

use App\Models\Receipts;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    protected $connection = 'mysql';
    public function index()
    {
        $receipt = Receipts::all();

        return response()->json($receipt, 200);
    }

    public function paid(Request $request, $id)
    {
        $receipt = Receipts::findOrFail($id);

        $receipt->status = 'paid';
        $receipt->save();

        return response()->json([
            'message' => 'Receipt marked as paid',
            'data' => $receipt
        ], 200);
    }
}
