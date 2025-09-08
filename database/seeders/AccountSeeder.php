<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = DB::table('ref_companies')->value('id');
        $bankId = DB::table('ref_banks')->value('id');

        if (!$companyId || !$bankId) {
            return;
        }

        $accounts = [
            [
                'company_id' => $companyId,
                'bank_id' => $bankId,
                'account_number' => '1234567890',
                'holder_name' => 'Default Account',
            ],
        ];

        foreach ($accounts as $account) {
            DB::table('ref_accounts')->updateOrInsert(
                ['account_number' => $account['account_number']],
                $account
            );
        }
    }
}
