<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ticket_satisfactions', 'machine_ok')) {
            return;
        }

        Schema::table('ticket_satisfactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('work_speed')->nullable()->after('solution_quality');
            $table->unsignedTinyInteger('explanation_quality')->nullable()->after('work_speed');
            $table->unsignedTinyInteger('overall_satisfaction')->nullable()->after('explanation_quality');
            $table->unsignedTinyInteger('grooming')->nullable()->after('overall_satisfaction');

            $table->boolean('machine_ok')->nullable()->after('grooming');
            $table->text('machine_issue_note')->nullable()->after('machine_ok');
            $table->text('feedback')->nullable()->after('machine_issue_note');
            $table->string('signer_name')->nullable()->after('feedback');
            $table->string('signer_position')->nullable()->after('signer_name');

            $table->enum('source', ['link', 'admin'])->default('admin')->after('signer_position');
            $table->enum('review_status', ['normal', 'needs_review', 'reviewed'])->default('normal')->after('source');
            $table->json('review_reasons')->nullable()->after('review_status');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('review_reasons')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');

            $table->timestamp('otp_verified_at')->nullable()->after('reviewed_at');
            $table->string('otp_phone_masked', 32)->nullable()->after('otp_verified_at');
            $table->foreignId('link_created_by_user_id')->nullable()->after('otp_phone_masked')->constrained('users')->nullOnDelete();
            $table->string('shared_via', 32)->nullable()->after('link_created_by_user_id');
            $table->string('ip_address', 45)->nullable()->after('shared_via');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->unsignedInteger('duration_seconds')->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_satisfactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropConstrainedForeignId('link_created_by_user_id');
            $table->dropColumn([
                'work_speed', 'explanation_quality', 'overall_satisfaction', 'grooming',
                'machine_ok', 'machine_issue_note', 'feedback', 'signer_name', 'signer_position',
                'source', 'review_status', 'review_reasons', 'reviewed_at',
                'otp_verified_at', 'otp_phone_masked', 'shared_via', 'ip_address', 'user_agent', 'duration_seconds',
            ]);
        });
    }
};
