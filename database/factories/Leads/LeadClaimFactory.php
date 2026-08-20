<?php

namespace Database\Factories\Leads;

use App\Models\Leads\LeadClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadClaim>
 */
class LeadClaimFactory extends Factory
{
    protected $model = LeadClaim::class;

    /**
     * No relationship defaults on purpose — lead_id/sales_id must always be
     * passed explicitly by the caller to avoid eagerly resolving Lead::factory()
     * (which requires seeded ref_regions data not guaranteed to exist in tests).
     */
    public function definition(): array
    {
        return [
            'lead_id' => null,
            'sales_id' => null,
            'claimed_at' => now(),
        ];
    }
}
