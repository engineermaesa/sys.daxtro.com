<?php

use App\Models\Aftersales\RefSurveyTemplate;
use App\Models\Aftersales\TicketSatisfaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_survey_templates', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('version')->unique();
            $t->string('status', 16)->index();
            $t->string('title');
            $t->json('content');
            $t->unsignedInteger('lock_version')->default(0);
            foreach (['created_by_user_id', 'updated_by_user_id', 'activated_by_user_id'] as $column) {
                $t->foreignId($column)->nullable()->constrained('users')->nullOnDelete();
            }
            $t->timestamp('activated_at')->nullable();
            $t->timestamps();
        });
        Schema::create('survey_template_state', function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->foreignId('active_template_id')->nullable()->constrained('ref_survey_templates');
            $t->foreignId('draft_template_id')->nullable()->constrained('ref_survey_templates');
        });
        Schema::table('satisfaction_links', fn (Blueprint $t) => $t->foreignId('ref_survey_template_id')->nullable()->constrained('ref_survey_templates'));
        Schema::table('ticket_satisfactions', function (Blueprint $t) {
            $t->foreignId('ref_survey_template_id')->nullable()->constrained('ref_survey_templates');
            foreach (array_keys(TicketSatisfaction::ASPECTS) as $column) {
                $t->unsignedTinyInteger($column)->nullable()->change();
            }
        });
        Schema::create('ticket_satisfaction_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_satisfaction_id')->constrained('ticket_satisfactions')->cascadeOnDelete();
            $t->string('question_key', 64);
            $t->string('type', 16);
            $t->unsignedTinyInteger('value_number')->nullable();
            $t->text('value_text')->nullable();
            $t->json('value_options')->nullable();
            $t->timestamps();
            $t->unique(['ticket_satisfaction_id', 'question_key'], 'satisfaction_answer_question_unique');
            $t->index(['question_key', 'type']);
        });
        Schema::create('survey_template_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ref_survey_template_id')->constrained('ref_survey_templates');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('actor_name');
            $t->string('action');
            $t->json('changes')->nullable();
            $t->timestamp('created_at');
        });
        $template = RefSurveyTemplate::create(['version' => 1, 'status' => 'active', 'title' => 'Satisfaction Survey', 'content' => RefSurveyTemplate::initialContent(), 'activated_at' => now()]);
        DB::table('survey_template_state')->insert(['id' => 1, 'active_template_id' => $template->id]);
        DB::table('satisfaction_links')->update(['ref_survey_template_id' => $template->id]);
        DB::table('ticket_satisfactions')->update(['ref_survey_template_id' => $template->id]);
        DB::table('ticket_satisfactions')->orderBy('id')->chunkById(200, function ($responses) use ($template) {
            $rows = [];
            foreach ($responses as $response) {
                foreach ($template->questions() as $q) {
                    $key = $q['question_key'];
                    $value = $response->{$key} ?? null;
                    $rows[] = ['ticket_satisfaction_id' => $response->id, 'question_key' => $key, 'type' => $q['type'], 'value_number' => in_array($q['type'], ['rating', 'yes_no']) ? $value : null, 'value_text' => $q['type'] === 'long_text' ? $value : null, 'value_options' => null, 'created_at' => $response->created_at ?? now(), 'updated_at' => $response->updated_at ?? now()];
                }
            }
            if ($rows) {
                DB::table('ticket_satisfaction_answers')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        if (DB::table('ref_survey_templates')->where('version', '>', 1)->exists()) {
            throw new RuntimeException('Survey versions exist; rollback would discard historical answers.');
        }
        Schema::dropIfExists('survey_template_audits');
        Schema::dropIfExists('survey_template_state');
        Schema::dropIfExists('ticket_satisfaction_answers');
        Schema::table('satisfaction_links', fn (Blueprint $t) => $t->dropConstrainedForeignId('ref_survey_template_id'));
        Schema::table('ticket_satisfactions', fn (Blueprint $t) => $t->dropConstrainedForeignId('ref_survey_template_id'));
        Schema::dropIfExists('ref_survey_templates');
    }
};
