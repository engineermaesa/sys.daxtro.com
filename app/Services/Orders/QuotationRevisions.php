<?php

namespace App\Services\Orders;

use App\Models\Orders\Quotation;
use App\Models\Orders\TempQuotation;
use App\Models\Orders\QuotationLog;
use Carbon\Carbon;

class QuotationRevisions
{
    public function startRevision(Quotation $quotation, int $createdBy): TempQuotation
    {
        return $quotation->getConnection()->transaction(function () use ($quotation, $createdBy) {
            $current = $quotation->newQuery()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($current->status, ['rejected', 'approved_by_bm'], true), 409,
                'This quotation is no longer available for revision. Reload the quotation.');
            $this->ensureInitialSnapshot($current);

            $draft = TempQuotation::where('lead_id', $current->lead_id)
                ->where('status', 'draft')->lockForUpdate()->first();
            $metadata = [
                'quotation_id' => $current->id,
                'revision_no' => $current->revision_no + 1,
                'revision_code' => $current->quotation_no.'_R'.($current->revision_no + 1),
            ];
            if ($draft) {
                $this->validateRevisionDraft($current, $draft);
                $draft->update($metadata);

                return $draft->load('items', 'paymentTerms');
            }

            $draft = $current->revisions()->create(array_merge($current->only([
                'lead_id', 'subtotal', 'tax_pct', 'tax_total', 'total_discount', 'grand_total', 'booking_fee',
            ]), $metadata, ['status' => 'draft', 'created_by' => $createdBy]));
            $this->copyContents($current, $draft);
            QuotationLog::create([
                'quotation_id' => $current->id, 'temp_quotation_id' => $draft->id,
                'action' => 'draft_created', 'user_id' => $createdBy,
                'logged_at' => Carbon::now('Asia/Jakarta'),
            ]);

            return $draft->load('items', 'paymentTerms');
        }, 3);
    }

    public function validateRevisionDraft(Quotation $quotation, TempQuotation $draft): void
    {
        $target = $quotation->revision_no + 1;
        abort_if(
            ($draft->quotation_id !== null && (int) $draft->quotation_id !== (int) $quotation->id)
            || ($draft->revision_no !== null && $draft->revision_no !== $target)
            || ($draft->revision_code !== null && $draft->revision_code !== $quotation->quotation_no.'_R'.$target),
            409, 'This revision is based on an older quotation. Reload the quotation before continuing.'
        );
    }

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

            $this->copyContents($current, $snapshot);

            return $snapshot;
        }, 3);

        if ($snapshot) {
            $quotation->setAttribute('revision_code', $snapshot->revision_code);
            $quotation->syncOriginalAttribute('revision_code');
            $quotation->unsetRelation('revisions');
        }

        return $snapshot;
    }

    private function copyContents(Quotation $quotation, TempQuotation $target): void
    {
        $items = $quotation->items()->orderBy('id')->get();
        $copiedItems = [];
        foreach ($items as $item) {
            $copiedItems[$item->id] = $target->items()->create($item->only([
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
        foreach ($quotation->paymentTerms()->get() as $term) {
            $target->paymentTerms()->create($term->only(['term_no', 'percentage', 'description']));
        }
    }
}
