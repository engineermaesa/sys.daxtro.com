<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ref_agreement_templates', function (Blueprint $table) {
            $table->string('status', 16)->default('inactive')->index();
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('activated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
        });
        $active = DB::table('ref_agreement_templates')->whereNull('deleted_at')->where('is_active', true)->orderByDesc('version')->first();
        DB::table('ref_agreement_templates')->update(['is_active' => false]);
        if ($active) {
            DB::table('ref_agreement_templates')->where('id', $active->id)->update(['status' => 'active', 'is_active' => true, 'activated_at' => $active->created_at]);
        }

        Schema::create('agreement_template_state', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->foreignId('active_template_id')->nullable()->constrained('ref_agreement_templates');
            $table->foreignId('draft_template_id')->nullable()->constrained('ref_agreement_templates');
        });
        DB::table('agreement_template_state')->insert(['id' => 1, 'active_template_id' => $active?->id]);
        Schema::create('agreement_template_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ref_agreement_template_id')->constrained('ref_agreement_templates');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('action');
            $table->json('changes')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_template_audits');
        Schema::dropIfExists('agreement_template_state');
        Schema::table('ref_agreement_templates', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('updated_by_user_id');
            $table->dropConstrainedForeignId('activated_by_user_id');
            $table->dropColumn(['status', 'lock_version', 'activated_at']);
        });
    }
};
