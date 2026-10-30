<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketSatisfaction;
use App\Notifications\Aftersales\MachineNotWorkingNotification;
use App\Services\Aftersales\SurveyResponses;
use App\Services\Aftersales\TicketSla;
use App\Services\WhatsAppSender;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyLinkController extends Controller
{
    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeShare($request, $ticket);

        return response()->json(['status' => 'success', 'data' => $this->payload($request, $ticket)]);
    }

    public function share(Request $request, Ticket $ticket, WhatsAppSender $whatsapp)
    {
        $this->authorizeShare($request, $ticket);

        $validated = $request->validate(['via' => 'required|in:copy,whatsapp']);

        $link = $this->activeLink($ticket);

        if ($validated['via'] === 'whatsapp') {
            $phone = $ticket->customer?->phone;
            abort_if(blank($phone), 422, 'No WhatsApp number is registered for this customer.');
            abort_unless($whatsapp->send($phone, $this->whatsappMessage($ticket, $link)), 503, 'Failed to send the WhatsApp message.');
        }

        $link->update([
            'shared_by_user_id' => $request->user()->id,
            'shared_via' => $validated['via'],
            'shared_at' => Carbon::now('Asia/Jakarta'),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Survey link share recorded', 'data' => $this->payload($request, $ticket->refresh())]);
    }

    public function regenerate(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $validated = $request->validate(['reason' => 'required|string|max:255']);

        $old = $ticket->surveyLink()->first();
        abort_if(! $old || $old->status !== SatisfactionLink::STATUS_ACTIVE, 422, 'There is no live survey link to regenerate.');

        DB::transaction(function () use ($ticket, $old, $validated, $request) {
            $old = SatisfactionLink::whereKey($old->id)->lockForUpdate()->firstOrFail();
            abort_unless($old->status === SatisfactionLink::STATUS_ACTIVE, 409, 'This link was already replaced. Reload the page.');
            $new = SatisfactionLink::issue($ticket, $request->user()->id, $old->template);
            $old->update([
                'status' => SatisfactionLink::STATUS_REPLACED,
                'replaced_by_id' => $new->id,
                'regenerate_reason' => $validated['reason'],
            ]);
        });

        return response()->json(['status' => 'success', 'message' => 'Survey link regenerated', 'data' => $this->payload($request, $ticket->refresh())], 201);
    }

    public function enterOnBehalf(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.satisfaction.submit'), 403);

        $activeLink = $this->activeLink($ticket);
        $responseData = app(SurveyResponses::class)->validate($request, $activeLink->template, true);
        $validated = $responseData['attributes'];

        $satisfaction = DB::transaction(function () use ($ticket, $validated, $responseData, $activeLink, $request) {
            $ticket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            // single use: re-check under lock so a double submit can't create two responses
            $link = SatisfactionLink::where('ticket_id', $ticket->id)->latest('id')->lockForUpdate()->first();
            abort_if(! $link || $link->display_status !== SatisfactionLink::STATUS_ACTIVE, 422, 'Survey link is not active.');
            abort_unless($link->id === $activeLink->id, 409, 'The survey link changed. Reload the form.');
            TicketSla::guardSurveyCycle($ticket, $link->cycle_id);

            $now = Carbon::now('Asia/Jakarta');
            $user = $request->user();

            $satisfaction = TicketSatisfaction::create(array_merge(
                collect($validated)->except(['reason', 'signature'])->all(),
                [
                    'ticket_id' => $ticket->id,
                    ...($link->cycle_id !== null ? ['cycle_id' => $link->cycle_id] : []),
                    'signature_path' => filled($validated['signature'] ?? null)
                        ? TicketSatisfaction::storeSignature($ticket->id, $validated['signature'])
                        : null,
                    'source' => TicketSatisfaction::SOURCE_ADMIN,
                    'review_status' => TicketSatisfaction::REVIEW_NEEDS_REVIEW,
                    'review_reasons' => ['admin_input'],
                    'admin_input_reason' => $validated['reason'],
                    'filled_by_user_id' => $user->id,
                    'filled_at' => $now,
                ]
            ));

            app(SurveyResponses::class)->store($satisfaction, $responseData['answers']);

            SatisfactionLink::markUsedFor($ticket);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                ...($link->cycle_id !== null ? ['cycle_id' => $link->cycle_id] : []),
                'step' => TicketLog::STEP_SATISFACTION_SUBMITTED,
                'actor_user_id' => $user->id,
                'note' => 'Entered on behalf of customer: '.$validated['reason'],
            ]);

            MachineNotWorkingNotification::sendFor($satisfaction);

            return $satisfaction;
        });

        return $this->setJsonResponse('Survey response entered on behalf of customer', ['data' => $satisfaction], 201);
    }

    private function authorizeShare(Request $request, Ticket $ticket): void
    {
        $user = $request->user();

        abort_unless(
            $user?->hasPermission('aftersales.tickets.manage')
            || ($user?->hasPermission('aftersales.survey-link.share') && $ticket->assigned_technician_id === $user->id),
            403
        );
    }

    private function activeLink(Ticket $ticket): SatisfactionLink
    {
        $link = $ticket->surveyLink()->first();

        abort_if(! $link || $link->display_status !== SatisfactionLink::STATUS_ACTIVE, 422, 'Survey link is not active.');

        return $link;
    }

    private function whatsappMessage(Ticket $ticket, SatisfactionLink $link): string
    {
        $name = $ticket->customer?->pic_name ?: $ticket->customer?->name;

        return "Halo {$name}, terima kasih telah menggunakan layanan Daxtro. "
            ."Mohon luangkan waktu mengisi survei kepuasan untuk tiket {$ticket->ticket_code} melalui tautan berikut: {$link->url}";
    }

    private function payload(Request $request, Ticket $ticket): array
    {
        $link = $ticket->surveyLink()->with('sharedBy')->first();
        $isActive = $link?->display_status === SatisfactionLink::STATUS_ACTIVE;
        $phone = $ticket->customer?->phone;

        return [
            'ticket_id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'template_version' => $link?->template?->version,
            'template' => $isActive ? ['version' => $link->template->version, 'title' => $link->template->title, 'scale_labels' => $link->template->content['scale_labels'], 'sections' => $link->template->documentSections()] : null,
            'status' => $link?->display_status ?? SatisfactionLink::STATUS_INACTIVE,
            'filled' => $link?->status === SatisfactionLink::STATUS_USED,
            'expires_at' => $link?->expires_at,
            'url' => $isActive ? $link->url : null,
            'whatsapp_message' => $isActive ? $this->whatsappMessage($ticket, $link) : null,
            'phone_masked' => $this->maskPhone($phone),
            'phone_missing' => blank($phone),
            'shared_by' => $link?->sharedBy?->name,
            'shared_via' => $link?->shared_via,
            'shared_at' => $link?->shared_at,
            'can_share' => $isActive,
            'can_regenerate' => (bool) $request->user()->hasPermission('aftersales.tickets.manage')
                && in_array($link?->display_status, [SatisfactionLink::STATUS_ACTIVE, 'expired'], true),
        ];
    }

    /** 0812••••1234: keep first 4 and last 4 digits. */
    private function maskPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) < 8 ? null : substr($digits, 0, 4).'••••'.substr($digits, -4);
    }
}
