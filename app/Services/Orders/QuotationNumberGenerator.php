<?php

namespace App\Services\Orders;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class QuotationNumberGenerator
{
    public function next(): string
    {
        $connection = DB::connection();

        return $connection->transaction(function () use ($connection) {
            $counter = $connection->table('document_counters')
                ->where('type', 'quotation')
                
                ->where('date', '1970-01-01');

            do {
                $updated = (clone $counter)->increment('last_number', 1, [
                    'updated_at' => Carbon::now('Asia/Jakarta'),
                ]);

                if ($updated !== 1) {
                    throw new LogicException('Quotation counter is missing. Run the quotation counter migration first.');
                }

                $number = (int) (clone $counter)->value('last_number');
                $quotationNo = 'QT_DEAL_'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
                
                $alreadyUsed = $connection->table('quotations')
                    ->where('quotation_no', $quotationNo)->lockForUpdate()->exists();
            } while ($alreadyUsed);

            return $quotationNo;
        }, 5);
    }
}
