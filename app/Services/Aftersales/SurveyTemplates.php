<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\RefSurveyTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SurveyTemplates
{
    public function createDraft(User $actor): RefSurveyTemplate
    {
        return DB::transaction(function () use ($actor) {
            $state = DB::table('survey_template_state')->where('id', 1)->lockForUpdate()->first();
            if ($state->draft_template_id) {
                return RefSurveyTemplate::findOrFail($state->draft_template_id);
            }
            $source = RefSurveyTemplate::findOrFail($state->active_template_id);
            $draft = RefSurveyTemplate::create($source->only(['title', 'content']) + ['version' => RefSurveyTemplate::max('version') + 1, 'status' => 'draft', 'lock_version' => 0, 'created_by_user_id' => $actor->id, 'updated_by_user_id' => $actor->id]);
            DB::table('survey_template_state')->where('id', 1)->update(['draft_template_id' => $draft->id]);
            $this->audit($draft, $actor, 'template_draft_created', ['source_version' => $source->version]);

            return $draft;
        }, 3);
    }

    public function save(int $id, array $input, User $actor): RefSurveyTemplate
    {
        return DB::transaction(function () use ($id, $input, $actor) {
            $draft = RefSurveyTemplate::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft, $input['lock_version']);
            $before = $draft->only(['title', 'content']);
            $draft->fill(['title' => $input['title'], 'content' => $this->normalize($input['content'], $draft->content), 'lock_version' => $draft->lock_version + 1, 'updated_by_user_id' => $actor->id])->save();
            $this->audit($draft, $actor, 'template_draft_saved', ['before' => $before, 'after' => $draft->only(['title', 'content'])]);

            return $draft;
        }, 3);
    }

    public function activate(int $id, int $revision, User $actor): RefSurveyTemplate
    {
        return DB::transaction(function () use ($id, $revision, $actor) {
            $state = DB::table('survey_template_state')->where('id', 1)->lockForUpdate()->first();
            $draft = RefSurveyTemplate::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft, $revision);
            abort_unless((int) $state->draft_template_id === $id, 409, 'The current draft changed. Reload the page.');
            $this->validateActivation($draft);
            RefSurveyTemplate::whereKey($state->active_template_id)->update(['status' => 'inactive']);
            $draft->fill(['status' => 'active', 'activated_at' => now(), 'activated_by_user_id' => $actor->id, 'updated_by_user_id' => $actor->id, 'lock_version' => $draft->lock_version + 1])->save();
            DB::table('survey_template_state')->where('id', 1)->update(['active_template_id' => $id, 'draft_template_id' => null]);
            $this->audit($draft, $actor, 'template_activated', ['previous_template_id' => $state->active_template_id]);

            return $draft;
        }, 3);
    }

    private function normalize(array $input, array $previous): array
    {
        $oldSections = collect($previous['sections'])->pluck('section_key')->all();
        $oldQuestions = collect($previous['sections'])->flatMap(fn ($s) => $s['questions'])->keyBy('question_key');
        $seenSections = $seenQuestions = [];
        $sections = [];
        foreach ($input['sections'] as $i => $s) {
            $key = $s['section_key'] ?? null;
            if ($key !== null && (! in_array($key, $oldSections, true) || in_array($key, $seenSections, true))) {
                throw ValidationException::withMessages(['content' => 'Section keys are immutable and unique.']);
            }
            $key ??= 's_'.Str::uuid();
            $seenSections[] = $key;
            $questions = [];
            foreach ($s['questions'] as $j => $q) {
                $qKey = $q['question_key'] ?? null;
                if ($qKey !== null && (! $oldQuestions->has($qKey) || in_array($qKey, $seenQuestions, true))) {
                    throw ValidationException::withMessages(['content' => 'Question keys are immutable and unique.']);
                }
                $qKey ??= 'q_'.Str::uuid();
                $seenQuestions[] = $qKey;
                $core = (bool) ($oldQuestions->get($qKey)['is_core'] ?? false);
                if ($core && (! $q['is_active'] || ! $q['required'] || $q['type'] !== 'yes_no' || ! $s['is_active'])) {
                    throw ValidationException::withMessages(['content' => 'The core machine condition question must remain active, required, and Yes/No in an active section.']);
                }
                $questions[] = ['question_key' => $qKey, 'label' => trim($q['label'] ?? ''), 'hint' => trim($q['hint'] ?? ''), 'type' => $q['type'], 'options' => $q['type'] === 'choice' ? array_values(array_unique($q['options'])) : [], 'required' => $q['required'], 'is_active' => $q['is_active'], 'is_core' => $core, 'sort_order' => $j];
            }
            $sections[] = ['section_key' => $key, 'title' => trim($s['title'] ?? ''), 'is_active' => $s['is_active'], 'questions' => $questions, 'sort_order' => $i];
        }
        if (array_diff($oldSections, $seenSections) || array_diff($oldQuestions->keys()->all(), $seenQuestions)) {
            throw ValidationException::withMessages(['content' => 'Existing sections and questions cannot be deleted. Disable them instead.']);
        }

        return ['scale_labels' => array_map(fn ($label) => trim($label ?? ''), array_values($input['scale_labels'])), 'sections' => $sections];
    }

    public function validateActivation(RefSurveyTemplate $template): void
    {
        $fail = fn ($message) => throw ValidationException::withMessages(['content' => $message]);
        if (count($template->content['scale_labels']) !== 3 || count(array_filter($template->content['scale_labels'], fn ($s) => blank($s)))) {
            $fail('All three rating scale labels are required.');
        }
        $questions = $template->questions();
        if (! collect($questions)->contains('type', 'rating')) {
            $fail('At least one active rating question is required.');
        }
        $core = collect($questions)->where('is_core', true);
        if ($core->count() !== 1 || $core->first()['question_key'] !== 'machine_ok' || $core->first()['type'] !== 'yes_no' || ! $core->first()['required']) {
            $fail('The core machine condition question is required.');
        }
        foreach ($template->documentSections() as $s) {
            if (blank($s['title']) || ! count($s['questions'])) {
                $fail('Each active section needs a title and an active question.');
            }
            foreach ($s['questions'] as $q) {
                if (blank($q['label'])) {
                    $fail('Active questions need labels.');
                }
                if ($q['type'] === 'choice' && (! count($q['options']) || count(array_filter($q['options'], fn ($v) => blank($v))))) {
                    $fail('Choice questions need nonempty options.');
                }
            }
        }
    }

    private function assertDraft(RefSurveyTemplate $template, int $revision): void
    {
        abort_unless($template->status === 'draft', 409, 'Only draft versions can be edited.');
        abort_unless($template->lock_version === $revision, 409, 'Another user changed this draft. Reload before saving.');
    }

    private function audit(RefSurveyTemplate $template, User $actor, string $action, array $changes): void
    {
        DB::table('survey_template_audits')->insert(['ref_survey_template_id' => $template->id, 'user_id' => $actor->id, 'actor_name' => $actor->name, 'action' => $action, 'changes' => json_encode($changes, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
