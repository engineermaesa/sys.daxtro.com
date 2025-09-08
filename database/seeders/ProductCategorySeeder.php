<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'Government'],
            ['id' => 2, 'name' => 'Corporate'],
            ['id' => 3, 'name' => 'FOB'],
            ['id' => 3, 'name' => 'Package'],
            ['id' => 3, 'name' => 'Personal']
        ];

        foreach ($categories as $category) {
            DB::table('ref_product_categories')->updateOrInsert(
                ['id' => $category['id']],
                $category
            );
        }
    }
}
