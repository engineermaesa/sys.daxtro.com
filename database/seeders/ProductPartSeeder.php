<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductPartSeeder extends Seeder
{
    public function run(): void
    {
        // Get all product IDs
        $productIds = DB::table('ref_products')->pluck('id')->toArray();

        // Get all part IDs
        $partIds = DB::table('ref_parts')->pluck('id')->toArray();

        $data = [];

        foreach ($productIds as $productId) {
            foreach ($partIds as $partId) {
                $data[] = [
                    'product_id' => $productId,
                    'part_id'    => $partId,
                ];
            }
        }

        // Optional: Clear existing relations to avoid duplicates
        DB::table('ref_product_parts')->truncate();

        // Chunk insert for large datasets
        $chunks = array_chunk($data, 500);
        foreach ($chunks as $chunk) {
            DB::table('ref_product_parts')->insert($chunk);
        }
    }
}
