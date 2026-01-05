<?php

namespace Tests\Unit\Models\Masters;

use Tests\TestCase;
use App\Models\Masters\MeetingType;
use App\Models\Leads\{Lead, LeadMeeting};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeetingTypeTest extends TestCase
{
    use RefreshDatabase;

    private $lead;
    private $expoMeetingType;

    protected function setUp(): void
    {
        parent::setUp();

        // Create expo meeting type
        $this->expoMeetingType = MeetingType::create([
            'name' => 'EXPO'
        ]);

        // Create a mock lead source
        $source = \App\Models\Masters\LeadSource::create([
            'name' => 'Expo RHVAC Jakarta 2025'
        ]);

        // Create a test lead
        $this->lead = Lead::create([
            'source_id' => $source->id,
            // Add other required lead fields
        ]);
    }

    /** @test */
    public function it_auto_fills_expo_meeting_details()
    {
        // Freeze time for testing
        Carbon::setTestNow(now());

        // Create meeting for expo lead
        $meeting = LeadMeeting::create([
            'lead_id' => $this->lead->id,
            'meeting_type_id' => $this->expoMeetingType->id,
            'is_online' => false,
            'scheduled_start_at' => now(),
            'scheduled_end_at' => now()->addMinute(),
            'city' => 'Jakarta Pusat',
            'address' => null
        ]);

        // Create empty expense
        $expense = $meeting->expense()->create([
            'total_amount' => 0
        ]);

        $this->assertEquals('EXPO', $meeting->meetingType->name);
        $this->assertEquals('Jakarta Pusat', $meeting->city);
        $this->assertNull($meeting->address);
        $this->assertEquals(0, $meeting->expense->total_amount);
        $this->assertEquals(
            now()->toDateTimeString(),
            $meeting->scheduled_start_at->toDateTimeString()
        );
        $this->assertEquals(
            now()->addMinute()->toDateTimeString(),
            $meeting->scheduled_end_at->toDateTimeString()
        );
        
        Carbon::setTestNow(); // Clear mock time
    }

    /** @test */
    public function it_requires_expo_meeting_type_for_expo_source()
    {
        $this->assertTrue(
            MeetingType::where('name', 'EXPO')->exists(),
            'EXPO meeting type should exist'
        );
    }
}