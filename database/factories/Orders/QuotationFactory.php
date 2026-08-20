<?php

namespace Database\Factories\Orders;

use App\Models\Orders\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        return [
            'lead_id' => null,
            'quotation_no' => 'QT_DEAL_' . str_pad((string) $this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'status' => 'review',
            'subtotal' => 1000000,
            'tax_pct' => 11,
            'tax_total' => 110000,
            'total_discount' => 0,
            'grand_total' => 1110000,
            'booking_fee' => null,
            'expiry_date' => now()->addDays(15),
            'created_by' => null,
        ];
    }
}
