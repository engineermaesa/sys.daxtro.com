<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_quotation_has_booking_fee_field(): void
    {
        $quotation = new \App\Models\Orders\Quotation(['booking_fee' => 100]);
        $this->assertSame(100, $quotation->booking_fee);
    }
}
