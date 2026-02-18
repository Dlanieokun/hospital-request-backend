<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\DataMapping;
use App\Models\User;
use function Illuminate\Foundation\Testing\Concerns\json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    // GET /settings
    public function index()
    {
        $certificates = Certificate::with(['subQuestions', 'subOptions'])->get();
        $staff = User::all();

        $json = [
            'certificates' => $certificates,
            'staff' => $staff,
        ];

        return response()->json($json, 200);
    }

    public function requestList()
    {
        $certificates = Certificate::with(['subQuestions', 'subOptions'])->get();

        return response()->json($certificates, 200);
    }

    // POST /settings
    public function store(Request $request)
    {
        // 1. Validation to ensure data integrity
        $validated = $request->validate([
            'name'                      => 'required|string|unique:certificates,name',
            'fee'                       => 'required|numeric|min:0',
            'days'                      => 'required|integer|min:0',
            'url'                       => 'nullable|string',
            'sub_options'               => 'nullable|array',
            'sub_options.*.name'        => 'required|string',
            'sub_questions'             => 'nullable|array',
            'sub_questions.*.question'  => 'required|string',
            'sub_questions.*.type'      => 'required|string',
        ]);

        $certificate = DB::transaction(function () use ($validated) {
            // 2. Create the main Certificate
            $cert = Certificate::create([
                'name' => $validated['name'],
                'fee'  => $validated['fee'],
                'days' => $validated['days'],
                'url' => $validated['url'],
            ]);

            // 3. Create children (createMany ignores keys not in $fillable)
            if (!empty($validated['sub_questions'])) {
                $cert->subQuestions()->createMany($validated['sub_questions']);
            }

            if (!empty($validated['sub_options'])) {
                $cert->subOptions()->createMany($validated['sub_options']);
            }

            // 4. Return the fresh instance with relations
            return $cert->load(['subQuestions', 'subOptions']);
        });

        return response()->json([
            'message' => 'Certificate created successfully',
            'data' => $certificate
        ], 201);
    }

    // GET /settings/{id}
    public function show(string $id)
    {
        $certificate = Certificate::findOrFail($id);
        return response()->json($certificate, 200);
    }

    public function update(Request $request, $id)
    {
        $certificate = Certificate::findOrFail($id);

        $validated = $request->validate([
            'name'                      => 'required|string|unique:certificates,name,' . $id,
            'fee'                       => 'required|numeric|min:0',
            'days'                      => 'required|integer|min:0',
            'url'                       => 'nullable|string',
            'sub_options'               => 'nullable|array',
            'sub_options.*.id'          => 'nullable',
            'sub_options.*.name'        => 'required|string',
            'sub_questions'             => 'nullable|array',
            'sub_questions.*.id'        => 'nullable',
            'sub_questions.*.question'  => 'required|string',
            'sub_questions.*.type'      => 'required|string',
        ]);

        $updated = DB::transaction(function () use ($certificate, $validated) {
            // 1. Update Certificate
            $certificate->update([
                'name' => $validated['name'],
                'fee'  => $validated['fee'],
                'days' => $validated['days'],
                'url' => $validated['url'],
            ]);

            // 2. Sync Sub-Options
            $optionIds = collect($validated['sub_options'] ?? [])->pluck('id')->filter();
            // Delete items NOT in the payload
            $certificate->subOptions()->whereNotIn('id', $optionIds)->delete();

            foreach ($validated['sub_options'] ?? [] as $optionData) {
                $certificate->subOptions()->updateOrCreate(
                    ['id' => $optionData['id'] ?? null], // Match by ID
                    ['name' => $optionData['name']]      // Update or Create with these values
                );
            }

            // 3. Sync Sub-Questions
            $questionIds = collect($validated['sub_questions'] ?? [])->pluck('id')->filter();
            // Delete items NOT in the payload
            $certificate->subQuestions()->whereNotIn('id', $questionIds)->delete();

            foreach ($validated['sub_questions'] ?? [] as $questionData) {
                $certificate->subQuestions()->updateOrCreate(
                    ['id' => $questionData['id'] ?? null],
                    [
                        'question' => $questionData['question'],
                        'type'     => $questionData['type']
                    ]
                );
            }

            return $certificate->load(['subQuestions', 'subOptions']);
        });

        return response()->json([
            'message' => 'Certificate and relations synced successfully',
            'data' => $updated
        ], 200);
    }

    // DELETE /settings/{id}
    public function destroy(string $id)
    {
        $certificate = Certificate::findOrFail($id);

        // Use a transaction to ensure both parent and children are deleted
        DB::transaction(function () use ($certificate) {
            // Delete related records first
            $certificate->subQuestions()->delete();
            $certificate->subOptions()->delete();

            // Delete the certificate
            $certificate->delete();
        });

        return response()->json([
            'message' => 'Certificate and all associated questions/options deleted successfully'
        ], 200);
    }

    // Post Sync ===============
    public function dataMapping(Request $request, $id)
    {
        // 1. Find the certificate. If $id is 0 or non-existent, this throws a 404.
        $cert = Certificate::findOrFail($id);

        // 2. Updated validation to allow 'null' for key_sync
        $validated = $request->validate([
            'sync_name' => 'required|string|max:255',
            'data' => 'nullable|array',
            'data.*.key' => 'required|string',
            'data.*.key_sync' => 'nullable|string', // Changed from 'required' to 'nullable'
        ]);

        DB::transaction(function () use ($cert, $validated) {

            // 3. Update certificate sync name
            $cert->update([
                'syn_cert' => $validated['sync_name']
            ]);

            // 4. Clear old mappings using the table defined in your model ('sync_data')
            DataMapping::where('certificate_id', $cert->id)->delete();

            // 5. Insert new mappings safely
            if (!empty($validated['data'])) {
                $mappings = collect($validated['data'])->map(function ($item) use ($cert) {
                    return [
                        'certificate_id' => $cert->id,
                        'key'            => $item['key'],
                        'key_sync'       => $item['key_sync'],
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                });

                DataMapping::insert($mappings->toArray());
            }
        });

        return response()->json([
            'message' => 'Mapping completed successfully'
        ], 200);
    }

}

