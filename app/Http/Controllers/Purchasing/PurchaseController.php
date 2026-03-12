<?php

// Test Replace
namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Leads\Lead;

class PurchaseController extends Controller
{
    public function list(Request $request)
    {
        if (! Schema::hasTable('purchasings')) {
            return response()->json([
                'data' => [],
                'message' => 'Table purchasings not found',
            ], 200);
        }

        $query = DB::table('purchasings');

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->input('lead_id'));
        }

        $purchasings = $query->orderByDesc('created_at')->get();

        // Ambil semua lead terkait dan tempelkan sebagai anak di setiap item (field "lead" tepat setelah lead_id)
        $leadIds = $purchasings->pluck('lead_id')->filter()->unique();

        $leads = $leadIds->isEmpty()
            ? collect()
            : Lead::with([
                'status',
                'source',
                'segment',
                'region',
                'product',
                'meetings.expense.details.expenseType',
                'meetings.expense.financeRequest',
                'meetings.attachment',
                'quotation.items',
                'quotation.proformas',
                'quotation.order.orderItems',
                'quotation.reviews.reviewer',
                'picExtensions',
                'factoryCity',
            ])->whereIn('id', $leadIds)->get()->keyBy('id');

        $purchasings = $purchasings->map(function ($row) use ($leads) {
            $row->lead = $leads->get($row->lead_id);
            return $row;
        });

        return response()->json([
            'data' => $purchasings,
        ]);
    }

    public function save(Request $request, $id = null)
    {
        if (! Schema::hasTable('purchasings')) {
            return response()->json([
                'message' => 'Table purchasings not found',
            ], 404);
        }

        $existing = null;
        if ($id !== null) {
            $existing = DB::table('purchasings')->where('id', $id)->first();

            if (! $existing) {
                return response()->json([
                    'message' => 'Purchasing record not found',
                ], 404);
            }
        }

        // Tentukan status_code, nama status, dan stage berdasarkan input
        $statusInput      = $request->input('status');
        $statusCodeInput  = $request->input('status_code');
        $statusCode       = null;
        $statusName       = $statusInput;

        if ($statusCodeInput !== null) {
            $statusCode = (int) $statusCodeInput;
            $statusName = $this->mapStatusCodeToName($statusCode);
        } elseif ($statusInput !== null) {
            // Coba cari kode berdasarkan nama status yang dikirim (case-insensitive)
            for ($i = 1; $i <= 19; $i++) {
                if (strtolower($this->mapStatusCodeToName($i)) === strtolower($statusInput)) {
                    $statusCode = $i;
                    $statusName = $this->mapStatusCodeToName($i);
                    break;
                }
            }
        }

        // Default stage
        $stage = $existing ? $existing->stage : 'Invoice Received';
        if ($statusCode !== null) {
            $stage = $this->mapStatusToStage($statusCode);
        }

        // Tentukan kewajiban notes / file berdasarkan status
        $isPendingOrCancel = false;
        $isWaitingToCompleted = false;

        if ($statusCode !== null) {
            if (in_array($statusCode, [18, 19], true)) {
                // Pending atau Cancel
                $isPendingOrCancel = true;
            } elseif ($statusCode >= 1 && $statusCode <= 17) {
                // Waiting sampai Completed
                $isWaitingToCompleted = true;
            }
        }

        $rules = [
            'status_code' => 'nullable|integer|min:1|max:19',
            'status'      => 'required_without:status_code|string|max:100',
            // Pending atau Cancel: notes wajib, selain itu opsional
            'notes'       => $isPendingOrCancel ? 'required|string' : 'nullable|string',
        ];

        // Waiting sampai Completed: file wajib, selain itu opsional
        if ($isWaitingToCompleted) {
            $rules['file'] = 'required|file';
        } else {
            $rules['file'] = 'nullable|file';
        }

        // Untuk create baru, lead_id wajib diisi
        if ($id === null) {
            $rules['lead_id'] = 'required|integer';
        }

        $validated = $request->validate($rules);

        $filesJson = $existing ? $existing->files : null;

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('purchasing');
            $filesJson = json_encode([$path]);
        }

        if ($id !== null) {
            DB::table('purchasings')
                ->where('id', $id)
                ->update([
                    'stage'      => $stage,
                    'status'     => $statusName ?? $validated['status'],
                    'notes'      => $validated['notes'] ?? null,
                    'files'      => $filesJson,
                    'updated_at' => now(),
                ]);
        } else {
            $id = DB::table('purchasings')->insertGetId([
                'lead_id'    => $validated['lead_id'],
                'stage'      => $stage,
                'status'     => $statusName ?? $validated['status'],
                'notes'      => $validated['notes'] ?? null,
                'files'      => $filesJson,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $record = DB::table('purchasings')->where('id', $id)->first();

        return response()->json([
            'data' => $record,
        ]);
    }

    public function storeFromLead(int $leadId, string $stage = 'Invoice Received', string $status = 'Waiting', ?string $notes = null, $files = null): void
    {
        if (! Schema::hasTable('purchasings')) {
            return;
        }

        // Cek per kombinasi lead + stage supaya satu lead bisa punya beberapa tahapan
        $exists = DB::table('purchasings')
            ->where('lead_id', $leadId)
            ->where('stage', $stage)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('purchasings')->insert([
            'lead_id'    => $leadId,
            'stage'      => $stage,
            'status'     => $status,
            'notes'      => $notes,
            'files'      => $files ? json_encode($files) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

   
    private function mapStatusToStage(int $status): string
    {
        if ($status >= 1 && $status <= 3) {
            return 'Invoice Received';
        }

        if ($status >= 4 && $status <= 15) {
            return 'Vendor Processing';
        }

        if ($status === 16) {
            return 'Ready for Handover';
        }

        if ($status === 17) {
            return 'Completed';
        }

        if ($status === 18) {
            return 'Pending';
        }

        if ($status === 19) {
            return 'Cancelled';
        }

        // Default fallback kalau suatu saat ada kode lain
        return 'Invoice Received';
    }

   
    private function mapStatusCodeToName(int $status): string
    {
        $map = [
            1  => 'Waiting',
            2  => 'Accepted',
            3  => 'On Progress Production',
            4  => '50% Production',
            5  => '70% Production',
            6  => '100% Production',
            7  => 'Running Test',
            8  => 'Machine Completed',
            9  => 'Document Registration',
            10 => 'Waiting to Deliver',
            11 => 'On Delivery to Indonesia',
            12 => 'Arrived in Indonesia',
            13 => 'Delivery to Customer',
            14 => 'On Progress Install',
            15 => 'Running Test Final',
            16 => 'BAST',
            17 => 'Completed',
            18 => 'Pending',
            19 => 'Cancel',
        ];

        return $map[$status] ?? 'Waiting';
    }

  
    public function storeFromLeadWithStatus(int $leadId, int $statusCode, $file = null): void
    {
        $stage = $this->mapStatusToStage($statusCode);
        $statusName = $this->mapStatusCodeToName($statusCode);

        // Simpan dengan nama status detail sesuai ref_purchasing_statuses (1..19)
        $this->storeFromLead($leadId, $stage, $statusName, null, $file ? [$file] : null);
    }

    public function handleLeadDeal(int $leadId): void
    {
        // Saat lead pertama kali DEAL, set stage awal = "Invoice Received"
        // dan status awal = "Waiting" (sesuai ref_purchasing_statuses id = 1).
        $this->storeFromLead($leadId, 'Invoice Received', 'Waiting');
    }
}
