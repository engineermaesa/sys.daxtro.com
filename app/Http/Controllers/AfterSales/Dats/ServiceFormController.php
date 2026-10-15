<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\RefInspectionField;
use App\Models\Aftersales\RefInspectionItem;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketInspection;
use App\Models\Aftersales\TicketInspectionValue;
use App\Models\Aftersales\TicketPhoto;
use App\Models\Aftersales\TicketPhotoToken;
use App\Services\Aftersales\TicketDocuments;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceFormController extends Controller
{
    public function show(Request $request, Ticket $ticket)
    {
        TicketDocuments::authorizeView($request->user(), $ticket);

        $form = TicketDocuments::document($ticket, TicketDocuments::TYPE_FORM);
        $ticket->load(['customer', 'customerProduct.productType', 'technician', 'supervisor', 'visits']);
        $product = $ticket->customerProduct;
        $visit = $ticket->visits->sortByDesc('scheduled_at')->first();

        return response()->json(['status' => 'success', 'data' => [
            'ticket_id' => $ticket->id,
            'number' => $ticket->ticket_code,
            'status' => $form->status,
            'locked' => $form->isLocked(),
            'saved_at' => $form->saved_at,
            'template' => $form->template->only(['version', 'title', 'diagnosis_section_title', 'diagnosis_fields']),
            'section1' => [
                'customer' => $ticket->customer?->name,
                'pic_name' => $ticket->customer?->pic_name,
                'address' => $ticket->customer?->address,
                'phone' => $ticket->customer?->phone,
                'technician' => $ticket->technician?->name,
                'supervisor' => $ticket->supervisor?->name,
                'service_date' => ($visit?->actual_start ?? $visit?->scheduled_at)?->toDateString(),
            ],
            'section2' => [
                'machine_type' => $product?->productType?->name,
                'serial_number' => $product?->serial_number,
                'service_type' => $ticket->service_type,
                'capacity' => $product?->capacity,
                'refrigerant' => $product?->refrigerant,
                'condenser_type' => $product?->condenser_type,
                'voltage' => $product?->voltage,
                'phase' => $product?->phase,
                'hz' => $product?->hz,
            ],
            'groups' => TicketDocuments::formStructure($ticket, $form),
            'diagnosis' => app(\App\Services\Aftersales\ServiceDiagnosis::class)->structure($ticket, $form),
            'signatures' => TicketDocuments::signatureSummary($form, $ticket),
            'completeness' => TicketDocuments::completeness($ticket, TicketDocuments::TYPE_FORM),
            'can' => TicketDocuments::can($request->user(), $ticket, $form),
        ]]);
    }

    public function saveDraft(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless(TicketDocuments::isTechnician($user, $ticket), 403, 'Only the assigned technician can fill the Service Form.');

        $validated = $request->validate([
            'service_type' => 'nullable|in:' . implode(',', TicketDocuments::SERVICE_TYPES),
            'unit' => 'nullable|array',
            'unit.capacity' => 'nullable|string|max:100',
            'unit.refrigerant' => 'nullable|string|max:100',
            'unit.condenser_type' => 'nullable|string|max:100',
            'unit.voltage' => 'nullable|string|max:100',
            'unit.phase' => 'nullable|string|max:100',
            'unit.hz' => 'nullable|string|max:100',
            'inspections' => 'nullable|array',
            'inspections.*.item_number' => 'required|integer|min:1',
            'inspections.*.status' => 'nullable|in:' . implode(',', TicketInspection::STATUSES),
            'inspections.*.values' => 'nullable|array',
            'diagnosis' => 'sometimes|array|max:3',
            'diagnosis.*.id' => 'nullable|integer',
            'diagnosis.*.field' => 'required|in:problem,cause,action|distinct',
            'diagnosis.*.name' => 'nullable|string|max:255',
            'diagnosis.*.body' => 'nullable|string',
            'diagnosis.*.visit_notes' => 'nullable|string',
        ]);

        $form = TicketDocuments::document($ticket, TicketDocuments::TYPE_FORM);
        TicketDocuments::saveFormDraft($ticket, $form, $validated, $user);

        return $this->show($request, $ticket->refresh());
    }

    public function photoLink(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless(TicketDocuments::isTechnician($user, $ticket), 403);

        $validated = $request->validate([
            'item_number' => 'required|integer|min:1',
            'field_key' => 'required|string|max:40',
        ]);

        $form = TicketDocuments::document($ticket, TicketDocuments::TYPE_FORM);
        abort_if($form->isLocked(), 422, 'The Service Form is saved and locked. Unlock it first.');

        $item = $form->template->items()->where('number', $validated['item_number'])->firstOrFail();
        $typeId = $ticket->customerProduct?->ref_product_type_id;
        abort_unless($item->appliesToType($typeId === null ? null : (int) $typeId), 422, 'This item is not applicable to this machine type.');
        $field = RefInspectionField::where('item_id', $item->id)->where('key', $validated['field_key'])->first();
        abort_if(! $field || $field->type !== RefInspectionField::TYPE_NUMBER, 422, 'Photos can only be attached to number fields.');

        $inspection = TicketInspection::where('ticket_service_form_id', $form->id)->where('ref_inspection_item_id', $item->id)->first();
        $value = $inspection ? TicketInspectionValue::where('ticket_inspection_id', $inspection->id)->where('ref_inspection_field_id', $field->id)->first() : null;
        abort_if(! $value || blank($value->value), 422, 'Enter and save the value before taking a photo.');

        $token = Str::random(48);
        $link = TicketPhotoToken::create([
            'token_hash' => hash('sha256', $token),
            'ticket_id' => $ticket->id,
            'ticket_inspection_value_id' => $value->id,
            'created_by_user_id' => $user->id,
            'expires_at' => Carbon::now('Asia/Jakarta')->addMinutes(TicketPhotoToken::TTL_MINUTES),
        ]);

        return response()->json(['status' => 'success', 'data' => [
            'url' => url('/capture/' . $token),
            'token' => $token,
            'expires_at' => $link->expires_at,
        ]], 201);
    }

    public function photo(Request $request, Ticket $ticket, TicketPhoto $photo)
    {
        TicketDocuments::authorizeView($request->user(), $ticket);
        abort_unless($photo->ticket_id === $ticket->id && in_array($photo->category, [TicketPhoto::CATEGORY_INSPECTION, TicketPhoto::CATEGORY_DIAGNOSIS], true), 404);

        $path = ($request->boolean('thumb') && $photo->thumbnail_path) ? $photo->thumbnail_path : $photo->file_path;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
