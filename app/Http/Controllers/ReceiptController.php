<?php

namespace App\Http\Controllers;

use App\Models\Receipts;
use Carbon\Carbon;
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
        $receipt->status_request = 'request';
        $receipt->request_date = now();
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

    public function storeReceipt(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // 1. Create the Main Receipt
            $receipt = Receipts::create([
                'reference'      => $request->transactionId,
                'name'           => $request->userName,
                'paid'           => $request->total,
                'mode'           => $request->paymentMethod,
                'date'           => Carbon::parse($request->input('arrival.date'))->format('Y-m-d'),
                'time'           => $request->input('arrival.time'),
                'status'         => 'Pending',
            ]);

            // 2. Loop through each item in the 'requests' array
            foreach ($request->input('requests', []) as $certData) {

                // Insert into certificate_requests and get the ID for child tables
                $certificateRequestId = DB::table('certificate_requests')->insertGetId([
                    'receipt_id'     => $receipt->id,
                    'certificate_id' => $certData['id'],
                    'copies' => $certData['copies'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                // 3. Handle sub_option nested array
                if (isset($certData['sub_option']) && is_array($certData['sub_option'])) {
                    foreach ($certData['sub_option'] as $option) {
                        DB::table('sub_option_request')->insert([
                            'certificate_id' => $certificateRequestId,
                            'sub_option_id'  => $option['id'],
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                    }
                }

                // 4. Handle sub_question nested array
                if (isset($certData['sub_question']) && is_array($certData['sub_question'])) {
                    foreach ($certData['sub_question'] as $question) {
                        DB::table('sub_question_request')->insert([
                            'certificate_id'  => $certificateRequestId, // links to certificate_requests.id
                            'sub_question_id' => $question['id'],
                            'answer'          => $question['answer'] ?? null,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Transaction completed successfully',
                'receipt_id' => $receipt->id
            ], 201);
        });
    }

    public function getReceipt($id)
    {
        $receipt = Receipts::with([
            'certificateRequests.details',
            'certificateRequests.subOptions.details',
            'certificateRequests.subQuestions.details'
        ])->find($id);

        if (!$receipt) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json($receipt);
    }

    public function AllReceipt()
    {
        // Eager load all levels of the relationship hierarchy
        $receipts = Receipts::with([
            'certificateRequests.details',
            'certificateRequests.subOptions.details',
            'certificateRequests.subQuestions.details'
        ])->whereNot('status_request', '=', 'pending')->get();

        if ($receipts->isEmpty()) {
            return response()->json(['message' => 'No receipts found'], 404);
        }

        return response()->json($receipts, 200);
    }

    public function receiptRelease(Request $request, $id)
    {
        $receipt = Receipts::find($id);

        if (!$receipt) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $receipt->status_request = $request->status;
        $receipt->release_date = now();
        $receipt->save();

        return response()->json($receipt, 200);
    }

    public function onlineReceipt()
    {
        $receipts = Receipts::where('mode', '=', 'online')->pluck('ref');

        return response()->json($receipts, 200);
    }

    public function setReceipt(Request $request, $id)
    {
        $updated = Receipts::where('reference', $id)
            ->update(['ref' => $request->ref]);

        if ($updated === 0) {
            return response()->json(['message' => 'Receipt not found'], 404);
        }

        return response()->json(['message' => 'Updated successfully'], 200);
    }

}
