<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeetingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Zoom / Google Meet',
            'Offline - Office',
            'Offline - Canvass',
            'Video Call',
            'EXPO'
        ];

        foreach ($types as $name) {
            DB::table('meeting_types')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
    }
}
