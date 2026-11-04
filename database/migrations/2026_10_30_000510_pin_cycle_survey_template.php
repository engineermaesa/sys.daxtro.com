<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_cycles', fn (Blueprint $t) => $t->foreignId('survey_template_id')->nullable()->constrained('ref_survey_templates'));
        DB::table('ticket_cycles')->join('tickets', 'tickets.id', '=', 'ticket_cycles.ticket_id')
            ->where('tickets.workflow_version', 'v3')->where('ticket_cycles.documentation_status', 'documentation_published')
            ->select('ticket_cycles.*')->orderBy('ticket_cycles.id')->chunkById(200, function ($cycles) {
                foreach ($cycles as $cycle) {
                    $phone = DB::table('ticket_recomings')->where('cycle_id', $cycle->id)->where('recoming_type', 'phone_call')->exists();
                    $variant = $phone ? 'phone_call' : 'site';
                    $template = $phone ? null : DB::table('satisfaction_links')->where('cycle_id', $cycle->id)->orderByDesc('id')->value('ref_survey_template_id');
                    $template ??= DB::table('ref_survey_templates')->where('variant', $variant)->where('status', 'active')->value('id');
                    DB::table('ticket_cycles')->where('id', $cycle->id)->update(['survey_template_id' => $template]);
                }
            }, 'ticket_cycles.id', 'id');
    }

    public function down(): void
    {
        if (DB::table('ticket_cycles')->whereNotNull('survey_template_id')->exists()) {
            throw new RuntimeException('Pinned cycle survey templates exist; rollback would discard the cycle snapshot.');
        }
        Schema::table('ticket_cycles', fn (Blueprint $t) => $t->dropConstrainedForeignId('survey_template_id'));
    }
};
