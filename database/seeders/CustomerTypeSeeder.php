<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Corporate'],
            ['name' => 'Government'],
            ['name' => 'Personal'],
            ['name' => 'Repeat Order'],
            ['name' => 'Distributor'],
            ['name' => 'Tender-based'],
            ['name' => 'Commanditaire Vennootschap'],
            ['name' => 'Institution'],
            ['name' => 'Hospital'],
            ['name' => 'Foundation'],
        ];

        foreach ($types as $type) {
            DB::table('ref_customer_types')->updateOrInsert(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
