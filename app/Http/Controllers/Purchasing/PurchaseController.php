<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseController extends Controller
{
    /**
     * Simpan data purchasing (log per stage) untuk lead tertentu.
     * Dipakai oleh controller lain (mis. FinanceRequestController).
     */
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

    /**
     * Pemetaan kode status detail (1-19) ke stage level tinggi.
     */
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

    /**
     * Pemetaan kode status detail (1-19) ke nama status
     * sesuai tabel ref_purchasing_statuses.
     */
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

    /**
     * Simpan log purchasing berdasarkan kode status (1-19),
     * otomatis menentukan stages dari status.
     */
    public function storeFromLeadWithStatus(int $leadId, int $statusCode, $file = null): void
    {
        $stage = $this->mapStatusToStage($statusCode);
        $statusName = $this->mapStatusCodeToName($statusCode);

        // Simpan dengan nama status detail sesuai ref_purchasing_statuses (1..19)
        $this->storeFromLead($leadId, $stage, $statusName, null, $file ? [$file] : null);
    }

    /**
     * Dipanggil ketika status lead berubah menjadi DEAL.
     */
    public function handleLeadDeal(int $leadId): void
    {
        // Saat lead pertama kali DEAL, set stage awal = "Invoice Received"
        // dan status awal = "Waiting" (sesuai ref_purchasing_statuses id = 1).
        $this->storeFromLead($leadId, 'Invoice Received', 'Waiting');
    }
}
