<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_recomings')) {
            return;
        }

        DB::statement("ALTER TABLE ticket_logs MODIFY step ENUM('published','assigned','on_site','repair','waiting_sparepart','documentation_published','satisfaction_submitted','closed','recoming') NOT NULL");

        Schema::create('ticket_recomings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->unsignedInteger('visit_no');
            $table->enum('reason', ['sparepart_unavailable', 'further_work_needed', 'site_access', 'other']);
            $table->text('notes')->nullable();
            $table->timestamp('planned_visit_at');
            $table->string('from_step', 32);
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_deleted')->default(false);
        });

        Schema::table('ticket_satisfactions', function (Blueprint $table) {
            $table->index('ticket_id');
        });
        Schema::table('ticket_satisfactions', function (Blueprint $table) {
            $table->dropUnique(['ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_recomings');
        DB::statement("DELETE FROM ticket_logs WHERE step = 'recoming'");
        DB::statement("ALTER TABLE ticket_logs MODIFY step ENUM('published','assigned','on_site','repair','waiting_sparepart','documentation_published','satisfaction_submitted','closed') NOT NULL");
    }
};
