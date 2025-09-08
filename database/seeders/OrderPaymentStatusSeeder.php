<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Leads\Lead;

class OrderPaymentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $productId = DB::table('ref_products')->value('id');
        $userId = DB::table('users')->value('id');

        for ($i = 0; $i < 4; $i++) {
            $lead = Lead::factory()->create([
                'id' => 201 + $i,
            ]);

            // limit amount to stay within invoice decimal(8,2) column
            $total = $faker->numberBetween(150000, 500000);
            $perTerm = (int) round($total / 3);
            $taxPct = 11;
            $taxAmount = $total * $taxPct / 100;
            $grand = $total + $taxAmount;

            $quotationId = DB::table('quotations')->insertGetId([
                'lead_id' => $lead->id,
                'quotation_no' => 'QT_DEAL_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'status' => 'published',
                'subtotal' => $total,
                'tax_pct' => $taxPct,
                'tax_total' => $taxAmount,
                'grand_total' => $grand,
                'booking_fee' => $total * 0.1,
                'expiry_date' => now()->addWeeks(2),
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $itemPrice = $total / 2;
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

            // add-on item without product id
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

            $orderId = 201 + $i;
            DB::table('orders')->insert([
                'id' => $orderId,
                'lead_id' => $lead->id,
                'order_no' => 'ORDER_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'total_billing' => $total,
                'order_status' => $i === 3 ? 'done' : ($i === 0 ? 'in_progress' : 'confirmed'),
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
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            for ($term = 1; $term <= 3; $term++) {
                if ($term <= $i) {
                    $proformaId = DB::table('proformas')->insertGetId([
                        'quotation_id' => $quotationId,
                        'term_no'      => $term,
                        'proforma_type' => 'term_payment',
                        'proforma_no'   => sprintf('PROFORMA_%d_ORDER_%d', $term, $orderId),
                        'amount'        => $perTerm,
                        'status'        => 'confirmed',
                        'issued_at'     => now(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    DB::table('attachments')->insert([
                        'type' => 'proforma_pdf',
                        'file_path' => sprintf('storage/proformas/PROFORMA_%d_ORDER_%d.pdf', $term, $orderId),
                        'mime_type' => 'application/pdf',
                        'size' => 0,
                        'uploaded_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($term === $i + 1 && $i < 3) {
                    $proformaId = DB::table('proformas')->insertGetId([
                        'quotation_id' => $quotationId,
                        'term_no'      => $term,
                        'proforma_type' => 'term_payment',
                        'amount'        => $perTerm,
                        'status'        => 'pending',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                } else {
                    continue;
                }

                if ($term <= $i) {
                    $paymentAttachmentId = DB::table('attachments')->insertGetId([
                        'type' => 'payment_proof',
                        'file_path' => sprintf('storage/payments/PAYMENT_%d_ORDER_%d.pdf', $term, $orderId),
                        'mime_type' => 'application/pdf',
                        'size' => 0,
                        'uploaded_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('payment_confirmations')->insert([
                        'proforma_id' => $proformaId,
                        'payer_name' => $faker->company(),
                        'paid_at' => now(),
                        'amount' => $perTerm,
                        'attachment_id' => $paymentAttachmentId,
                        'confirmed_by' => $userId,
                        'confirmed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $invoiceId = DB::table('invoices')->insertGetId([
                        'proforma_id' => $proformaId,
                        'invoice_no' => sprintf('INVOICE_%d_ORDER_%d', $term, $orderId),
                        'invoice_type' => $term === 3 ? 'final' : 'down_payment',
                        'amount' => $perTerm,
                        'due_date' => now()->addWeeks(4),
                        'status' => 'paid',
                        'issued_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $invoiceAttachmentId = DB::table('attachments')->insertGetId([
                        'type' => 'invoice_pdf',
                        'file_path' => sprintf('storage/invoices/INVOICE_%d_ORDER_%d.pdf', $term, $orderId),
                        'mime_type' => 'application/pdf',
                        'size' => 0,
                        'uploaded_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('invoice_payments')->insert([
                        'invoice_id' => $invoiceId,
                        'paid_at' => now(),
                        'amount' => $perTerm,
                        'attachment_id' => $invoiceAttachmentId,
                        'confirmed_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
