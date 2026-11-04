<?php

namespace Database\Seeders;

use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\ServiceCustomer;
use App\Models\Aftersales\ServiceCustomerProduct;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\Aftersales\TicketVisit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SatisfactionSurveyDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Ticket::where('ticket_code', 'like', 'DEMO-%')->exists()) {
            $this->command?->info('Demo survey data already seeded, skipping.');

            return;
        }

        $actor = User::whereHas('role', fn ($q) => $q->where('code', 'after_sales'))->first() ?? User::first();
        $tech = User::whereHas('role', fn ($q) => $q->where('code', 'technician'))->first() ?? $actor;
        $now = Carbon::now('Asia/Jakarta');

        $customers = collect([
            ['PT Global Dynamics', 'Budi Hartono', '08811478570'], // OTP smoke-test number
            ['CV Heavy Logistics', 'Rina Wijaya', '081234567890'],
            ['PT Maju Bersama', 'Hendra Gunawan', null], // no phone -> "phone_missing" case
        ])->map(fn ($c) => ServiceCustomer::create(['name' => $c[0], 'pic_name' => $c[1], 'phone' => $c[2]]));

        $ticket = function (int $i, ServiceCustomer $customer, array $steps, int $daysAgo) use ($tech, $actor, $now) {
            $product = ServiceCustomerProduct::create(['customer_id' => $customer->id]);
            $t = Ticket::create([
                'ticket_code' => "DEMO-{$i}",
                'customer_id' => $customer->id,
                'customer_product_id' => $product->id,
                'category' => 'mechanical',
                'priority' => 'medium',
                'description' => 'Demo ticket for satisfaction survey',
                'sla_value' => 3,
                'sla_unit' => 'day',
                'assigned_technician_id' => $tech->id,
            ]);
            $visitAt = $now->copy()->subDays($daysAgo)->startOfDay()->addHours(9);
            TicketVisit::create(['ticket_id' => $t->id, 'scheduled_at' => $visitAt, 'actual_start' => $visitAt]);
            foreach ($steps as $step) {
                TicketLog::create(['ticket_id' => $t->id, 'step' => $step, 'actor_user_id' => $actor->id]);
            }

            return $t;
        };

        $done = [TicketLog::STEP_PUBLISHED, TicketLog::STEP_ASSIGNED, TicketLog::STEP_ON_SITE, TicketLog::STEP_REPAIR,
            TicketLog::STEP_DOCUMENTATION_PUBLISHED, TicketLog::STEP_SATISFACTION_SUBMITTED];

        $responses = [
            [0, [5, 5, 5, 4, 5, 5, 5, 5, 4], true, null, 'Technician was helpful and explained the repair clearly.', 'link', 'needs_review', ['fast_completion'], 300],
            [1, [4, 4, 4, 4, 5, 4, 4, 4, 4], false, 'Machine still makes noise under full load.', 'Sparepart follow up needed.', 'link', 'normal', null, 2000],
            [2, [3, 4, 3, 3, 4, 3, 4, 3, 4], true, null, null, 'admin', 'reviewed', ['admin_input'], 4000],
            [0, [2, 3, 3, 2, 3, 3, 2, 2, 3], false, 'Technician arrived late, issue not resolved.', 'Please improve punctuality.', 'link', 'needs_review', ['same_network_as_technician'], 6000],
        ];

        foreach ($responses as $n => [$ci, $scores, $ok, $issue, $feedback, $source, $review, $reasons, $minAgo]) {
            $t = $ticket($n + 1, $customers[$ci], $done, 1 + intdiv($minAgo, 1440));
            $row = array_combine(array_keys(TicketSatisfaction::ASPECTS), $scores) + [
                'ticket_id' => $t->id,
                'machine_ok' => $ok,
                'machine_issue_note' => $issue,
                'feedback' => $feedback,
                'signer_name' => $customers[$ci]->pic_name,
                'signer_position' => 'Customer PIC',
                'source' => $source,
                'review_status' => $review,
                'review_reasons' => $reasons,
                'filled_at' => $now->copy()->subMinutes($minAgo),
            ];
            if ($source === 'link') {
                $row += [
                    'otp_verified_at' => $now->copy()->subMinutes($minAgo + 5),
                    'otp_phone_masked' => '0881••••8570',
                    'link_created_by_user_id' => $actor->id,
                    'shared_via' => 'copy',
                    'ip_address' => '103.28.10.20',
                    'user_agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/126.0 Mobile Safari/537.36',
                    'duration_seconds' => $reasons && in_array('fast_completion', $reasons, true) ? 18 : 258,
                ];
            } else {
                $row['filled_by_user_id'] = $actor->id;
            }
            TicketSatisfaction::create($row);
        }

        $legacy = $ticket(5, $customers[1], $done, 8);
        TicketSatisfaction::create([
            'ticket_id' => $legacy->id,
            'timeliness' => 4, 'technician_attitude' => 5, 'technical_knowledge' => 4, 'work_neatness' => 4, 'solution_quality' => 5,
            'filled_by_user_id' => $actor->id,
            'filled_at' => $now->copy()->subDays(8),
        ]);

        $open = $ticket(6, $customers[0], array_slice($done, 0, 5), 1);
        SatisfactionLink::issue($open, $actor->id);

        // Ticket in Recoming
        $recoming = $ticket(7, $customers[1], [TicketLog::STEP_PUBLISHED, TicketLog::STEP_ASSIGNED, TicketLog::STEP_ON_SITE, TicketLog::STEP_REPAIR], 2);
        TicketVisit::create(['ticket_id' => $recoming->id, 'scheduled_at' => $now->copy()->addDays(3)->startOfDay()]);
        TicketLog::create(['ticket_id' => $recoming->id, 'step' => TicketLog::STEP_RECOMING, 'actor_user_id' => $actor->id]);

        $this->command?->info('Seeded 5 survey responses, 1 active survey link (DEMO-6), 1 recoming ticket (DEMO-7).');
    }
}
