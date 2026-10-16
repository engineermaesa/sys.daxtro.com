<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\RefAgreementTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgreementTemplates
{
    public function createDraft(User $actor): RefAgreementTemplate
    {
        return DB::transaction(function () use ($actor) {
            $state = DB::table('agreement_template_state')->where('id', 1)->lockForUpdate()->first();
            if ($state->draft_template_id) {
                return RefAgreementTemplate::findOrFail($state->draft_template_id);
            }
            $source = RefAgreementTemplate::find($state->active_template_id);
            if (! $source) {
                throw ValidationException::withMessages(['template' => 'Seed the initial agreement template before creating a draft.']);
            }
            $draft = RefAgreementTemplate::create([
                'version' => RefAgreementTemplate::withTrashed()->max('version') + 1,
                'title' => $source->title, 'sections' => $source->editableSections(),
                'closing_statement' => $source->closing_statement,
                'status' => 'draft', 'is_active' => false, 'lock_version' => 0,
                'created_by_user_id' => $actor->id, 'updated_by_user_id' => $actor->id,
            ]);
            DB::table('agreement_template_state')->where('id', 1)->update(['draft_template_id' => $draft->id]);
            $this->audit($draft, $actor, 'template_draft_created', ['source_version' => $source->version]);

            return $draft;
        }, 3);
    }

    public function save(int $id, array $input, User $actor): RefAgreementTemplate
    {
        return DB::transaction(function () use ($id, $input, $actor) {
            $draft = RefAgreementTemplate::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft, $input['lock_version']);
            $sections = $this->normalize($input['sections'], $draft->editableSections());
            $before = $draft->only(['title', 'closing_statement', 'sections']);
            $draft->fill(['title' => $input['title'], 'closing_statement' => $input['closing_statement'] ?? null, 'sections' => $sections]);
            $draft->lock_version++;
            $draft->updated_by_user_id = $actor->id;
            $draft->save();
            $this->audit($draft, $actor, 'template_draft_saved', ['before' => $before, 'after' => $draft->only(['title', 'closing_statement', 'sections'])]);

            return $draft;
        }, 3);
    }

    public function activate(int $id, int $revision, User $actor): RefAgreementTemplate
    {
        return DB::transaction(function () use ($id, $revision, $actor) {
            $state = DB::table('agreement_template_state')->where('id', 1)->lockForUpdate()->first();
            $draft = RefAgreementTemplate::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft, $revision);
            abort_unless((int) $state->draft_template_id === $draft->id, 409, 'This draft is no longer current. Reload the page.');
            $active = $draft->documentSections();
            if (! count($active)) {
                throw ValidationException::withMessages(['sections' => 'At least one active section is required.']);
            }
            foreach ($active as $s) {
                if (trim($s['title']) === '' || ! count($s['clauses'])) {
                    throw ValidationException::withMessages(['sections' => 'Every active section needs a title and at least one active clause.']);
                }
                foreach ($s['clauses'] as $c) {
                    if (trim($c['text']) === '') {
                        throw ValidationException::withMessages(['sections' => 'Every active clause needs text.']);
                    }
                }
            }
            if ($state->active_template_id) {
                RefAgreementTemplate::whereKey($state->active_template_id)->update(['status' => 'inactive', 'is_active' => false]);
            }
            $draft->fill(['status' => 'active', 'is_active' => true, 'activated_at' => now(), 'activated_by_user_id' => $actor->id, 'updated_by_user_id' => $actor->id, 'lock_version' => $draft->lock_version + 1])->save();
            DB::table('agreement_template_state')->where('id', 1)->update(['active_template_id' => $id, 'draft_template_id' => null]);
            $this->audit($draft, $actor, 'template_activated', ['previous_template_id' => $state->active_template_id]);

            return $draft;
        }, 3);
    }

    private function assertDraft(RefAgreementTemplate $template, int $revision): void
    {
        abort_unless($template->status === 'draft', 409, 'Only draft versions can be edited. Create a draft from the active version.');
        abort_unless($template->lock_version === $revision, 409, 'This draft was changed by another user. Reload before saving.');
    }

    private function normalize(array $input, array $previous): array
    {
        $oldSections = collect($previous)->pluck('section_key')->all();
        $oldClauses = collect($previous)->flatMap(fn ($s) => collect($s['clauses'])->pluck('key'))->all();
        $seenSections = $seenClauses = [];
        $result = [];
        foreach ($input as $i => $s) {
            $key = $s['section_key'] ?? null;
            if ($key !== null && (! in_array($key, $oldSections, true) || in_array($key, $seenSections, true))) {
                throw ValidationException::withMessages(['sections' => 'Section keys are immutable and must be unique.']);
            }
            $key ??= 's_'.Str::uuid();
            $seenSections[] = $key;
            $clauses = [];
            foreach ($s['clauses'] as $j => $c) {
                $clauseKey = $c['key'] ?? null;
                if ($clauseKey !== null && (! in_array($clauseKey, $oldClauses, true) || in_array($clauseKey, $seenClauses, true))) {
                    throw ValidationException::withMessages(['sections' => 'Clause keys are immutable and must be unique.']);
                }
                $clauseKey ??= 'c_'.Str::uuid();
                $seenClauses[] = $clauseKey;
                $clauses[] = ['key' => $clauseKey, 'text' => trim($c['text'] ?? ''), 'is_active' => $c['is_active'], 'sort_order' => $j];
            }
            $result[] = ['section_key' => $key, 'code' => RefAgreementTemplate::sectionCode($i), 'title' => trim($s['title'] ?? ''), 'is_active' => $s['is_active'], 'sort_order' => $i, 'clauses' => $clauses];
        }
        if (array_diff($oldSections, $seenSections) || array_diff($oldClauses, $seenClauses)) {
            throw ValidationException::withMessages(['sections' => 'Existing sections and clauses cannot be deleted. Disable them instead.']);
        }

        return $result;
    }

    private function audit(RefAgreementTemplate $template, User $actor, string $action, array $changes): void
    {
        DB::table('agreement_template_audits')->insert([
            'ref_agreement_template_id' => $template->id, 'user_id' => $actor->id,
            'actor_name' => $actor->name, 'action' => $action, 'changes' => json_encode($changes, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
