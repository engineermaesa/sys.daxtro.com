<?php

namespace Database\Factories\Orders;

use App\Models\Orders\TempQuotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TempQuotation>
 */
class TempQuotationFactory extends Factory
{
    protected $model = TempQuotation::class;

    public function definition(): array
    {
        return [
            'lead_id' => null,
            'status' => 'draft',
            'subtotal' => 1000000,
            'tax_pct' => 11,
            'tax_total' => 110000,
            'total_discount' => 0,
            'grand_total' => 1110000,
            'booking_fee' => null,
            'created_by' => null,
        ];
    }
}
