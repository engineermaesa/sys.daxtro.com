<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        $jabatans = [
            ['name' => 'Direktur'],
            ['name' => 'Manager'],
            ['name' => 'Supervisor'],
            ['name' => 'Staff'],
            ['name' => 'Owner'],
            ['name' => 'Purchasing'],
            ['name' => 'Finance'],
            ['name' => 'Engineer'],
            ['name' => 'Operator'],
            ['name' => 'Lainnya'],
        ];

        foreach ($jabatans as $jabatan) {
            DB::table('ref_jabatans')->updateOrInsert(
                ['name' => $jabatan['name']],
                $jabatan
            );
        }
    }
}
