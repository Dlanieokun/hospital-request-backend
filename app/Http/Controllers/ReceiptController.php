<?php

namespace App\Http\Controllers;

use App\Models\Receipts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function getDate($id)
    {
        $patient_date = DB::connection('mysql2')
            ->table('patient_case_tbl')
            ->selectRaw('DATE(arrival_date) AS date')
            ->selectRaw('TIME(arrival_time) AS time')
            ->where('patient_id', $id)
            ->get();

        if (! $patient_date) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        return response()->json($patient_date, 200);
    }

    public function storeReciept(Request $request) {
        // Insert the data into the 'receipts' table
        DB::table('receipts')->insert([
            'reference'  => $request->transactionId,
            'name'       => $request->userName,
            'paid'       => $request->total,
            'mode'       => $request->paymentMethod,
            'created_at' => now(),
            'updated_at' => now(),
        ]);



        return response()->json(['message' => 'Receipt created successfully'], 201);
    }

}
