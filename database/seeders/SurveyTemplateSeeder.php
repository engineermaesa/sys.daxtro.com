<?php

namespace Database\Seeders;

use App\Models\Aftersales\RefSurveyTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SurveyTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            DB::table('survey_template_state')->where('id', 1)->lockForUpdate()->first();
            if (RefSurveyTemplate::exists()) {
                return;
            }
            $template = RefSurveyTemplate::create(['version' => 1, 'status' => 'active', 'title' => 'Satisfaction Survey', 'content' => RefSurveyTemplate::initialContent(), 'activated_at' => now()]);
            DB::table('survey_template_state')->where('id', 1)->update(['active_template_id' => $template->id]);
        });
    }
}
