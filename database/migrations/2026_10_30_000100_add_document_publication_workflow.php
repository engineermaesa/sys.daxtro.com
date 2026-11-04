<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ticket_service_forms', 'ticket_agreements'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->string('status', 40)->default('draft')->change());
        }
        Schema::table('ticket_cycles', function (Blueprint $t) {
            $t->string('documentation_status', 40)->default('incomplete');
            $t->timestamp('service_form_submitted_at')->nullable();
            $t->foreignId('service_form_submitted_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('ticket_document_publications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('cycle_id')->unique();
            $t->foreign(['ticket_id', 'cycle_id'])->references(['ticket_id', 'id'])->on('ticket_cycles')->restrictOnDelete();
            $t->foreignId('ticket_log_id')->nullable()->constrained('ticket_logs')->nullOnDelete();
            $t->foreignId('satisfaction_link_id')->nullable()->constrained('satisfaction_links')->nullOnDelete();
            $t->timestamp('published_at');
            $t->timestamps();
        });
        Schema::table('ticket_document_dispatches', fn (Blueprint $t) => $t->foreignId('publication_id')->nullable()->constrained('ticket_document_publications')->restrictOnDelete());

        DB::table('ticket_logs')->where('step', 'documentation_published')->whereNull('deleted_at')->whereNotNull('cycle_id')
            ->orderBy('id')->chunkById(200, function ($logs) {
                foreach ($logs as $log) {
                    DB::table('ticket_document_publications')->insertOrIgnore([
                        'ticket_id' => $log->ticket_id, 'cycle_id' => $log->cycle_id,
                        'ticket_log_id' => $log->id, 'published_at' => $log->created_at ?? now(),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('ticket_cycles')->where('id', $log->cycle_id)->update(['documentation_status' => 'documentation_published']);
                }
            });
    }

    public function down(): void
    {
        Schema::table('ticket_document_dispatches', fn (Blueprint $t) => $t->dropConstrainedForeignId('publication_id'));
        Schema::dropIfExists('ticket_document_publications');
        Schema::table('ticket_cycles', function (Blueprint $t) {
            $t->dropConstrainedForeignId('service_form_submitted_by');
            $t->dropColumn(['documentation_status', 'service_form_submitted_at']);
        });
        foreach (['ticket_service_forms', 'ticket_agreements'] as $name) {
            DB::table($name)->where('status', 'awaiting_spv_signature')->update(['status' => 'draft']);
            Schema::table($name, fn (Blueprint $t) => $t->enum('status', ['draft', 'saved'])->default('draft')->change());
        }
    }
};
