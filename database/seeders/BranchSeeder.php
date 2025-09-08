<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $companyIds = DB::table('ref_companies')->pluck('id', 'name');

        $defaultCompany = $companyIds->first();

        $branches = [
            [
                'company_id' => $defaultCompany,
                'name' => 'Branch Jakarta',
                'code' => 'JKT',
                'address' => 'Jakarta Address',
            ],
            [
                'company_id' => $defaultCompany,
                'name' => 'Branch Makassar',
                'code' => 'MKS',
                'address' => 'Makassar Address',
            ],
            [
                'company_id' => $defaultCompany,
                'name' => 'Branch Surabaya',
                'code' => 'SBY',
                'address' => 'Surabaya Address',
            ],
        ];

        foreach ($branches as $branch) {
            DB::table('ref_branches')->updateOrInsert(
                ['code' => $branch['code']],
                $branch
            );
        }
    }
}
