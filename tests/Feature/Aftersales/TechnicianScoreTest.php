<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketRecoming;
use App\Models\Aftersales\TicketSatisfaction;
use App\Services\Aftersales\TechnicianScore;
use Carbon\Carbon;
use Tests\TestCase;

class TechnicianScoreTest extends TestCase
{
    private int $id = 0;

    private function ticket(bool $closed, array $recomings = [], array $responses = []): Ticket
    {
        $t = new Ticket(['assigned_technician_id' => 1]);
        $t->id = ++$this->id;
        $t->ticket_code = 'T' . $t->id;
        $logs = collect([new TicketLog(['step' => TicketLog::STEP_DOCUMENTATION_PUBLISHED])]);
        if ($closed) {
            $log = new TicketLog(['step' => TicketLog::STEP_CLOSED]);
            $log->id = 99;
            $log->created_at = Carbon::now();
            $logs->push($log);
        }
        $t->setRelation('logs', $logs);
        $t->setRelation('visits', collect());
        $t->setRelation('recomings', collect($recomings)->map(function ($r) {
            $m = new TicketRecoming(['reason' => $r[0], 'from_step' => $r[1] ?? TicketLog::STEP_REPAIR]);
            $m->created_at = Carbon::now();

            return $m;
        }));
        $t->setRelation('satisfactions', collect($responses));

        return $t;
    }

    private function response(int $id, float $score, string $source = 'link', string $review = 'normal'): TicketSatisfaction
    {
        $s = new TicketSatisfaction(['source' => $source, 'review_status' => $review, 'overall_satisfaction' => $score]);
        $s->id = $id;
        $s->filled_at = Carbon::now();
        $s->setRelation('answers', collect());

        return $s;
    }

    private function summary(array $tickets): array
    {
        $m = new \ReflectionMethod(TechnicianScore::class, 'summary');

        return $m->invoke(null, collect($tickets), null);
    }

    public function test_prd_completion_example_is_six_of_nine(): void
    {
        $tickets = [];
        for ($i = 0; $i < 6; $i++) {
            $tickets[] = $this->ticket(true);
        }
        $tickets[] = $this->ticket(true, [['further_work_needed']]);
        $tickets[] = $this->ticket(true, [['further_work_needed']]);
        $tickets[] = $this->ticket(false, [['other']]);
        $tickets[] = $this->ticket(true, [['sparepart_unavailable']]);
        $tickets[] = $this->ticket(false); // open, never Recoming: not counted

        $s = $this->summary($tickets);

        $this->assertSame([67, 6, 9], [$s['completion'], $s['completion_closed'], $s['completion_total']]);
    }

    public function test_survey_recoming_and_mixed_reasons_count(): void
    {
        $s = $this->summary([
            $this->ticket(true, [['sparepart_unavailable', TicketLog::STEP_SATISFACTION_SUBMITTED]]),
            $this->ticket(true, [['site_access'], ['other']]),
        ]);

        $this->assertSame([0, 2], [$s['completion_closed'], $s['completion_total']]);
    }

    public function test_rating_uses_only_current_valid_link_responses(): void
    {
        $s = $this->summary([
            $this->ticket(true, [], [$this->response(1, 2), $this->response(2, 5)]),          // old one is history
            $this->ticket(true, [], [$this->response(3, 4, 'link', 'reviewed')]),
            $this->ticket(true, [], [$this->response(4, 1, 'admin', 'reviewed')]),            // admin never counts
            $this->ticket(true, [], [$this->response(5, 1, 'link', 'needs_review')]),
            $this->ticket(true, [], [$this->response(6, 1, 'link', 'rejected')]),
        ]);

        $this->assertSame([4.5, 2], [$s['rating'], $s['rating_count']]);
    }
}
