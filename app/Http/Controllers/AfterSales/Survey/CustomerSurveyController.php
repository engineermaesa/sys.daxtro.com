<?php

namespace App\Http\Controllers\AfterSales\Survey;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\SatisfactionOtp;
use App\Models\Aftersales\ServiceCustomerPhoneChange;
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
use Illuminate\Support\Str;

class CustomerSurveyController extends Controller
{
    private const FAST_SECONDS = 30;

    public function show(string $token)
    {
        $link = $this->findLink($token);
        $link->load('ticket.customer');
        $lockedUntil = $this->lockedUntil($link);

        return response()->json([
            'status' => 'success',
            'data' => [
                'state' => $lockedUntil ? 'locked' : $link->display_status,
                'locked_until' => $lockedUntil,
                'ticket_code' => $link->ticket->ticket_code,
                'customer' => $link->ticket->customer?->name,
                'phone_masked' => $this->maskPhone($link->ticket->customer?->phone),
            ],
        ]);
    }

    public function sendOtp(Request $request, string $token, WhatsAppSender $whatsapp)
    {
        $link = $this->activeLink($token);
        $this->guardNotLocked($link);

        $phone = $link->ticket->customer?->phone;
        abort_if(blank($phone), 422, 'No WhatsApp number is registered for this customer. Please contact admin.');

        $now = Carbon::now('Asia/Jakarta');
        $last = SatisfactionOtp::where('link_id', $link->id)->latest('id')->first();

        if ($last && $last->sent_at->gt($now->copy()->subSeconds(SatisfactionOtp::RESEND_GAP_SECONDS))) {
            $wait = SatisfactionOtp::RESEND_GAP_SECONDS - (int) $last->sent_at->diffInSeconds($now, true);
            abort(429, "Please wait {$wait} seconds before requesting a new code.");
        }

        $sentLastHour = SatisfactionOtp::where('link_id', $link->id)->where('sent_at', '>=', $now->copy()->subHour())->count();
        abort_if($sentLastHour >= SatisfactionOtp::MAX_SENDS_PER_HOUR, 429, 'Too many code requests. Try again later.');

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $message = "[Daxtro] Kode verifikasi survei layanan untuk tiket {$link->ticket->ticket_code}: {$code}. "
            .'Berlaku '.SatisfactionOtp::CODE_TTL_MINUTES.' menit. Jangan berikan kode ini kepada teknisi atau siapa pun.';

        abort_unless($whatsapp->send($phone, $message), 503, 'Failed to send the WhatsApp code. Please contact admin.');

        $otp = SatisfactionOtp::create([
            'link_id' => $link->id,
            'phone_masked' => $this->maskPhone($phone),
            'code_hash' => hash('sha256', $code),
            'sent_at' => $now,
            'expires_at' => $now->copy()->addMinutes(SatisfactionOtp::CODE_TTL_MINUTES),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Verification code sent',
            'data' => [
                'phone_masked' => $otp->phone_masked,
                'expires_at' => $otp->expires_at,
                'resend_available_at' => $now->copy()->addSeconds(SatisfactionOtp::RESEND_GAP_SECONDS),
            ],
        ]);
    }

    public function verifyOtp(Request $request, string $token)
    {
        $validated = $request->validate(['code' => 'required|digits:6']);

        $link = $this->activeLink($token);
        $this->guardNotLocked($link);

        $now = Carbon::now('Asia/Jakarta');
        $otp = SatisfactionOtp::where('link_id', $link->id)->whereNull('verified_at')->latest('id')->first();

        abort_if(! $otp, 422, 'Request a verification code first.');
        abort_if($otp->expires_at->lt($now), 422, 'The code has expired. Request a new one.');

        if (! hash_equals($otp->code_hash, hash('sha256', $validated['code']))) {
            $otp->attempts++;
            if ($otp->attempts >= SatisfactionOtp::MAX_ATTEMPTS) {
                $otp->locked_until = $now->copy()->addMinutes(SatisfactionOtp::LOCK_MINUTES);
            }
            $otp->save();

            abort(422, $otp->locked_until
                ? 'Too many wrong codes. This link is locked for '.SatisfactionOtp::LOCK_MINUTES.' minutes.'
                : 'Wrong code. '.(SatisfactionOtp::MAX_ATTEMPTS - $otp->attempts).' attempt(s) left.');
        }

        $session = Str::random(64);
        $otp->update([
            'verified_at' => $now,
            'session_hash' => hash('sha256', $session),
            'session_expires_at' => $now->copy()->addMinutes(SatisfactionOtp::SESSION_MINUTES),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Verified',
            'data' => ['session_token' => $session, 'session_expires_at' => $otp->session_expires_at],
        ]);
    }

