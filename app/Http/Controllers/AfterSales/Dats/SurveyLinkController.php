<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\Ticket;
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

    public function share(Request $request, Ticket $ticket)
    {
        $this->authorizeShare($request, $ticket);

        $validated = $request->validate(['via' => 'required|in:copy']);

        $link = $this->activeLink($ticket);
        $link->update([
            'shared_by_user_id' => $request->user()->id,
            'shared_via' => $validated['via'],
            'shared_at' => Carbon::now('Asia/Jakarta'),
        ]);

        return $this->setJsonResponse('Survey link share recorded', ['data' => $this->payload($request, $ticket->refresh())]);
    }

    public function regenerate(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $validated = $request->validate(['reason' => 'required|string|max:255']);

        $old = $ticket->surveyLink()->first();
        abort_if(! $old || $old->status !== SatisfactionLink::STATUS_ACTIVE, 422, 'There is no live survey link to regenerate.');

        DB::transaction(function () use ($ticket, $old, $validated, $request) {
            $new = SatisfactionLink::issue($ticket, $request->user()->id);
            $old->update([
                'status' => SatisfactionLink::STATUS_REPLACED,
                'replaced_by_id' => $new->id,
                'regenerate_reason' => $validated['reason'],
            ]);
        });

        return $this->setJsonResponse('Survey link regenerated', ['data' => $this->payload($request, $ticket->refresh())], 201);
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

    private function payload(Request $request, Ticket $ticket): array
    {
        $link = $ticket->surveyLink()->with('sharedBy')->first();
        $isActive = $link?->display_status === SatisfactionLink::STATUS_ACTIVE;
        $phone = $ticket->customer?->phone;

        return [
            'ticket_id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'status' => $link?->display_status ?? SatisfactionLink::STATUS_INACTIVE,
            'expires_at' => $link?->expires_at,
            'url' => $isActive ? $link->url : null,
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

    private function maskPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) < 8 ? null : substr($digits, 0, 4) . '••••' . substr($digits, -4);
    }
}
