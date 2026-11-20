<?php

namespace App\Services\Orders;

use App\Models\Orders\Quotation;
use App\Models\Orders\TempQuotation;

class QuotationRevisions
{
    public function ensureInitialSnapshot(Quotation $quotation): ?TempQuotation
    {
        $snapshot = $quotation->getConnection()->transaction(function () use ($quotation) {
            $current = $quotation->newQuery()->whereKey($quotation->getKey())->lockForUpdate()->firstOrFail();

            if ((int) $current->revision_no !== 0 || blank($current->quotation_no)) {
                return null;
            }

            if (blank($current->revision_code)) {
                
                $current->revision_code = $current->quotation_no.'_R0';
                $current->timestamps = false;
                $current->saveQuietly();
            }

            $snapshot = $current->revisions()->withTrashed()
                ->where('revision_no', 0)
                ->where('status', 'submitted')
                ->first();

            if ($snapshot) {
                return $snapshot;
            }

            $items = $current->items()->orderBy('id')->get();
            $terms = $current->paymentTerms()->get();

            $snapshot = $current->revisions()->create(array_merge($current->only([
                'lead_id', 'subtotal', 'tax_pct', 'tax_total', 'total_discount',
                'grand_total', 'booking_fee', 'created_by',
            ]), [
                'revision_no' => 0,
                'revision_code' => $current->revision_code,
                'status' => 'submitted',
            ]));
            
            $snapshot->timestamps = false;
            $snapshot->forceFill($current->only(['created_at', 'updated_at']))->saveQuietly();
            $snapshot->timestamps = true;

            $copiedItems = [];
            foreach ($items as $item) {
                $copiedItems[$item->id] = $snapshot->items()->create($item->only([
                    'product_id', 'qty', 'description', 'unit_price', 'discount_pct',
                    'line_total', 'is_visible_pdf',
                ]));
            }

            foreach ($items as $item) {
                if ($item->merge_into_item_id && isset($copiedItems[$item->merge_into_item_id])) {
                    $copiedItems[$item->id]->update([
                        'merge_into_item_id' => $copiedItems[$item->merge_into_item_id]->id,
                    ]);
                }
            }

            foreach ($terms as $term) {
                $snapshot->paymentTerms()->create($term->only(['term_no', 'percentage', 'description']));
            }

            return $snapshot;
        }, 3);

        if ($snapshot) {
            $quotation->setAttribute('revision_code', $snapshot->revision_code);
            $quotation->syncOriginalAttribute('revision_code');
            $quotation->unsetRelation('revisions');
        }

        return $snapshot;
    }
}
