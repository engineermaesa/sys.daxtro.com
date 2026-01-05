<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadSeeder extends Seeder
{
    public function run(): void
    {    
        $sources = [
                'Ads Google',
                'Website',
                'Meta',
                'Linked In',
                'Tik Tok',
                'Friends Recommendation',
                'Canvas', 
                'Visit', 
                'Expo RHVAC Jakarta 2025',
                'Association',
                'Business Association',
                'Repeat Order',
                'Sales Independen',
                'Aftersales',
                'Office Walk In',
                'Media with QR/Referral',
                'Agent / Reseller',
                'Youtube',
                'Google Search',
                'Telemarketing',
        ];
        foreach ($sources as $name) {
            DB::table('lead_sources')->updateOrInsert(['name' => $name], ['name' => $name]);
        }

        $segments = ['Corporate', 'Government', 'Personal', 'FOB'];
        
        foreach ($segments as $name) {
            DB::table('lead_segments')->updateOrInsert(['name' => $name], ['name' => $name]);
        }

        $statuses = [
            ['name' => 'Published'],
            ['name' => 'Cold'],
            ['name' => 'Warm'],
            ['name' => 'Hot'],
            ['name' => 'Deal'],
            ['name' => 'Trash Cold'],
            ['name' => 'Trash Warm'],
        ];
        
        foreach ($statuses as $status) {
            DB::table('lead_statuses')->insert([
                'name' => $status['name'],
            ]);
        }

        // fetch ids for relation
        $sourceIds = DB::table('lead_sources')->pluck('id');
        $segmentIds = DB::table('lead_segments')->pluck('id');
        $regions = DB::table('ref_regions');
        $statusId = DB::table('lead_statuses')->where('name', 'Published')->value('id');
        $provinces = config('provinces');

        $needsOptions = [
            'Tube Ice',
            'Cube Ice',
            'Block Ice',
            'Flake ice',
            'Slurry Ice',
            'Flake Ice',
            'Cold Room',
            'Other',
        ];

        $customerTypes = DB::table('ref_customer_types')->pluck('name');

        $productIds = DB::table('ref_products')->pluck('id');
        $industryIds = DB::table('ref_industries')->pluck('id');
        $jabatanIds = DB::table('ref_jabatans')->pluck('id');

        for ($i = 1; $i <= 10; $i++) {
            $region = $regions->inRandomOrder()->first();
            $region_id = $region->id;
            $branch_id = $region->branch_id;

            DB::table('leads')->insert([
                'source_id' => $sourceIds->random(),
                'segment_id' => $segmentIds->random(),
                'branch_id' => $branch_id,
                'region_id' => $region_id,
                'industry_id' => $industryIds->random(),
                'jabatan_id' => $jabatanIds->random(),
                'product_id' => $productIds->random(),
                'province' => $provinces[array_rand($provinces)],
                'status_id' => $statusId,
                'company' => 'Company '.$i,
                'customer_type' => $customerTypes->random(),
                'name' => 'Lead '.$i,
                'phone' => '080000000'.$i,
                'email' => 'lead'.$i.'@example.com',
                'needs' => $needsOptions[array_rand($needsOptions)],
                'tonase' => rand(1, 100),
                'published_at' => now()->subHours(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
