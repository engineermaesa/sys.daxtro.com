<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Leads\Lead;
use Database\Seeders\MeetingSeeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $productId = DB::table('ref_products')->value('id');
        $userId = DB::table('users')->value('id');

        for ($i = 0; $i < 3; $i++) {
            $lead = Lead::factory()->create([
                'id' => 101 + $i,
            ]);

            $subtotal = $faker->numberBetween(500, 1500);
            $taxPct = 11;
            $tax = $subtotal * $taxPct / 100;
            $grand = $subtotal + $tax;

            $quotationId = DB::table('quotations')->insertGetId([
                'lead_id' => $lead->id,
                'quotation_no' => 'QT' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'status' => 'published',
                'subtotal' => $subtotal,
                'tax_pct' => $taxPct,
                'tax_total' => $tax,
                'total_discount' => 0,
                'grand_total' => $grand,
                'booking_fee' => $subtotal * 0.1,
                'expiry_date' => now()->addWeeks(2),
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $itemPrice = $subtotal / 2;
            // regular product item
            DB::table('quotation_items')->insert([
                'quotation_id' => $quotationId,
                'product_id' => $productId,
                'qty' => 1,
                'description' => $faker->sentence(),
                'unit_price' => $itemPrice,
                'discount_pct' => 0,
                'line_total' => $itemPrice,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // add-on item with no product reference
            DB::table('quotation_items')->insert([
                'quotation_id' => $quotationId,
                'product_id' => null,
                'qty' => 1,
                'description' => 'Addon Item',
                'unit_price' => $itemPrice,
                'discount_pct' => 0,
                'line_total' => $itemPrice,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $proformaId = DB::table('proformas')->insertGetId([
                'quotation_id' => $quotationId,
                'term_no'      => null,
                'proforma_type' => 'booking_fee',
                'proforma_no'   => 'PROFORMA_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'amount'        => $subtotal,
                'status'        => 'confirmed',
                'issued_at'     => now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $pfAttachmentId = DB::table('attachments')->insertGetId([
                'type' => 'proforma_pdf',
                'file_path' => 'storage/proformas/PROFORMA_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT) . '.pdf',
                'mime_type' => 'application/pdf',
                'size' => 0,
                'uploaded_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('proformas')->where('id', $proformaId)->update(['attachment_id' => $pfAttachmentId]);

            $orderId = DB::table('orders')->insertGetId([
                'lead_id' => $lead->id,
                'order_no' => 'ORD' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'total_billing' => $grand,
                'order_status' => 'in_progress',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('order_progress_logs')->insert([
                'order_id' => $orderId,
                'progress_step' => 1, 
                'note' => 'Order initialized via seeder',
                'logged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'user_id' => 1
            ]);


            $percentages = [30, 40, 30];
            foreach ($percentages as $idx => $pct) {
                DB::table('quotation_payment_terms')->insert([
                    'quotation_id' => $quotationId,
                    'term_no'      => $idx + 1,
                    'percentage'   => $pct,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                DB::table('order_payment_terms')->insert([
                    'order_id'   => $orderId,
                    'term_no'    => $idx + 1,
                    'percentage' => $pct,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            for ($j = 0; $j < 2; $j++) {
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'description' => $faker->sentence(),
                    'qty' => 1,
                    'unit_price' => $itemPrice,
                    'discount_pct' => 0,
                    'tax_pct' => 10,
                    'line_total' => $itemPrice + ($itemPrice * 0.1),
                    'total_discount' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $invoiceId = DB::table('invoices')->insertGetId([
                'proforma_id' => $proformaId,
                'invoice_no' => 'INV_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'invoice_type' => 'booking_fee',
                'amount' => $subtotal,
                'due_date' => now()->addWeeks(4),
                'status' => 'paid',
                'issued_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $attachmentId = DB::table('attachments')->insertGetId([
                'type' => 'invoice_pdf',
                'file_path' => 'storage/invoices/INV_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT) . '.pdf',
                'mime_type' => 'application/pdf',
                'size' => 0,
                'uploaded_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('invoice_payments')->insert([
                'invoice_id' => $invoiceId,
                'paid_at' => now(),
                'amount' => $subtotal,
                'attachment_id' => $attachmentId,
                'confirmed_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->call(MeetingSeeder::class);
    }
}
