<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quotations')
            ->where('revision_no', 0)
            ->whereNotNull('quotation_no')
            ->where('quotation_no', '<>', '')
            ->where(fn ($query) => $query->whereNull('revision_code')->orWhere('revision_code', ''))
            ->select(['id', 'quotation_no'])
            ->chunkById(500, function ($quotations) {
                foreach ($quotations as $quotation) {
                    DB::table('quotations')->where('id', $quotation->id)
                        ->where('revision_no', 0)
                        ->where(fn ($query) => $query->whereNull('revision_code')->orWhere('revision_code', ''))
                        ->update(['revision_code' => $quotation->quotation_no.'_R0']);
                }
            });
    }

    public function down(): void
    {
        
    }
};
