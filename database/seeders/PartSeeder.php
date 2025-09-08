<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PartSeeder extends Seeder
{
    public function run(): void
    {
        $parts = [
            ['id' => 1, 'name' => 'PLC Siemens PLC', 'sku' => 'A03.01.0017', 'price' => 0],
            ['id' => 2, 'name' => 'Touchscreen', 'sku' => 'A03.01.0057', 'price' => 0],
            ['id' => 3, 'name' => 'Phase Sequence Protector', 'sku' => 'A03.02.0006', 'price' => 0],
            ['id' => 4, 'name' => 'Circuit Breaker', 'sku' => 'A03.04.0001', 'price' => 0],
            ['id' => 5, 'name' => 'Circuit Breaker', 'sku' => 'A03.04.0065', 'price' => 0],
            ['id' => 6, 'name' => 'Circuit Breaker', 'sku' => 'A03.04.0071', 'price' => 0],
            ['id' => 7, 'name' => 'Thermal Relay', 'sku' => 'A03.05.0085', 'price' => 0],
            ['id' => 8, 'name' => 'Thermal Relay', 'sku' => 'A03.05.0083', 'price' => 0],
            ['id' => 9, 'name' => 'Thermal Relay', 'sku' => 'A03.05.0075', 'price' => 0],
            ['id' => 10, 'name' => 'AC Contactors', 'sku' => 'A03.06.0054', 'price' => 0],
            ['id' => 11, 'name' => 'AC Contactors', 'sku' => 'A03.06.0059', 'price' => 0],
            ['id' => 12, 'name' => 'Electrical Box', 'sku' => 'A03.10.0115', 'price' => 0],
            ['id' => 13, 'name' => 'Rotary Switch', 'sku' => 'A03.11.0011', 'price' => 0],
            ['id' => 14, 'name' => 'Buzzer', 'sku' => 'A03.11.0006', 'price' => 0],
            ['id' => 15, 'name' => 'Emergency Stop Button', 'sku' => 'A03.11.0009', 'price' => 0],
            ['id' => 16, 'name' => 'Lamp Knob Labelling Frame', 'sku' => 'A03.11.0001', 'price' => 0],
            ['id' => 17, 'name' => 'Emergency Stop Warning Sign', 'sku' => 'A03.11.0003', 'price' => 0],
            ['id' => 18, 'name' => 'Grounding Aluminium Label', 'sku' => 'A03.11.0004', 'price' => 0],
            ['id' => 19, 'name' => 'Terminal Block Row', 'sku' => 'A03.14.0005', 'price' => 0],
        ];

        foreach ($parts as $part) {
            DB::table('ref_parts')->updateOrInsert(['id' => $part['id']], $part);
        }
    }
}
