<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            ['name' => 'Perikanan & Kelautan'],
            ['name' => 'Peternakan & Perikanan'],
            ['name' => 'Makanan & Minuman'],
            ['name' => 'Logistik & Distribusi'],
            ['name' => 'Wisata & Hospitality'],
            ['name' => 'CSR Project'],
            ['name' => 'Isntitusi Pendidikan'],
            ['name' => 'Hospital'],
            ['name' => 'Agent Mesin Es'],
        ];

        foreach ($industries as $industry) {
            DB::table('ref_industries')->updateOrInsert(
                ['name' => $industry['name']],
                $industry
            );
        }
    }
}
