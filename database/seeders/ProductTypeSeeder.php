<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['id' => 1, 'name' => 'Tube Ice Machine'],
            ['id' => 2, 'name' => 'Cube Ice Machine'],
            ['id' => 3, 'name' => 'Block Ice Machine'],
            ['id' => 4, 'name' => 'Flake Ice Machine'],
            ['id' => 5, 'name' => 'Cold Room'],
            ['id' => 6, 'name' => 'Slurry Ice Machine'],
        ];

        foreach ($types as $type) {
            DB::table('ref_product_types')->updateOrInsert(
                ['id' => $type['id']],
                $type
            );
        }
    }
}
