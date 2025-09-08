<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategoryPivotSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['product_id' => 1, 'category_id' => 1],
            ['product_id' => 1, 'category_id' => 2],
            ['product_id' => 1, 'category_id' => 3],
            ['product_id' => 2, 'category_id' => 1],
            ['product_id' => 2, 'category_id' => 2],
        ];

        foreach ($data as $row) {
            DB::table('ref_product_category')->updateOrInsert($row, $row);
        }
    }
}
