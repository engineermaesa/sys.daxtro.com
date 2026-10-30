<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_diagnosis_entries', function (Blueprint $t) {
            $t->foreignId('cycle_id')->nullable()->constrained('ticket_cycles')->restrictOnDelete();
            $t->unique(['ticket_service_form_id', 'field', 'cycle_id'], 'diagnosis_form_field_cycle');
        });
        Schema::table('ticket_cycles', function (Blueprint $t) {
            $t->timestamp('service_form_first_edited_at')->nullable();
            $t->foreignId('service_form_first_edited_by')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('tickets')->orderBy('id')->chunkById(200, function ($tickets) {
            foreach ($tickets as $ticket) {
                $cycles = DB::table('ticket_cycles')->where('ticket_id', $ticket->id)->pluck('id');
                if ($cycles->count() !== 1) {
                    continue;
                }
                $forms = DB::table('ticket_service_forms')->where('ticket_id', $ticket->id)->pluck('id');
                
                foreach ($forms as $formId) {
                    $ambiguous = DB::table('ticket_diagnosis_entries')->where('ticket_service_form_id', $formId)
                        ->select('field')->groupBy('field')->havingRaw('COUNT(*) > 1')->exists();
                    if (! $ambiguous) {
                        DB::table('ticket_diagnosis_entries')->where('ticket_service_form_id', $formId)->update(['cycle_id' => $cycles->first()]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        if (DB::table('ticket_diagnosis_entries as d')->join('ticket_cycles as c', 'c.id', '=', 'd.cycle_id')->where('c.cycle_no', '>', 1)->exists()) {
            throw new RuntimeException('Cycle-specific Recoming findings exist; removing their ownership would lose service history. Use an explicit rollback migration.');
        }
        Schema::table('ticket_diagnosis_entries', function (Blueprint $t) {
            $t->dropUnique('diagnosis_form_field_cycle');
            $t->dropConstrainedForeignId('cycle_id');
        });
        Schema::table('ticket_cycles', function (Blueprint $t) {
            $t->dropConstrainedForeignId('service_form_first_edited_by');
            $t->dropColumn('service_form_first_edited_at');
        });
    }
};
