<?php

namespace Database\Seeders;

use App\Models\Aftersales\RefInspectionItem;
use App\Models\Aftersales\RefInspectionTemplate;
use App\Services\Aftersales\InspectionTemplates;
use Illuminate\Database\Seeder;

class InspectionVersionSeeder extends Seeder
{
    public function run(): void
    {
        if (RefInspectionTemplate::exists()) {
            return;
        }
        if (! RefInspectionItem::exists()) {
            $this->call(InspectionItemSeeder::class);
        }
        app(InspectionTemplates::class)->importLegacy();
    }
}
