<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_customers', function (Blueprint $t) {
            $t->string('address_maps_url', 2048)->nullable();
            $t->string('source')->default('manual');
        });
        Schema::table('ref_product_types', fn (Blueprint $t) => $t->string('code_prefix')->nullable());
        Schema::table('service_customer_products', function (Blueprint $t) {
            // The original machine_name was renamed to product_id in September.
            $t->string('machine_name')->nullable();
            $t->string('brand')->nullable();
            $t->text('note')->nullable();
            $t->unsignedBigInteger('product_id')->nullable()->change();
            // Preserve legacy values while allowing Basic and Premium.
            $t->string('pm_contract_status')->default('none')->change();
        });
        Schema::table('ticket_logs', fn (Blueprint $t) => $t->string('step')->change());
        Schema::table('tickets', fn (Blueprint $t) => $t->boolean('happy_call_required')->default(false));
        // Existing tickets retain false; every ticket created after rollout defaults to true.
        Schema::table('tickets', fn (Blueprint $t) => $t->boolean('happy_call_required')->default(true)->change());

        Schema::create('customer_deals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_customer_id')->nullable()->constrained('service_customers');
            $t->foreignId('lead_id')->unique()->constrained('leads');
            $t->string('status')->default('linked');
            $t->json('candidates')->nullable();
            $t->json('lead_snapshot')->nullable();
            $t->json('pending_changes')->nullable();
            $t->timestamp('left_deal_at')->nullable();
            $t->timestamps();
        });
        Schema::create('customer_imports', function (Blueprint $t) {
            $t->id();
            $t->string('file_name');
            $t->string('status')->default('review');
            $t->unsignedInteger('lock_version')->default(0);
            $t->unsignedInteger('created_count')->default(0);
            $t->unsignedInteger('matched_count')->default(0);
            $t->unsignedInteger('error_count')->default(0);
            $t->foreignId('created_by_user_id')->constrained('users');
            $t->timestamp('applied_at')->nullable();
            $t->timestamps();
        });
        Schema::create('customer_import_rows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_import_id')->constrained('customer_imports')->cascadeOnDelete();
            $t->unsignedInteger('row_number');
            $t->json('input');
            $t->json('review');
            $t->json('decision')->nullable();
            $t->json('result')->nullable();
            $t->timestamps();
            $t->unique(['customer_import_id', 'row_number']);
        });
        Schema::create('aftersales_customer_audits', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->unsignedBigInteger('machine_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action');
            $t->json('changes');
            $t->timestamp('created_at');
        });

        foreach (['Tube' => 'TD', 'Cube' => 'CD', 'Block' => 'BD', 'Flake' => 'FD', 'Slurry' => 'SD', 'Cold Room' => 'RD', 'Reverse Osmosis' => 'RO'] as $name => $prefix) {
            $existing = DB::table('ref_product_types')->whereRaw('LOWER(name) = ?', [strtolower($name)])->orWhereRaw('LOWER(name) = ?', [strtolower($name.' Ice Machine')])->first();
            if ($existing) {
                DB::table('ref_product_types')->where('id', $existing->id)->update(['code_prefix' => $prefix]);
            } else {
                DB::table('ref_product_types')->insert(['name' => $name, 'code_prefix' => $prefix]);
            }
        }
        (new \Database\Seeders\AftersalesSupervisorSeeder)->run();

        Schema::table('ticket_satisfactions', function (Blueprint $t) {
            $t->json('score_original')->nullable();
            $t->unsignedTinyInteger('scale_version')->default(3);
        });
        Schema::table('ticket_satisfaction_answers', fn (Blueprint $t) => $t->integer('score_original')->nullable());
        DB::table('ticket_satisfactions')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $original = $updates = [];
                foreach (array_keys(\App\Models\Aftersales\TicketSatisfaction::ASPECTS) as $key) {
                    $original[$key] = $row->{$key};
                    $updates[$key] = $row->{$key} === null ? null : $this->score((int) $row->{$key});
                }
                DB::table('ticket_satisfactions')->where('id', $row->id)->update($updates + ['score_original' => json_encode($original)]);
            }
        });
        DB::table('ticket_satisfaction_answers')->where('type', 'rating')->whereNotNull('value_number')->update([
            'score_original' => DB::raw('value_number'),
            'value_number' => DB::raw('CASE WHEN value_number <= 2 THEN 1 WHEN value_number = 3 THEN 2 ELSE 3 END'),
        ]);
        DB::table('ref_survey_templates')->orderBy('id')->each(function ($row) {
            $content = json_decode($row->content, true);
            $content['scale_labels'] = ['Kurang Puas', 'Puas', 'Sangat Puas'];
            DB::table('ref_survey_templates')->where('id', $row->id)->update(['content' => json_encode($content, JSON_UNESCAPED_UNICODE)]);
        });
    }

    private function score(int $score): int
    {
        return $score <= 2 ? 1 : ($score === 3 ? 2 : 3);
    }

    public function down(): void
    {
        throw new RuntimeException('Data migration is irreversible. Restore the pre-rollout backup to roll back.');
    }
};
