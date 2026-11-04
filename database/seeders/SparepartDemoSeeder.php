<?php

namespace Database\Seeders;

use App\Models\Aftersales\PartStockMovement;
use App\Models\Aftersales\RefSparepart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SparepartDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo spareparts may only be seeded locally or in testing.');
        }

        $parts = [
            ['Compressor 2 HP', 'Demo Cooling', 4500000, 8, 2, 'A-01'],
            ['Condenser Fan Motor', 'Demo Motor', 850000, 12, 3, 'A-02'],
            ['Water Circulation Pump', 'Demo Pump', 1250000, 6, 2, 'A-03'],
            ['Filter Drier', 'Demo Cooling', 175000, 25, 5, 'B-01'],
            ['Expansion Valve', 'Demo Cooling', 650000, 2, 3, 'B-02'],
            ['Solenoid Valve', 'Demo Cooling', 475000, 10, 3, 'B-03'],
            ['High Pressure Switch', 'Demo Control', 325000, 3, 3, 'C-01'],
            ['Temperature Sensor', 'Demo Sensor', 150000, 20, 5, 'C-02'],
            ['Magnetic Contactor', 'Demo Electric', 285000, 0, 2, 'C-03'],
            ['Refrigerant R404A Cylinder', 'Demo Cooling', 1850000, 5, 2, 'D-01'],
        ];

        DB::transaction(function () use ($parts) {
            foreach ($parts as $index => [$name, $brand, $price, $stock, $minimum, $rack]) {
                $number = 'DEMO-SP-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
                // Include deleted rows in the lookup so reruns neither recreate nor reset them.
                $part = RefSparepart::withTrashed()->firstOrCreate(['part_number' => $number], [
                    'name' => $name.' (Demo)', 'brand' => $brand, 'supplier' => 'Demo Supplier — Local Testing',
                    'price' => $price, 'stock' => $stock, 'min_stock' => $minimum, 'rack_location' => $rack,
                ]);
                if ($part->wasRecentlyCreated && $stock > 0) {
                    PartStockMovement::create(['ref_sparepart_id' => $part->id, 'type' => 'in', 'qty' => $stock, 'note' => 'Opening stock for local testing (SparepartDemoSeeder)']);
                }
                $this->command?->info($number.' | '.$part->name.' | '.($part->wasRecentlyCreated ? 'Created' : 'Existing; unchanged'));
            }
        });
    }
}
