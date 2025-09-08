<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        $userId = DB::table('users')->value('id');

        // seed meeting expense
        $leadId = DB::table('leads')->value('id');
        $meetingId = DB::table('lead_meetings')->insertGetId([
            'lead_id'            => $leadId,
            'is_online'          => false,
            'scheduled_start_at' => now(),
            'scheduled_end_at'   => now()->addHour(),
            'city'               => 'Jakarta',
            'address'            => 'Jl. Example',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $meetingExpenseId = DB::table('meeting_expenses')->insertGetId([
            'meeting_id'   => $meetingId,
            'sales_id'     => $userId,
            'amount'       => 100,
            'status'       => 'submitted',
            'requested_at' => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('finance_requests')->insert([
            'request_type' => 'meeting-expense',
            'reference_id' => $meetingExpenseId,
            'requester_id' => $userId,
            'status'       => 'pending',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // one invoice request
        $orderId = DB::table('orders')->value('id');
        DB::table('finance_requests')->insert([
            'request_type' => 'invoice',
            'reference_id' => $orderId.'-1',
            'requester_id' => $userId,
            'status'       => 'pending',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // seed payment confirmation request
        $proformaId = DB::table('proformas')->value('id');
        $attachmentId = DB::table('attachments')->value('id');
        $paymentId = DB::table('payment_confirmations')->insertGetId([
            'proforma_id'  => $proformaId,
            'payer_name'   => 'Seeder Customer',
            'paid_at'      => now(),
            'amount'       => DB::table('proformas')->where('id', $proformaId)->value('amount'),
            'attachment_id'=> $attachmentId,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('finance_requests')->insert([
            'request_type' => 'payment-confirmation',
            'reference_id' => $paymentId,
            'requester_id' => $userId,
            'status'       => 'pending',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

    }
}
