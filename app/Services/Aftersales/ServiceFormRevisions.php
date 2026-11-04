<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\ServiceCustomerProduct;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketServiceForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceFormRevisions
{
    public static function validate(Request $request, Ticket $ticket): ?string
    {
        if (! TicketDocumentWorkflow::enabled($ticket)) {
            return null;
        }

        return $request->validate(['revision' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']])['revision'];
    }

    public static function lockMaterial(Ticket $ticket): ?TicketServiceForm
    {
        $form = TicketServiceForm::where('ticket_id', $ticket->id)->lockForUpdate()->first();
        $product = $ticket->customer_product_id
            ? ServiceCustomerProduct::whereKey($ticket->customer_product_id)->lockForUpdate()->first() : null;
        $ticket->setRelation('customerProduct', $product);

        return $form;
    }

    public static function advance(Ticket $ticket): void
    {
        DB::table('ticket_service_forms')->where('ticket_id', $ticket->id)->whereNull('deleted_at')->increment('sync_version');
    }

    public static function token(Ticket $ticket, TicketServiceForm $form): ?string
    {
        if (! TicketDocumentWorkflow::enabled($ticket)) {
            return null;
        }
        $photoFields = ['id', 'file_path', 'thumbnail_path', 'caption', 'captured_at', 'locked_at'];
        $inspections = $form->inspections()->with('values.photo')->orderBy('id')->get()->map(fn ($inspection) => [
            'id' => $inspection->id, 'item_id' => $inspection->ref_inspection_item_id, 'status' => $inspection->status,
            'values' => $inspection->values->sortBy('id')->values()->map(fn ($value) => [
                'id' => $value->id, 'field_id' => $value->ref_inspection_field_id, 'value' => $value->value,
                'unit' => $value->unit, 'photo' => $value->photo?->only($photoFields),
            ])->all(),
        ])->all();
        $state = [
            'format' => 'service-form-v1',
            'form' => $form->only(['id', 'sync_version', 'status', 'ref_inspection_template_id', 'template_version', 'saved_at', 'unlocked_at']),
            'ticket' => $ticket->only(['id', 'workflow_version', 'customer_id', 'customer_product_id', 'assigned_technician_id', 'supervisor_id', 'service_type']),
            'progress' => $ticket->progress,
            'cycle' => $ticket->currentCycle?->only(['id', 'cycle_no', 'documentation_status', 'service_form_submitted_at']),
            'latest_log' => $ticket->logs()->latest('id')->first()?->only(['id', 'step']),
            'recoming' => TicketRecomings::current($ticket)?->only(['id', 'cycle_id', 'recoming_type', 'planned_visit_at', 'planned_call_at']),
            'visit' => $ticket->visits()->orderByDesc('scheduled_at')->orderByDesc('id')->first()?->only(['id', 'cycle_id', 'scheduled_at', 'actual_start', 'actual_end']),
            'unit' => $ticket->customerProduct?->only(array_merge(['id', 'ref_product_type_id'], TicketDocuments::UNIT_FIELDS)),
            'inspections' => $inspections,
            'findings' => $form->diagnosisEntries()->reorder('id')->get()->map(fn ($entry) => $entry->only([
                'id', 'cycle_id', 'field', 'visit_no', 'entry_date', 'visit_time', 'name', 'body', 'visit_notes', 'locked_at',
            ]))->all(),
            'photos' => $form->diagnosisPhotos()->reorder('id')->get()->map(fn ($photo) => $photo->only(array_merge($photoFields, ['diagnosis_field'])))->all(),
            'signatures' => $form->signatures()->orderBy('id')->get()->map(fn ($signature) => $signature->only(['id', 'role', 'signer_name', 'file_path', 'signed_at']))->all(),
        ];

        return hash('sha256', json_encode($state, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }

    public static function guard(Ticket $ticket, ?TicketServiceForm $form, string $expected): void
    {
        $current = $form ? self::token($ticket, $form) : null;
        if ($current === null || ! hash_equals($current, $expected)) {
            TicketDocumentWorkflow::error(409, 'service_form_conflict',
                'The Service Form or ticket workflow changed. Keep your local draft and load the latest form before submitting again.', [
                    'current_revision' => $current, 'reload_url' => '/api/aftersales/tickets/'.$ticket->id.'/service-form',
                ]);
        }
    }
}
