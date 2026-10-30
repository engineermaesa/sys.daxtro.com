<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketAgreement;
use App\Models\Aftersales\TicketCycle;
use App\Models\Aftersales\TicketDocumentPublication;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketServiceForm;
use App\Models\Aftersales\TicketSignature;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketDocumentWorkflow
{
    public static function enabled(Ticket $ticket): bool
    {
        return $ticket->workflow_version === Ticket::WORKFLOW_V3;
    }

    public static function status(Ticket $ticket): ?string
    {
        return self::enabled($ticket) ? ($ticket->currentCycle?->documentation_status ?? 'incomplete') : null;
    }

    public static function error(int $http, string $code, string $message, array $extra = []): never
    {
        throw new HttpResponseException(response()->json(['status' => 'error', 'code' => $code, 'message' => $message] + $extra, $http));
    }

    private static function lock(Ticket $ticket): Ticket
    {
        $ticket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
        if (! self::enabled($ticket)) {
            self::error(409, 'legacy_document_workflow', 'This ticket uses the legacy document workflow.');
        }
        abort_if($ticket->progress === TicketLog::STEP_CLOSED, 422, 'This ticket is already closed.');

        return $ticket;
    }

    public static function canSubmit(Ticket $ticket, TicketServiceForm $form, User $user): bool
    {
        if (! self::enabled($ticket) || ! TicketDocuments::isTechnician($user, $ticket) || $form->isLocked()
            || in_array($ticket->progress, [TicketLog::STEP_CLOSED, TicketLog::STEP_RECOMING], true)
            || self::status($ticket) !== 'incomplete') {
            return false;
        }

        return TicketDocuments::completeness($ticket, TicketDocuments::TYPE_FORM, false)['complete'];
    }

    public static function submit(Ticket $ticket, User $user, array $data, Request $request): TicketCycle
    {
        $paths = [];
        try {
            return DB::transaction(function () use ($ticket, $user, $data, $request, &$paths) {
                $ticket = self::lock($ticket);
                abort_unless(TicketDocuments::isTechnician($user, $ticket), 403);
                $cycle = $ticket->currentCycle()->lockForUpdate()->firstOrFail();
                if ($cycle->service_form_submitted_at) {
                    return $cycle;
                }
                if ($cycle->publication()->exists()) {
                    self::error(409, 'cycle_already_published', 'Schedule a new Recoming cycle before submitting this form again.');
                }
                $form = TicketDocuments::document($ticket, TicketDocuments::TYPE_FORM);
                $form = TicketServiceForm::whereKey($form->id)->lockForUpdate()->firstOrFail();
                $fields = array_diff_key($data, ['signatures' => true]);
                if ($fields) {
                    TicketDocuments::saveFormDraft($ticket, $form, $fields, $user);
                }
                $ticket = $ticket->fresh();
                abort_if($ticket->progress === TicketLog::STEP_RECOMING, 422, 'Edit the Service Form to begin Repair in this Recoming cycle.');
                foreach ($data['signatures'] ?? [] as $signature) {
                    $captured = self::sign($ticket, $form, $user, $signature['role'], $signature['signature'], $signature['signer_name'] ?? null, $request);
                    $paths[] = $captured->file_path;
                }
                $check = TicketDocuments::completeness($ticket->fresh(), TicketDocuments::TYPE_FORM, false);
                if (! $check['complete']) {
                    self::error(422, 'service_form_incomplete', $check['summary'], ['errors' => ['service_form' => $check['missing']]]);
                }
                $signedRecoming = $cycle->cycle_no > 1 && $form->signatures()->where('role', 'supervisor')->exists();
                $form->update(['status' => $signedRecoming ? 'saved' : 'awaiting_spv_signature',
                    'saved_at' => $signedRecoming ? now('Asia/Jakarta') : null, 'saved_by_user_id' => $signedRecoming ? $user->id : null]);
                $cycle->update([
                    'documentation_status' => 'awaiting_spv_signature',
                    'service_form_submitted_at' => now('Asia/Jakarta'), 'service_form_submitted_by' => $user->id,
                ]);
                if ($signedRecoming) {
                    app(ServiceDiagnosis::class)->lock($form, $ticket);
                    self::publish($ticket->fresh(), $cycle, $user);
                }

                return $cycle->refresh();
            });
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                if (! TicketSignature::where('file_path', $path)->exists()) {
                    Storage::disk('local')->delete($path);
                }
            }
            throw $e;
        }
    }

    public static function sign(Ticket $ticket, TicketServiceForm|TicketAgreement $doc, User $user, string $role, string $dataUri, ?string $signerName, Request $request): TicketSignature
    {
        $newPath = null;
        try {
            return DB::transaction(function () use ($ticket, $doc, $user, $role, $dataUri, $signerName, $request, &$newPath) {
                $ticket = self::lock($ticket);
                $doc = $doc->newQuery()->whereKey($doc->id)->lockForUpdate()->firstOrFail();
                $type = $doc instanceof TicketServiceForm ? TicketDocuments::TYPE_FORM : TicketDocuments::TYPE_AGREEMENT;
                $cycle = $ticket->currentCycle()->lockForUpdate()->firstOrFail();
                abort_unless(in_array($role, TicketSignature::ROLES, true), 422, 'Invalid signature role.');
                $supervisor = $role === 'supervisor';
                abort_unless($supervisor ? TicketDocuments::isSupervisor($user, $ticket) : TicketDocuments::isTechnician($user, $ticket), 403);
                if ($supervisor && ! $cycle->service_form_submitted_at) {
                    self::error(409, 'service_form_not_submitted', 'The technician must submit the Service Form before SPV can sign.');
                }
                $existing = $doc->signatures()->where('role', $role)->first();
                if ($supervisor && $doc->status === 'saved' && $existing) {
                    return $existing;
                }
                if ($doc->isLocked() && ! ($supervisor && $doc instanceof TicketServiceForm && $doc->status === 'awaiting_spv_signature')) {
                    self::error(409, 'document_locked', 'This document is locked.');
                }
                if ($cycle->publication()->exists()) {
                    self::error(409, 'cycle_already_published', 'This cycle has already been published.');
                }
                if ($supervisor) {
                    $check = TicketDocuments::completeness($ticket, $type, false);
                    if (! $check['complete']) {
                        self::error(422, 'document_incomplete', $check['summary'], ['errors' => [$type => $check['missing']]]);
                    }
                }
                $name = $role === 'customer' ? trim((string) $signerName) : $user->name;
                abort_if(blank($name), 422, 'The signer name is required.');
                $newPath = \App\Models\Aftersales\TicketSatisfaction::storeSignature($ticket->id, $dataUri, 'ticket-signatures');
                if ($existing) {
                    $existing->delete();
                    DB::afterCommit(fn () => Storage::disk('local')->delete($existing->file_path));
                }
                if ($role === 'customer' && $doc instanceof TicketAgreement) {
                    $doc->update(['customer_name' => $name]);
                }
                $signature = $doc->signatures()->create([
                    'role' => $role, 'signer_name' => $name,
                    'signed_by_user_id' => $role === 'customer' ? null : $user->id,
                    'captured_by_user_id' => $supervisor ? null : $user->id,
                    'file_path' => $newPath, 'signed_at' => now('Asia/Jakarta'),
                    'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                ]);
                if (! $supervisor && $doc instanceof TicketServiceForm) {
                    TicketCycleEdits::record($ticket, $user->id);
                }
                if ($supervisor) {
                    $doc->update(['status' => 'saved', 'saved_at' => now('Asia/Jakarta'), 'saved_by_user_id' => $user->id]);
                    if ($doc instanceof TicketServiceForm) {
                        app(ServiceDiagnosis::class)->lock($doc, $ticket);
                    }
                    self::publish($ticket, $cycle, $user);
                }

                return $signature;
            });
        } catch (\Throwable $e) {
            if ($newPath && ! TicketSignature::where('file_path', $newPath)->exists()) {
                Storage::disk('local')->delete($newPath);
            }
            throw $e;
        }
    }

    private static function publish(Ticket $ticket, TicketCycle $cycle, User $user): void
    {
        if ($cycle->publication()->exists()) {
            return;
        }
        $form = $ticket->serviceForm()->firstOrFail();
        $agreement = $ticket->agreement()->firstOrFail();
        if ($form->status !== 'saved' || $agreement->status !== 'saved') {
            return;
        }
        foreach (TicketDocuments::TYPES as $type) {
            $check = TicketDocuments::completeness($ticket, $type);
            if (! $check['complete']) {
                self::error(422, 'document_incomplete', $check['summary']);
            }
        }
        $publication = TicketDocumentPublication::create([
            'ticket_id' => $ticket->id, 'cycle_id' => $cycle->id, 'published_at' => now('Asia/Jakarta'),
        ]);
        $log = $ticket->logs()->create([
            'cycle_id' => $cycle->id, 'step' => TicketLog::STEP_DOCUMENTATION_PUBLISHED,
            'actor_user_id' => $user->id, 'note' => 'Auto: Service Form and Agreement signed by SPV Technician',
        ]);
        $phone = TicketRecomings::current($ticket)?->recoming_type === \App\Models\Aftersales\TicketRecoming::TYPE_PHONE_CALL;
        $link = $phone ? null : SatisfactionLink::issue($ticket, $user->id);
        $publication->update(['ticket_log_id' => $log->id, 'satisfaction_link_id' => $link?->id]);
        $cycle->update(['documentation_status' => 'documentation_published']);
        TicketDocuments::queuePublication($ticket, $publication->id);
    }
}
