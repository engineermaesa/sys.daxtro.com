<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = json_decode(include __DIR__.'/data/products.php', true);

        foreach ($products as $product) {
            if (!array_key_exists('bdi_price', $product)) {
                $product['bdi_price'] = $product['corporate_price'] ?? null;
            }
            DB::table('ref_products')->insert($product);
        }
    }
}
