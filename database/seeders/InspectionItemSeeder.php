<?php

namespace Database\Seeders;

use App\Models\Aftersales\RefInspectionField;
use App\Models\Aftersales\RefInspectionItem;
use Illuminate\Database\Seeder;

class InspectionItemSeeder extends Seeder
{
    public function run(): void
    {
        $num = fn (string $key, string $label, ?string $unit = null) => ['key' => $key, 'label' => $label, 'type' => 'number', 'unit' => $unit];
        $chk = fn (array $options, string $label = 'Condition', string $key = 'condition') => ['key' => $key, 'label' => $label, 'type' => 'checklist', 'options' => $options];
        $txt = fn (string $key, string $label, ?string $requiredWhen = null) => ['key' => $key, 'label' => $label, 'type' => 'text', 'required_when' => $requiredWhen];

        $items = [
            // A. Electrical & Control Automation
            [1, 'A', 'Input Voltage', '380V ±10% (3P) / 220V ±10% (1P)', [$num('rs', 'R-S', 'V'), $num('st', 'S-T', 'V'), $num('tr', 'T-R', 'V')]],
            [2, 'A', 'Compressor Running Current', 'Per motor nameplate (A)', [$num('l1', 'L1', 'A'), $num('l2', 'L2', 'A'), $num('l3', 'L3', 'A')]],
            [3, 'A', 'Cutter / Gearbox / Pump Motor Current', 'Per nominal current (A)', [$num('cutter', 'Cutter', 'A'), $num('pump', 'Pump', 'A')]],
            [4, 'A', 'Contactor, Relay & Terminals', 'Tight, no burn marks or corrosion', [$chk(['Good', 'Loose', 'Burnt'])]],
            [5, 'A', 'Thermal Overload & Motor Protection', 'Correct amp rating setting', [$chk(['Normal', 'Frequent Trip'])]],
            [6, 'A', 'PLC / Controller / Display Panel', 'Normal program, no alarm or error', [$chk(['Normal', 'Alarm']), $txt('alarm_code', 'Alarm code', 'Alarm')]],
            [7, 'A', 'Safety Switch (HP / LP Pressure Cutout)', 'HP cutout 22-25 bar, LP 0.5-1 bar', [$num('hp', 'HP', 'bar'), $num('lp', 'LP', 'bar')]],
            [8, 'A', 'Temperature, Water Level & Bin Sensors', 'Accurate response, auto-stop active', [$chk(['Working', 'Error / Malfunction'])]],
            // B. Refrigeration & Compressor
            [9, 'B', 'Suction Pressure (LP)', 'Tube/Cube: 2-3.5 bar, Flake: 1.5-2.5 bar', [$num('lp', 'LP', 'bar')]],
            [10, 'B', 'Discharge Pressure (HP)', 'Air cooled: 15-20 bar, water cooled: 12-16 bar', [$num('hp', 'HP', 'bar')]],
            [11, 'B', 'Compressor Oil (Level & Cleanliness)', 'Level 1/2 - 3/4 of sight glass, clear color', [$num('level', 'Level'), $chk(['Clear', 'Cloudy'])]],
            [12, 'B', 'Suction & Discharge Temperature', 'Suction line < 10°C, discharge < 90°C', [$num('t_suc', 'T. Suction', '°C'), $num('t_dis', 'T. Discharge', '°C')]],
            [13, 'B', 'Condenser (Air / Water Cooled)', 'Free of dust and scale, fins not bent', [$chk(['Clean', 'Dirty / Scaled'])]],
            [14, 'B', 'Cooling Tower & Condenser Water Pump', 'Smooth water circulation, fan normal', [$num('water_in', 'Water in', '°C'), $num('water_out', 'Water out', '°C')]],
            [15, 'B', 'Expansion Valve (TXV/EXV) & Solenoid', 'Superheat 5-8 K, valve opens properly', [$chk(['Normal', 'Blocked / Frosting'])]],
            [16, 'B', 'Hot Gas Valve / Harvest Solenoid Valve', 'Fully open during harvest cycle', [$chk(['Normal', 'Leaking / Stuck'])]],
            [17, 'B', 'Sight Glass & Filter Drier', 'Green (dry), filter delta T < 1°C', [$chk(['Green (Dry)', 'Yellow (Wet)'])]],
            // C. Water & Ice Making
            [18, 'C', 'Incoming Water Supply & Quality', 'Pressure 2-4 bar, TDS < 150 ppm', [$num('pressure', 'Pressure', 'bar'), $num('tds', 'TDS', 'ppm')]],
            [19, 'C', 'Water Filter (Pre-Filter Cartridge)', 'Cartridge clean, not clogged', [$chk(['Clean', 'Dirty / Clogged'])]],
            [20, 'C', 'Ice Water Pump & Water Distributor', 'Even spray / flow to the evaporator', [$chk(['Even / Smooth', 'Clogged'])]],
            [21, 'C', 'Evaporator (Tube/Drum/Grid/Slurry Chamber)', 'Free of scale and mineral deposits', [$chk(['Clean', 'Scaled', 'Corroded'])]],
            [22, 'C', 'Ice Cutter Mechanism (Cutter Blade)', 'Smooth rotation, precise and sharp blade', [$chk(['Smooth', 'Noisy', 'Worn'])]],
            [23, 'C', 'Freezing Cycle Time', 'Per unit capacity specification', [$num('duration', 'Duration', 'min')]],
            [24, 'C', 'Harvest Cycle Time', '300 - 400 seconds (Tube/Cube type)', [$num('duration', 'Duration', 'sec')], ['Tube', 'Cube']],
            [25, 'C', 'Ice Quality', 'Clear, solid, uniform thickness', [$chk(['Good / Clear', 'Cloudy / Porous'])]],
            [26, 'C', 'Ice Production Quantity', 'Per machine capacity', [$num('qty', 'Qty', 'kg/cycle')]],
            // D. Mechanical, Bin Storage & Structure
            [27, 'D', 'System Leaks (Refrigerant / Oil / Water)', 'No leaks at all', [$chk(['Leak Free', 'Leak Found']), $txt('location', 'Leak location', 'Leak Found')]],
            [28, 'D', 'Ice Storage Bin & Ice Receiver', 'Tight insulation, smooth drainage', [$chk(['Good', 'Drain Clogged'])]],
            [29, 'D', 'Operating Vibration & Noise', 'Smooth, no harsh vibration', [$chk(['Smooth / Normal', 'Vibrating / Noisy'])]],
            [30, 'D', 'Stainless Frame & Unit Structure', 'Sturdy, free of excessive rust or corrosion', [$chk(['Good', 'Corroded'])]],
            // E. Machine Cleanliness
            [31, 'E', 'Electrical Box', 'Not dirty and not rusty', [$chk(['Clean', 'Corroded'])]],
            [32, 'E', 'Condenser', 'Not dirty and not rusty', [$chk(['Clean', 'Corroded'])]],
            [33, 'E', 'Evaporator', 'Not dirty and not rusty', [$chk(['Clean', 'Corroded'])]],
            [34, 'E', 'Compressor', 'Not dirty and not rusty', [$chk(['Clean', 'Corroded'])]],
        ];

        foreach ($items as $row) {
            [$number, $group, $name, $standard, $fields] = $row;

            $item = RefInspectionItem::withTrashed()->updateOrCreate(
                ['number' => $number],
                ['group_code' => $group, 'name' => $name, 'standard' => $standard, 'only_for_types' => $row[5] ?? null, 'deleted_at' => null]
            );

            foreach ($fields as $i => $field) {
                RefInspectionField::updateOrCreate(
                    ['item_id' => $item->id, 'key' => $field['key']],
                    [
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'unit' => $field['unit'] ?? null,
                        'options' => $field['options'] ?? null,
                        'required_when' => $field['required_when'] ?? null,
                        'sort' => $i + 1,
                    ]
                );
            }
        }
    }
}
