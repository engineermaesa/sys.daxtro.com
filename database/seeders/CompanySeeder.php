<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Tech Innovators',
                'address' => '123 Innovation Way',
                'phone' => '555-1000',
            ],
            [
                'name' => 'Logistics Corp',
                'address' => '456 Distribution Ave',
                'phone' => '555-2000',
            ],
        ];

        foreach ($companies as $company) {
            DB::table('ref_companies')->updateOrInsert(
                ['name' => $company['name']],
                $company
            );
        }
    }
}
