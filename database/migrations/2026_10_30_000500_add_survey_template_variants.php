<?php

use App\Models\Aftersales\RefSurveyTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ref_survey_templates', fn (Blueprint $t) => $t->string('variant', 16)->default('site')->index());
        Schema::table('survey_template_state', fn (Blueprint $t) => $t->string('variant', 16)->default('site')->unique());
        DB::transaction(function () {
            $template = RefSurveyTemplate::create(['variant' => 'phone_call', 'version' => RefSurveyTemplate::max('version') + 1,
                'status' => 'active', 'title' => 'Phone Call Satisfaction Survey', 'content' => RefSurveyTemplate::phoneContent(), 'activated_at' => now()]);
            DB::table('survey_template_state')->insert(['id' => 2, 'variant' => 'phone_call', 'active_template_id' => $template->id]);
        });
    }

    public function down(): void
    {
        $ids = DB::table('ref_survey_templates')->where('variant', 'phone_call')->pluck('id');
        $pinned = Schema::hasColumn('ticket_cycles', 'survey_template_id')
            && DB::table('ticket_cycles')->whereIn('survey_template_id', $ids)->exists();
        if ($pinned || DB::table('ticket_satisfactions')->whereIn('ref_survey_template_id', $ids)->exists()
            || DB::table('satisfaction_links')->whereIn('ref_survey_template_id', $ids)->exists()
            || DB::table('survey_template_audits')->whereIn('ref_survey_template_id', $ids)->exists()) {
            throw new RuntimeException('Phone-call survey history exists; rollback requires an explicit preservation migration.');
        }
        DB::table('survey_template_state')->where('variant', 'phone_call')->delete();
        DB::table('ref_survey_templates')->whereIn('id', $ids)->delete();
        Schema::table('survey_template_state', function (Blueprint $t) {
            $t->dropUnique(['variant']);
            $t->dropColumn('variant');
        });
        Schema::table('ref_survey_templates', function (Blueprint $t) {
            $t->dropIndex(['variant']);
            $t->dropColumn('variant');
        });
    }
};
