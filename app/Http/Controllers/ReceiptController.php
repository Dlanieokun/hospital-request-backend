<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
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
            ->selectRaw('id AS case_id')
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
                'case_id'        => $request->input('arrival.case_id'),
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

    public function AllReceipt(Request $request)
    {
        $validated = $request->validate([
            'page'     => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search'   => 'sometimes|nullable|string|max:255',
            'statuses' => 'sometimes|nullable|string',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 5);
        $search  = trim($validated['search'] ?? '');

        // 'pending' is intentionally not allowed: those receipts are not requests yet
        $allowedStatuses = ['request', 'preparing', 'prepared', 'release'];
        $statuses = $request->has('statuses')
            ? array_values(array_intersect(
                array_filter(array_map('trim', explode(',', (string) $request->input('statuses')))),
                $allowedStatuses
            ))
            : $allowedStatuses;

        // Escape LIKE wildcards so a search for "%" or "_" is treated literally
        $like = '%' . addcslashes($search, '%_\\') . '%';

        $receipts = Receipts::query()
            ->whereIn('status_request', $statuses)
            ->when($search !== '', function ($q) use ($like) {
                $q->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                      ->orWhere('reference', 'like', $like);
                });
            })
            ->orderByDesc('id')
            // Relationships are eager-loaded only for the rows on the requested page
            ->with([
                'certificateRequests.details',
                'certificateRequests.subOptions.details',
                'certificateRequests.subQuestions.details',
                'certificateRequests.syncData',
            ])
            ->paginate($perPage);

        return response()->json($receipts, 200);
    }

    public function receiptRelease(Request $request, $id)
    {
        $receipt = Receipts::find($id);

        if (!$receipt) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $receipt->status_request = $request->status;
        $receipt->receiver_name = $request->receiver_name;
        $receipt->release_date = $request->release_date;
        $receipt->save();

        return response()->json($receipt, 200);
    }

    public function onlineReceipt()
    {
        $receipts = Receipts::where('mode', '=', 'online')->wherenot('ref', '=', null)->pluck('ref');

        return response()->json($receipts, 200);
    }

    public function onlinePaid(Request $request)
    {
        foreach ($request->all() as $payment) {
            if (
                isset($payment['payment_status'], $payment['bux_transaction_id']) &&
                $payment['payment_status'] === 'paid'
            ) {
                Receipts::where('ref', $payment['bux_transaction_id'])
                    ->update(['status' => 'paid']);
            }
        }

        return response()->json(['message' => 'Processed'], 200);
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

    public function generateReport(Request $request)
    {
        // 1. Setup Date Range
        $startDate = $request->json('startDate', '2026-05-01');
        $endDate = $request->json('endDate', '2026-05-05');

        // 2. Fetch all certificates to build dynamic columns
        $certificates = Certificate::all();
        $knownNames = $certificates->pluck('name')->toArray();

        // 3. Build the SQL Selects
        $selects = [
            DB::raw("DATE(receipts.release_date) as day")
        ];

        foreach ($knownNames as $name) {
            // Use backticks for SQL safety and sum the 'copies' field
            $selects[] = DB::raw("SUM(CASE WHEN certificates.name = '{$name}' THEN certificate_requests.copies ELSE 0 END) as `{$name}`");
        }

        // 4. Execute the query
        $data = Receipts::query()
            ->join('certificate_requests', 'receipts.id', '=', 'certificate_requests.receipt_id')
            ->join('certificates', 'certificate_requests.certificate_id', '=', 'certificates.id')
            ->select($selects)
            ->whereDate('receipts.release_date', '>=', $startDate)
            ->whereDate('receipts.release_date', '<=', $endDate)
            ->where('receipts.status_request', '=', 'release')
            ->groupBy(DB::raw("DATE(receipts.release_date)"))
            ->get()
            ->keyBy('day'); // Keys the collection by the date for easy lookup

        // 5. Fill in the missing dates (The "Calendar" logic)
        $report = [];
        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day')
        );

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');

            if (isset($data[$formattedDate])) {
                // If DB has data for this day, use it
                $report[] = $data[$formattedDate];
            } else {
                // Otherwise, create a "zeroed out" row
                $emptyRow = ['day' => $formattedDate];
                foreach ($knownNames as $name) {
                    $emptyRow[$name] = 0;
                }
                $report[] = $emptyRow;
            }
        }

        $datas = [
          "report" => $report,
          "certificates" => $knownNames
        ];

        return response()->json($datas, 200);
    }
}