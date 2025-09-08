<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            ['name' => 'Bank A'],
            ['name' => 'Bank B'],
        ];

        foreach ($banks as $bank) {
            DB::table('ref_banks')->updateOrInsert(['name' => $bank['name']], $bank);
        }
    }
}
