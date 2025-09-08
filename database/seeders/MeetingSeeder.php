<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Leads\LeadStatus;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $cities = config('cities');
        $userId = DB::table('users')->value('id');

        $leads = DB::table('leads')
            ->select('id', 'status_id')
            ->whereIn('status_id', [
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL,
            ])
            ->get();

        if ($leads->isEmpty() || !$userId) {
            return;
        }

        $dir = storage_path('app/private/meetings');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ($leads as $index => $lead) {
            $fileName = sprintf('MEETING_%03d.pdf', $index + 1);
            $attachmentId = DB::table('attachments')->insertGetId([
                'type' => 'meeting_result',
                'file_path' => 'storage/meetings/' . $fileName,
                'mime_type' => 'application/pdf',
                'size' => 0,
                'uploaded_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('lead_meetings')->insert([
                'lead_id' => $lead->id,
                'is_online' => false,
                'scheduled_start_at' => now()->addDays($index + 1),
                'scheduled_end_at' => now()->addDays($index + 1)->addHour(),
                'city' => $faker->randomElement($cities),
                'address' => $faker->streetAddress(),
                'result' => $lead->status_id == LeadStatus::DEAL
                    ? 'yes'
                    : $faker->randomElement(['yes', 'no']),
                'summary' => $faker->sentence(),
                'attachment_id' => $attachmentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            file_put_contents($dir . '/' . $fileName, 'Dummy meeting result file');
        }
    }
}
