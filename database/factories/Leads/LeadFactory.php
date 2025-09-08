<?php

namespace Database\Factories\Leads;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        $needsOptions = [
            'Consultation about products',
            'Request for quotation',
            'Need support for existing product',
            'Follow-up from previous contact',
            'Want to schedule a meeting',
        ];

        $region = DB::table('ref_regions')->inRandomOrder()->first();
        $region_id = $region->id;
        $branch_id = $region->branch_id;
        
        return [
            'source_id' => DB::table('lead_sources')->inRandomOrder()->value('id') ?? 1,
            'segment_id' => DB::table('lead_segments')->inRandomOrder()->value('id') ?? 1,
            'branch_id' => $branch_id,
            'region_id' => $region_id,
            'status_id' => LeadStatus::DEAL,
            'company' => $this->faker->company(),
            'jabatan_id' => DB::table('ref_jabatans')->inRandomOrder()->value('id'),
            'customer_type' => DB::table('ref_customer_types')->inRandomOrder()->value('name') ?? 'Corporate',
            'name' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'product_id'  => DB::table('ref_products')->inRandomOrder()->value('id'),
            'province'     => $this->faker->randomElement(config('provinces')),
            'needs'        => $this->faker->randomElement($needsOptions),
            'published_at' => now(),
        ];
    }
}
