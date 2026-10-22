<?php

namespace App\Services\Aftersales;

use App\Http\Classes\ActivityLogger;
use App\Jobs\SendTicketDocumentEmail;
use App\Models\Aftersales\DocumentCounter;
use App\Models\Aftersales\RefAgreementTemplate;
use App\Models\Aftersales\RefInspectionField;
use App\Models\Aftersales\RefInspectionTemplate;
use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketAgreement;
use App\Models\Aftersales\TicketDocumentDispatch;
use App\Models\Aftersales\TicketInspection;
use App\Models\Aftersales\TicketInspectionValue;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketPhoto;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\Aftersales\TicketServiceForm;
use App\Models\Aftersales\TicketSignature;
use App\Models\Aftersales\TicketWorkOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketDocuments
{
    public const TYPE_AGREEMENT = 'agreement';

    public const TYPE_FORM = 'service_form';

    public const TYPES = [self::TYPE_AGREEMENT, self::TYPE_FORM];

    public const UNIT_FIELDS = ['capacity', 'refrigerant', 'condenser_type', 'voltage', 'phase', 'hz'];

    public const SERVICE_TYPES = ['preventive', 'repair'];

    public static function ensure(Ticket $ticket): void
    {
        if (! $ticket->assigned_technician_id) {
            return;
        }

        if (! TicketServiceForm::where('ticket_id', $ticket->id)->exists()) {
            $inspectionTemplate = RefInspectionTemplate::active();
            TicketServiceForm::firstOrCreate(['ticket_id' => $ticket->id], ['ref_inspection_template_id' => $inspectionTemplate->id, 'template_version' => $inspectionTemplate->version]);
        }

        if (! TicketAgreement::where('ticket_id', $ticket->id)->exists()) {
            $template = RefAgreementTemplate::active();
            TicketAgreement::create([
                'ticket_id' => $ticket->id,
                'ref_agreement_template_id' => $template->id,
                'template_version' => $template->version,
                'accepted_clauses' => [],
            ]);
        }

        if (! $ticket->workOrders()->exists()) {
            $now = Carbon::now('Asia/Jakarta');
            $sequence = DocumentCounter::nextNumber(DocumentCounter::TYPE_WORK_ORDER, $now);
            $latestVisit = $ticket->visits()->orderByDesc('scheduled_at')->first();

            TicketWorkOrder::create([
                'ticket_id' => $ticket->id,
                'ticket_visit_id' => $latestVisit?->id,
                'wo_number' => 'WO-'.$now->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                'technician_id' => $ticket->assigned_technician_id,
                'issued_at' => $now,
            ]);
        }
    }

    public static function document(Ticket $ticket, string $type): TicketServiceForm|TicketAgreement
    {
        abort_unless(in_array($type, self::TYPES, true), 404);
        self::ensure($ticket);

        $doc = $type === self::TYPE_FORM ? $ticket->serviceForm()->first() : $ticket->agreement()->first();
        abort_if(! $doc, 404, 'No documents yet: assign a technician to the ticket first.');

        return $doc;
    }

    public static function isTechnician(User $user, Ticket $ticket): bool
    {
        return $ticket->assigned_technician_id === $user->id && $user->hasPermission('aftersales.documents.fill');
    }

    public static function isSupervisor(User $user, Ticket $ticket): bool
    {
        return $ticket->supervisor_id === $user->id && $user->hasPermission('aftersales.tickets.manage')
            && (! $ticket->happy_call_required || $user->role?->code === 'supervisor_after_sales');
    }

    public static function isManager(User $user): bool
    {
        return (bool) $user->hasPermission('aftersales.tickets.manage');
    }

    public static function authorizeView(User $user, Ticket $ticket): void
    {
        abort_unless(self::isManager($user) || self::isTechnician($user, $ticket) || ($ticket->assigned_technician_id === $user->id && $user->hasPermission('aftersales.tickets.view-own')), 403);
    }

    public static function can(User $user, Ticket $ticket, TicketServiceForm|TicketAgreement $doc): array
    {
        $draft = ! $doc->isLocked() && $ticket->progress !== TicketLog::STEP_CLOSED;
        $tech = self::isTechnician($user, $ticket);
        $sup = self::isSupervisor($user, $ticket);

        return [
            'fill' => $draft && $tech,
            'sign_technician' => $draft && $tech,
            'sign_customer' => $draft && $tech,
            'sign_supervisor' => $draft && $sup,
            'save' => $draft && ($tech || $sup),
            'unlock' => $doc->isLocked() && self::isManager($user),
            'download_copies' => self::isManager($user)
                ? array_keys(TicketDocumentDispatch::COPY_LABELS)
                : ($tech ? ['technician'] : []),
        ];
    }

    public static function productTypeName(Ticket $ticket): ?string
    {
        return $ticket->customerProduct?->productType?->name;
    }

    public static function formStructure(Ticket $ticket, TicketServiceForm $form): array
    {
        $typeId = $ticket->customerProduct?->ref_product_type_id;
        $inspections = $form->inspections()->with('values.photo')->get()->keyBy('ref_inspection_item_id');

        $groups = [];
        foreach ($form->template->items()->with(['fields', 'group'])->get() as $item) {
            $inspection = $inspections->get($item->id);
            $applicable = $item->appliesToType($typeId === null ? null : (int) $typeId);
            $status = $applicable ? $inspection?->status : 'na';
            $values = $inspection?->values->keyBy('ref_inspection_field_id') ?? collect();

            $fields = $item->fields->map(function (RefInspectionField $field) use ($values, $ticket) {
                $value = $values->get($field->id);
                $photo = $value?->photo;

                return [
                    'id' => $field->id,
                    'key' => $field->key,
                    'label' => $field->label,
                    'type' => $field->type,
                    'unit' => $value?->unit ?? $field->unit,
                    'units' => $field->units ?? [],
                    'min_value' => $field->min_value,
                    'max_value' => $field->max_value,
                    'is_multiple' => $field->is_multiple,
                    'options' => $field->options,
                    'required_when' => $field->required_when,
                    'value' => $field->is_multiple && $value?->value ? json_decode($value->value, true) : $value?->value,
                    'photo_required' => $field->type === RefInspectionField::TYPE_NUMBER,
                    'photo' => $photo ? [
                        'id' => $photo->id,
                        'captured_at' => $photo->captured_at,
                        'url' => route('aftersales.tickets.service-form.photos.show', [$ticket->id, $photo->id], false),
                        'thumbnail_url' => route('aftersales.tickets.service-form.photos.show', [$ticket->id, $photo->id], false).'?thumb=1',
                    ] : null,
                ];
            })->values()->all();

            $groups[$item->group_code]['code'] = $item->group_code;
            $groups[$item->group_code]['title'] = $item->group?->title ?? $item->group_code;
            $groups[$item->group_code]['items'][] = [
                'id' => $item->id,
                'number' => $item->number,
                'name' => $item->name,
                'standard' => $item->standard,
                'applicable' => $applicable,
                'status' => $status,
                'fields' => $fields,
            ];
        }

        return array_values($groups);
    }

    /** @return array{complete: bool, missing: string[], summary: string, counts: array} */
    public static function completeness(Ticket $ticket, string $type): array
    {
        $doc = self::document($ticket, $type);
        $missing = [];
        $counts = [];

        if ($type === self::TYPE_FORM) {
            $product = $ticket->customerProduct;
            $unitMissing = collect(self::UNIT_FIELDS)->filter(fn ($f) => blank($product?->{$f}))->values()->all();
            if (blank($ticket->service_type)) {
                $unitMissing[] = 'service_type';
            }
            $counts['section2_missing'] = $unitMissing;
            if ($unitMissing) {
                $missing[] = 'Machine info incomplete: '.implode(', ', $unitMissing);
            }

            $unfilled = 0;
            $emptyFields = 0;
            $noPhoto = 0;
            foreach (self::formStructure($ticket, $doc) as $group) {
                foreach ($group['items'] as $item) {
                    if (! $item['applicable'] || $item['status'] === 'na') {
                        continue;
                    }
                    if ($item['status'] === null) {
                        $unfilled++;

                        continue;
                    }
                    $checklistValues = collect($item['fields'])->where('type', RefInspectionField::TYPE_CHECKLIST)->flatMap(fn ($f) => is_array($f['value']) ? $f['value'] : [$f['value']])->all();
                    foreach ($item['fields'] as $field) {
                        $required = $field['type'] !== RefInspectionField::TYPE_TEXT
                            || ($field['required_when'] && in_array($field['required_when'], $checklistValues, true));
                        if ($required && blank($field['value'])) {
                            $emptyFields++;
                        } elseif ($field['type'] === RefInspectionField::TYPE_NUMBER && filled($field['value']) && ! $field['photo']) {
                            $noPhoto++;
                        }
                    }
                }
            }
            $counts += ['items_unfilled' => $unfilled, 'fields_empty' => $emptyFields, 'photos_missing' => $noPhoto];
            $unfilled && $missing[] = "{$unfilled} inspection item(s) have no status";
            $emptyFields && $missing[] = "{$emptyFields} field(s) are empty";
            $noPhoto && $missing[] = "{$noPhoto} number field(s) have no photo";
            $missing = array_merge($missing, app(ServiceDiagnosis::class)->missing($doc));
        } else {
            $keys = $doc->template->clauseKeys();
            $unchecked = count(array_diff($keys, $doc->accepted_clauses ?? []));
            $counts['clauses_unchecked'] = $unchecked;
            $unchecked && $missing[] = "{$unchecked} clause(s) are not checked";
            blank($doc->customer_name) && $missing[] = 'Customer name is missing';
        }

        $signed = $doc->signatures()->pluck('role')->all();
        $unsigned = array_values(array_diff(TicketSignature::ROLES, $signed));
        $counts['signatures_missing'] = $unsigned;
        foreach ($unsigned as $role) {
            $missing[] = ucfirst($role).' signature is missing';
        }

        return [
            'complete' => $missing === [],
            'missing' => $missing,
            'summary' => $missing ? implode('; ', $missing) : 'Complete',
            'counts' => $counts,
        ];
    }

    public static function signatureSummary(TicketServiceForm|TicketAgreement $doc, Ticket $ticket): array
    {
        $type = $doc instanceof TicketServiceForm ? self::TYPE_FORM : self::TYPE_AGREEMENT;
        $byRole = $doc->signatures()->get()->keyBy('role');

        return collect(TicketSignature::ROLES)->mapWithKeys(fn ($role) => [$role => $byRole->has($role) ? [
            'signer_name' => $byRole[$role]->signer_name,
            'signed_at' => $byRole[$role]->signed_at,
            'url' => route('aftersales.tickets.documents.signature', [$ticket->id, $type, $role], false),
        ] : null])->all();
    }

    public static function saveFormDraft(Ticket $ticket, TicketServiceForm $form, array $data, User $user): void
    {
        abort_if($form->isLocked(), 422, 'The Service Form is saved and locked. Unlock it first.');
        abort_if($ticket->progress === TicketLog::STEP_CLOSED, 422, 'This ticket is already closed.');

        DB::transaction(function () use ($ticket, $form, $data, $user) {
            $form = TicketServiceForm::whereKey($form->id)->lockForUpdate()->firstOrFail();
            abort_if($form->isLocked(), 422, 'The Service Form is saved and locked.');
            if (array_key_exists('diagnosis', $data)) {
                app(ServiceDiagnosis::class)->save($ticket, $form, $data['diagnosis'], $user);
            }
            if (array_key_exists('service_type', $data)) {
                $ticket->update(['service_type' => $data['service_type'], 'updated_by' => $user->id]);
            }

            if (isset($data['unit']) && $ticket->customerProduct) {
                $ticket->customerProduct->update(array_intersect_key($data['unit'], array_flip(self::UNIT_FIELDS)));
            }

            $typeId = $ticket->customerProduct?->ref_product_type_id;
            $items = $form->template->items()->with('fields')->get()->keyBy('number');

            foreach ($data['inspections'] ?? [] as $entry) {
                $item = $items->get($entry['item_number']);
                abort_if(! $item, 422, "Unknown inspection item {$entry['item_number']}.");

                $applicable = $item->appliesToType($typeId === null ? null : (int) $typeId);
                $inspection = TicketInspection::firstOrCreate(['ticket_service_form_id' => $form->id, 'ref_inspection_item_id' => $item->id]);
                $inspection->update(['status' => $applicable ? ($entry['status'] ?? $inspection->status) : 'na']);
                if (! $applicable) {
                    continue;
                }

                foreach ($entry['values'] ?? [] as $key => $raw) {
                    $field = $item->fields->firstWhere('key', $key);
                    abort_if(! $field, 422, "Item {$item->number}: unknown field '{$key}'.");
                    self::storeValue($inspection, $field, $raw);
                }
            }

            if (! $form->first_draft_at) {
                $form->update(['first_draft_at' => Carbon::now('Asia/Jakarta')]);

                if (in_array($ticket->progress, [TicketLog::STEP_ASSIGNED, TicketLog::STEP_ON_SITE], true)) {
                    TicketLog::create([
                        'ticket_id' => $ticket->id,
                        'step' => TicketLog::STEP_REPAIR,
                        'actor_user_id' => $user->id,
                        'note' => 'Auto: first Service Form draft saved',
                    ]);
                }
            }
        });
    }

    private static function storeValue(TicketInspection $inspection, RefInspectionField $field, mixed $raw): void
    {
        if ($field->is_multiple) {
            abort_unless($raw === null || is_array($raw), 422, "'{$field->label}' needs a list of options.");
            abort_unless(collect($raw ?? [])->every(fn ($option) => is_string($option)), 422, 'Checklist options must be strings.');
            $options = array_values(array_unique($raw ?? []));
            abort_if(array_diff($options, $field->options ?? []), 422, "'{$field->label}' contains an unknown option.");
            TicketInspectionValue::updateOrCreate(['ticket_inspection_id' => $inspection->id, 'ref_inspection_field_id' => $field->id], ['value' => count($options) ? json_encode($options) : null]);

            return;
        }
        $unit = $field->unit;
        if (is_array($raw)) {
            abort_unless($field->type === RefInspectionField::TYPE_NUMBER, 422, 'Invalid field value.');
            $unit = $raw['unit'] ?? $field->unit;
            $raw = $raw['value'] ?? null;
        }
        abort_unless($raw === null || is_scalar($raw), 422, 'Invalid field value.');
        if ($field->type === RefInspectionField::TYPE_NUMBER && $unit !== null) {
            abort_unless(in_array($unit, $field->units ?? ($field->unit ? [$field->unit] : []), true), 422, 'Invalid measurement unit.');
        }
        $value = is_string($raw) ? trim($raw) : $raw;
        $value = ($value === '' || $value === null) ? null : (string) $value;

        if ($value !== null) {
            if ($field->type === RefInspectionField::TYPE_NUMBER) {
                $value = str_replace(',', '.', $value);
                abort_unless(is_numeric($value), 422, "'{$field->label}' must be a number.");
                abort_unless(is_finite((float) $value), 422, "'{$field->label}' must be a finite number.");
                abort_if(($field->min_value !== null && (float) $value < (float) $field->min_value) || ($field->max_value !== null && (float) $value > (float) $field->max_value), 422, "'{$field->label}' is outside the allowed range.");
            } elseif ($field->type === RefInspectionField::TYPE_CHECKLIST) {
                abort_unless(in_array($value, $field->options ?? [], true), 422, "'{$field->label}' must be one of: ".implode(', ', $field->options ?? []));
            }
        }

        $row = TicketInspectionValue::firstOrNew(['ticket_inspection_id' => $inspection->id, 'ref_inspection_field_id' => $field->id]);

        if ($row->exists && $field->type === RefInspectionField::TYPE_NUMBER && ((string) $row->value !== (string) $value || ($row->unit ?? $field->unit) !== $unit)) {
            self::deletePhotos($row);
        }

        $row->value = $value;
        $row->unit = $field->type === RefInspectionField::TYPE_NUMBER ? $unit : null;
        $row->save();
    }

    public static function saveAgreementDraft(Ticket $ticket, TicketAgreement $agreement, array $data): void
    {
        abort_if($agreement->isLocked(), 422, 'The Service Agreement is saved and locked. Unlock it first.');
        abort_if($ticket->progress === TicketLog::STEP_CLOSED, 422, 'This ticket is already closed.');

        $valid = $agreement->template->clauseKeys();
        if (array_key_exists('accepted_clauses', $data)) {
            $unknown = array_diff($data['accepted_clauses'], $valid);
            abort_if($unknown, 422, 'Unknown clause(s): '.implode(', ', $unknown));
            $agreement->accepted_clauses = array_values(array_intersect($valid, $data['accepted_clauses']));
        }
        if (array_key_exists('customer_name', $data)) {
            $agreement->customer_name = $data['customer_name'];
        }
        $agreement->save();
    }

    public static function deletePhotos(TicketInspectionValue $value): void
    {
        TicketPhoto::where('ticket_inspection_value_id', $value->id)->get()->each(function (TicketPhoto $p) {
            Storage::disk('local')->delete(array_filter([$p->file_path, $p->thumbnail_path]));
            $p->delete();
        });
    }

    public static function attachPhoto(TicketInspectionValue $value, UploadedFile $file, Ticket $ticket, ?int $userId): TicketPhoto
    {
        abort_if(blank($value->value), 422, 'Enter the value before attaching a photo.');

        $dir = 'ticket-inspection-photos/'.$ticket->id;
        $path = $file->store($dir, 'local');
        $thumb = self::makeThumbnail($file, $dir);

        self::deletePhotos($value);

        return TicketPhoto::create([
            'ticket_id' => $ticket->id,
            'ticket_inspection_value_id' => $value->id,
            'category' => TicketPhoto::CATEGORY_INSPECTION,
            'sequence' => (TicketPhoto::withTrashed()->where('ticket_id', $ticket->id)->where('category', TicketPhoto::CATEGORY_INSPECTION)->max('sequence') ?? 0) + 1,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'captured_at' => Carbon::now('Asia/Jakarta'),
            'thumbnail_path' => $thumb,
        ]);
    }

    private static function makeThumbnail(UploadedFile $file, string $dir): ?string
    {
        $src = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $src) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $nw = min(400, $w);
        $nh = (int) round($h * $nw / max(1, $w));
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($dst, null, 80);
        $path = $dir.'/thumb-'.Str::uuid().'.jpg';
        Storage::disk('local')->put($path, (string) ob_get_clean());

        return $path;
    }

    public static function sign(Ticket $ticket, TicketServiceForm|TicketAgreement $doc, User $user, string $role, string $dataUri, ?string $signerName, Request $request): TicketSignature
    {
        abort_unless(in_array($role, TicketSignature::ROLES, true), 422, 'Invalid signature role.');
        abort_if($doc->isLocked(), 422, 'The document is saved and locked. Unlock it first.');
        abort_if($ticket->progress === TicketLog::STEP_CLOSED, 422, 'This ticket is already closed.');

        if ($role === 'supervisor') {
            abort_unless(self::isSupervisor($user, $ticket), 403, 'Only the ticket supervisor can sign as supervisor.');
            $name = $user->name;
            $signedBy = $user->id;
            $capturedBy = null;
        } else {
            abort_unless(self::isTechnician($user, $ticket), 403, 'Only the assigned technician can sign on this device.');
            $signedBy = $role === 'technician' ? $user->id : null;
            $capturedBy = $user->id;
            $name = $role === 'technician' ? $user->name : trim((string) $signerName);
            abort_if(blank($name), 422, 'The customer name is required.');
        }

        $path = TicketSatisfaction::storeSignature($ticket->id, $dataUri, 'ticket-signatures');
        $existing = $doc->signatures()->where('role', $role)->first();
        if ($existing) {
            Storage::disk('local')->delete($existing->file_path);
            $existing->delete();
        }

        if ($role === 'customer' && $doc instanceof TicketAgreement) {
            $doc->update(['customer_name' => $name]);
        }

        return $doc->signatures()->create([
            'role' => $role,
            'signer_name' => $name,
            'signed_by_user_id' => $signedBy,
            'captured_by_user_id' => $capturedBy,
            'file_path' => $path,
            'signed_at' => Carbon::now('Asia/Jakarta'),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);
    }

    /** @return array{document: TicketServiceForm|TicketAgreement, dispatches: TicketDocumentDispatch[], warnings: string[]} */
    public static function save(Ticket $ticket, string $type, User $user): array
    {
        $doc = self::document($ticket, $type);

        abort_unless(self::isTechnician($user, $ticket) || self::isSupervisor($user, $ticket), 403, 'Only the assigned technician or the ticket supervisor can save.');
        abort_if($doc->isLocked(), 422, 'This document is already saved.');
        abort_if($ticket->progress === TicketLog::STEP_CLOSED, 422, 'This ticket is already closed.');
        abort_if($ticket->progress === TicketLog::STEP_RECOMING, 422, 'The ticket is in Recoming until the follow-up visit.');

        $check = self::completeness($ticket, $type);
        abort_if(! $check['complete'], 422, 'Cannot save yet: '.$check['summary']);

        $result = DB::transaction(function () use ($ticket, $type, $doc, $user) {
            $doc = $doc->newQuery()->whereKey($doc->id)->lockForUpdate()->firstOrFail();
            abort_if($doc->isLocked(), 422, 'This document is already saved.');
            if ($type === self::TYPE_FORM) {
                $missing = app(ServiceDiagnosis::class)->missing($doc);
                abort_if($missing, 422, implode('; ', $missing));
                app(ServiceDiagnosis::class)->lock($doc);
            }
            $doc->update(['status' => 'saved', 'saved_at' => Carbon::now('Asia/Jakarta'), 'saved_by_user_id' => $user->id]);

            self::publishDocumentationIfBothSaved($ticket, $user);

            return self::queueDispatches($ticket, $type);
        });

        foreach ($result['dispatches'] as $dispatch) {
            if ($dispatch->status === 'queued') {
                SendTicketDocumentEmail::dispatch($dispatch->id);
            }
        }

        return ['document' => $doc->refresh()] + $result;
    }

    private static function publishDocumentationIfBothSaved(Ticket $ticket, User $user): void
    {
        $bothSaved = TicketServiceForm::where('ticket_id', $ticket->id)->where('status', 'saved')->exists()
            && TicketAgreement::where('ticket_id', $ticket->id)->where('status', 'saved')->exists();

        $lastRecoming = $ticket->logs()->where('step', TicketLog::STEP_RECOMING)->max('id');
        $alreadyPublished = $ticket->logs()
            ->where('step', TicketLog::STEP_DOCUMENTATION_PUBLISHED)
            ->when($lastRecoming, fn ($q) => $q->where('id', '>', $lastRecoming))
            ->exists();

        if (! $bothSaved || $alreadyPublished) {
            return;
        }

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'step' => TicketLog::STEP_DOCUMENTATION_PUBLISHED,
            'actor_user_id' => $user->id,
            'note' => 'Auto: Service Agreement and Service Form saved',
        ]);
        SatisfactionLink::issue($ticket, $user->id);
    }

    public static function unlock(Ticket $ticket, string $type, User $user): TicketServiceForm|TicketAgreement
    {
        abort_unless(self::isManager($user), 403);
        $doc = self::document($ticket, $type);
        abort_if(! $doc->isLocked(), 422, 'This document is not saved.');

        DB::transaction(function () use ($doc, $user, $type, $ticket) {
            $doc->signatures()->get()->each(function (TicketSignature $s) {
                Storage::disk('local')->delete($s->file_path);
                $s->delete();
            });
            $doc->update(['status' => 'draft', 'saved_at' => null, 'saved_by_user_id' => null, 'unlocked_at' => Carbon::now('Asia/Jakarta'), 'unlocked_by_user_id' => $user->id]);

            ActivityLogger::writeLog('aftersales.document.unlock', "Unlocked {$type} of ticket {$ticket->ticket_code}", $doc, [], $user);
        });

        return $doc->refresh();
    }

    private static function queueDispatches(Ticket $ticket, string $type): array
    {
        $ticket->loadMissing('customer', 'creator', 'technician');
        $emails = [
            'customer' => $ticket->customer?->email,
            'aftersales' => $ticket->creator?->email,
            'technician' => $ticket->technician?->email,
        ];

        $rows = [];
        $warnings = [];
        foreach ($emails as $role => $email) {
            $row = TicketDocumentDispatch::create([
                'ticket_id' => $ticket->id,
                'document_type' => $type,
                'recipient_role' => $role,
                'recipient_email' => $email,
                'copy_label' => TicketDocumentDispatch::COPY_LABELS[$role],
                'status' => blank($email) ? 'skipped' : 'queued',
                'error' => blank($email) ? 'No email address for this recipient.' : null,
            ]);
            blank($email) && $warnings[] = ucfirst($role).' has no email address; email skipped.';
            $rows[] = $row;
        }

        return ['dispatches' => $rows, 'warnings' => $warnings];
    }

    public static function resend(TicketDocumentDispatch $old): TicketDocumentDispatch
    {
        $ticket = $old->ticket;
        $email = match ($old->recipient_role) {
            'customer' => $ticket->customer?->email,
            'aftersales' => $ticket->creator?->email,
            'technician' => $ticket->technician?->email,
        };
        abort_if(blank($email), 422, 'No email address for this recipient.');

        $row = TicketDocumentDispatch::create([
            'ticket_id' => $ticket->id,
            'document_type' => $old->document_type,
            'recipient_role' => $old->recipient_role,
            'recipient_email' => $email,
            'copy_label' => $old->copy_label,
            'status' => 'queued',
        ]);
        SendTicketDocumentEmail::dispatch($row->id);

        return $row;
    }
}