    public function form(Request $request, string $token)
    {
        $link = $this->activeLink($token);
        $this->verifiedOtp($request, $link);
        $link->load('ticket.customer', 'ticket.technician', 'ticket.visits', 'ticket.workOrders');
        $ticket = $link->ticket;
        $visit = $ticket->visits->sortByDesc('scheduled_at')->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'job_information' => [
                    'ticket_code' => $ticket->ticket_code,
                    'customer' => $ticket->customer?->name,
                    'pic_name' => $ticket->customer?->pic_name,
                    'technician' => $ticket->technician?->name,
                    'service_date' => ($visit?->actual_start ?? $visit?->scheduled_at)?->toDateString(),
                    'wo_number' => $ticket->workOrders->sortByDesc('id')->first()?->wo_number,
                ],
                'aspects' => collect($link->template->questions())->where('type', 'rating')->map(fn ($q) => ['key' => $q['question_key'], 'label' => $q['label']])->values(),
                'template' => ['version' => $link->template->version, 'title' => $link->template->title, 'scale_labels' => $link->template->content['scale_labels'], 'sections' => $link->template->documentSections()],
            ],
        ]);
    }

    public function submit(Request $request, string $token)
    {
        $link = $this->activeLink($token);
        $otp = $this->verifiedOtp($request, $link);
        $responseData = app(SurveyResponses::class)->validate($request, $link->template);
        $validated = $responseData['attributes'];

        $satisfaction = DB::transaction(function () use ($link, $otp, $validated, $responseData, $request) {
            $ticket = Ticket::whereKey($link->ticket_id)->lockForUpdate()->firstOrFail();
            $locked = SatisfactionLink::whereKey($link->id)->lockForUpdate()->first();
            abort_if($locked->display_status !== SatisfactionLink::STATUS_ACTIVE, 422, 'This link can no longer be used.');
            TicketSla::guardSurveyCycle($ticket, $locked->cycle_id);
            $link = $locked;
            $now = Carbon::now('Asia/Jakarta');
            $duration = (int) abs($otp->verified_at->diffInSeconds($now, true));
            $reasons = $this->reviewReasons($ticket, $request, $duration);

            $path = TicketSatisfaction::storeSignature($ticket->id, $validated['signature']);

            $satisfaction = TicketSatisfaction::create(array_merge(
                collect($validated)->except('signature')->all(),
                [
                    'ticket_id' => $ticket->id,
                    ...($link->cycle_id !== null ? ['cycle_id' => $link->cycle_id] : []),
                    'signature_path' => $path,
                    'source' => TicketSatisfaction::SOURCE_LINK,
                    'review_status' => $reasons ? TicketSatisfaction::REVIEW_NEEDS_REVIEW : TicketSatisfaction::REVIEW_NORMAL,
                    'review_reasons' => $reasons ?: null,
                    'otp_verified_at' => $otp->verified_at,
                    'otp_phone_masked' => $otp->phone_masked,
                    'link_created_by_user_id' => $link->created_by_user_id,
                    'shared_via' => $link->shared_via,
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                    'duration_seconds' => $duration,
                    'filled_by_user_id' => null,
                    'filled_at' => $now,
                ]
            ));

            app(SurveyResponses::class)->store($satisfaction, $responseData['answers']);

            SatisfactionLink::markUsedFor($ticket);
            MachineNotWorkingNotification::sendFor($satisfaction);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                ...($link->cycle_id !== null ? ['cycle_id' => $link->cycle_id] : []),
                'step' => TicketLog::STEP_SATISFACTION_SUBMITTED,
                'actor_user_id' => null,
                'note' => 'Submitted by customer via survey link',
            ]);

            return $satisfaction;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Thank you, your assessment has been submitted',
            'data' => ['submitted_at' => $satisfaction->filled_at],
        ], 201);
    }

    private function activeLink(string $token): SatisfactionLink
    {
        $link = $this->findLink($token);
        abort_if($link->display_status !== SatisfactionLink::STATUS_ACTIVE, 410, 'This survey link can no longer be used.');
        $link->load('ticket.customer');

        abort_unless(in_array($link->ticket->progress, [TicketLog::STEP_DOCUMENTATION_PUBLISHED, TicketLog::STEP_SATISFACTION_SUBMITTED], true), 410, 'This survey link is not active yet.');

        return $link;
    }

    private function findLink(string $token): SatisfactionLink
    {
        $link = SatisfactionLink::where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_unless($link->ticket, 410, 'This survey link can no longer be used.');

        return $link;
    }

    private function lockedUntil(SatisfactionLink $link): ?Carbon
    {
        $max = SatisfactionOtp::where('link_id', $link->id)->max('locked_until');
        $until = $max ? Carbon::parse($max) : null;

        return $until && $until->gt(Carbon::now('Asia/Jakarta')) ? $until : null;
    }

    private function guardNotLocked(SatisfactionLink $link): void
    {
        $until = $this->lockedUntil($link);
        if ($until) {
            abort(423, 'This link is locked until '.$until->format('H:i').'. Please try again later.');
        }
    }

    private function verifiedOtp(Request $request, SatisfactionLink $link): SatisfactionOtp
    {
        $session = (string) $request->header('X-Survey-Session');

        $otp = $session === '' ? null : SatisfactionOtp::where('link_id', $link->id)
            ->where('session_hash', hash('sha256', $session))
            ->where('session_expires_at', '>', Carbon::now('Asia/Jakarta'))
            ->first();

        abort_if(! $otp, 401, 'Verification required or session expired. Please verify again.');

        return $otp;
    }

    private function reviewReasons(Ticket $ticket, Request $request, int $duration): array
    {
        $reasons = [];

        if ($duration < self::FAST_SECONDS) {
            $reasons[] = 'fast_completion';
        }

        $recentLogins = DB::table('user_login_audits')
            ->where('user_id', $ticket->assigned_technician_id)
            ->where('logged_in_at', '>=', now()->subHours(24));
        if ($ticket->assigned_technician_id && (
            ($request->ip() && (clone $recentLogins)->where('ip_address', $request->ip())->exists())
            || DB::table('sessions')
                ->where('user_id', $ticket->assigned_technician_id)
                ->where('ip_address', $request->ip())
                ->where('last_activity', '>=', Carbon::now('Asia/Jakarta')->subDay()->timestamp)
                ->exists())) {
            $reasons[] = 'same_network_as_technician';
        }
        if ($ticket->assigned_technician_id && $request->userAgent()
            && (clone $recentLogins)->where('user_agent_hash', hash('sha256', $request->userAgent()))->exists()) {
            $reasons[] = 'same_browser_as_technician';
        }

        if (ServiceCustomerPhoneChange::where('customer_id', $ticket->customer_id)
            ->where('changed_at', '>=', Carbon::now('Asia/Jakarta')->subDay())
            ->exists()) {
            $reasons[] = 'phone_changed_recently';
        }

        return $reasons;
    }

    private function maskPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) < 8 ? null : substr($digits, 0, 4).'••••'.substr($digits, -4);
    }
}
