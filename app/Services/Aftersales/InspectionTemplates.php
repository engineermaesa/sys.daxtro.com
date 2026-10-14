<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\RefAgreementTemplate;
use App\Models\Aftersales\RefInspectionField;
use App\Models\Aftersales\RefInspectionGroup;
use App\Models\Aftersales\RefInspectionItem;
use App\Models\Aftersales\RefInspectionTemplate;
use App\Models\Masters\ProductType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InspectionTemplates
{
    public const DIAGNOSIS = [
        'problem' => ['label' => 'Problem', 'hint' => 'Describe the problem found during inspection.'],
        'cause' => ['label' => 'Root Cause', 'hint' => 'Describe the main cause of the problem.'],
        'action' => ['label' => 'Corrective Action', 'hint' => 'Describe the corrective action performed.'],
    ];

    public function importLegacy(): RefInspectionTemplate
    {
        return DB::transaction(function () {
            DB::table('inspection_template_state')->where('id', 1)->lockForUpdate()->first();
            if ($existing = RefInspectionTemplate::where('version', 1)->first()) {
                return $existing;
            }
            $items = RefInspectionItem::withTrashed()->with('fields')->orderBy('number')->get();
            $types = ProductType::all();
            $groups = [];
            foreach ($items->groupBy('group_code') as $code => $rows) {
                $groups[] = ['group_key' => 'legacy_group_'.$code, 'title' => RefInspectionItem::GROUPS[$code] ?? $code, 'is_active' => true, 'sort_order' => count($groups), 'items' => $rows->map(function ($item) use ($types) {
                    return ['item_key' => 'legacy_item_'.$item->id, 'name' => $item->name, 'standard' => $item->standard ?? '', 'is_active' => ! $item->trashed(), 'types_restricted' => ! empty($item->only_for_types),
                        'machine_types' => $types->filter(fn ($type) => $item->appliesTo($type->name))->pluck('id')->map(fn ($id) => (int) $id)->when(empty($item->only_for_types), fn () => collect())->values()->all(),
                        'fields' => $item->fields->map(fn ($f) => ['field_key' => 'legacy_field_'.$f->id, 'key' => $f->key, 'label' => $f->label, 'type' => $f->type, 'units' => $f->unit ? [$f->unit] : [], 'options' => $f->options ?? [], 'required_when' => $f->required_when, 'is_multiple' => false, 'min_value' => null, 'max_value' => null, 'is_active' => true])->all()];
                })->all()];
            }
            $template = RefInspectionTemplate::create(['version' => 1, 'status' => 'active', 'title' => 'Service Inspection & Report', 'groups' => $groups, 'diagnosis_section_title' => 'Analysis, Root Cause & Corrective Action', 'diagnosis_fields' => self::DIAGNOSIS, 'activated_at' => now()]);
            foreach ($groups as $i => $g) {
                $group = RefInspectionGroup::create(['ref_inspection_template_id' => $template->id, 'group_key' => $g['group_key'], 'title' => $g['title'], 'code' => RefAgreementTemplate::sectionCode($i), 'sort_order' => $i]);
                foreach ($g['items'] as $item) {
                    $id = (int) str_replace('legacy_item_', '', $item['item_key']);
                    DB::table('ref_inspection_items')->where('id', $id)->update(['ref_inspection_template_id' => $template->id, 'ref_inspection_group_id' => $group->id, 'item_key' => $item['item_key'], 'machine_types' => json_encode($item['machine_types']), 'types_restricted' => $item['types_restricted']]);
                    foreach ($item['fields'] as $f) {
                        DB::table('ref_inspection_fields')->where('id', (int) str_replace('legacy_field_', '', $f['field_key']))->update(['field_key' => $f['field_key'], 'units' => json_encode($f['units'])]);
                    }
                }
            }
            DB::table('inspection_template_state')->where('id', 1)->update(['active_template_id' => $template->id]);
            DB::table('ticket_service_forms')->whereNull('ref_inspection_template_id')->update(['ref_inspection_template_id' => $template->id, 'template_version' => 1]);

            return $template;
        }, 3);
    }

    public function createDraft(User $actor): RefInspectionTemplate
    {
        return DB::transaction(function () use ($actor) {
            $state = DB::table('inspection_template_state')->where('id', 1)->lockForUpdate()->first();
            if ($state->draft_template_id) {
                return RefInspectionTemplate::findOrFail($state->draft_template_id);
            }
            $source = RefInspectionTemplate::find($state->active_template_id);
            if (! $source) {
                throw ValidationException::withMessages(['template' => 'Seed the initial inspection template first.']);
            }
            $draft = RefInspectionTemplate::create($source->only(['title', 'groups', 'diagnosis_section_title', 'diagnosis_fields']) + ['version' => RefInspectionTemplate::max('version') + 1, 'status' => 'draft', 'lock_version' => 0, 'created_by_user_id' => $actor->id, 'updated_by_user_id' => $actor->id]);
            DB::table('inspection_template_state')->where('id', 1)->update(['draft_template_id' => $draft->id]);
            $this->audit($draft, $actor, 'template_draft_created', ['source_version' => $source->version]);

            return $draft;
        }, 3);
    }

    public function save(int $id, array $input, User $actor): RefInspectionTemplate
    {
        return DB::transaction(function () use ($id, $input, $actor) {
            $draft = RefInspectionTemplate::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft, $input['lock_version']);
            $before = $draft->only(['title', 'groups', 'diagnosis_section_title', 'diagnosis_fields']);
            $diagnosis = [];
            foreach (array_keys(self::DIAGNOSIS) as $key) {
                $diagnosis[$key] = ['label' => trim($input['diagnosis_fields'][$key]['label'] ?? ''), 'hint' => trim($input['diagnosis_fields'][$key]['hint'] ?? '')];
            }
            $draft->fill(['title' => $input['title'], 'groups' => $this->normalize($input['groups'], $draft->groups), 'diagnosis_section_title' => trim($input['diagnosis_section_title'] ?? ''), 'diagnosis_fields' => $diagnosis, 'lock_version' => $draft->lock_version + 1, 'updated_by_user_id' => $actor->id])->save();
            $this->audit($draft, $actor, 'template_draft_saved', ['before' => $before, 'after' => $draft->only(array_keys($before))]);

            return $draft;
        }, 3);
    }

    public function activate(int $id, int $revision, User $actor): RefInspectionTemplate
    {
        return DB::transaction(function () use ($id, $revision, $actor) {
            $state = DB::table('inspection_template_state')->where('id', 1)->lockForUpdate()->first();
            $draft = RefInspectionTemplate::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft, $revision);
            abort_unless((int) $state->draft_template_id === $id, 409, 'The draft changed. Reload the page.');
            $this->validateActivation($draft);
            $this->materialize($draft);
            if ($state->active_template_id) {
                RefInspectionTemplate::whereKey($state->active_template_id)->update(['status' => 'inactive']);
            }
            $draft->fill(['status' => 'active', 'activated_at' => now(), 'activated_by_user_id' => $actor->id, 'updated_by_user_id' => $actor->id, 'lock_version' => $draft->lock_version + 1])->save();
            DB::table('inspection_template_state')->where('id', 1)->update(['active_template_id' => $id, 'draft_template_id' => null]);
            $this->audit($draft, $actor, 'template_activated', ['previous_template_id' => $state->active_template_id]);

            return $draft;
        }, 3);
    }

    public function validateActivation(RefInspectionTemplate $template): void
    {
        $fail = fn ($message) => throw ValidationException::withMessages(['template' => $message]);
        $groups = $template->documentGroups();
        if (! count($groups)) {
            $fail('At least one active group is required.');
        }
        if (blank($template->diagnosis_section_title)) {
            $fail('The diagnosis section needs a title.');
        }
        foreach (self::DIAGNOSIS as $key => $unused) {
            if (blank($template->diagnosis_fields[$key]['label']) || blank($template->diagnosis_fields[$key]['hint'])) {
                $fail('All three diagnosis fields need labels and hints.');
            }
        }
        foreach ($groups as $g) {
            if (blank($g['title']) || ! count($g['items'])) {
                $fail('Every active group needs a title and at least one active item.');
            }
            foreach ($g['items'] as $item) {
                if (blank($item['name']) || ! count($item['fields'])) {
                    $fail('Every active item needs a name and at least one active field.');
                }
                if (count(array_unique(array_column($item['fields'], 'key'))) !== count($item['fields'])) {
                    $fail('An item cannot contain conflicting field keys.');
                }
                foreach ($item['fields'] as $f) {
                    if (blank($f['label'])) {
                        $fail('Every active field needs a label.');
                    }
                    if ($f['type'] === 'checklist' && (! count($f['options']) || count(array_filter($f['options'], fn ($v) => blank($v))))) {
                        $fail('Checklist fields need nonempty options.');
                    }
                    if ($f['min_value'] !== null && $f['max_value'] !== null && $f['min_value'] > $f['max_value']) {
                        $fail('Minimum value cannot exceed maximum value.');
                    }
                    if ($f['required_when'] && ! collect($item['fields'])->contains(fn ($other) => $other['type'] === 'checklist' && in_array($f['required_when'], $other['options'], true))) {
                        $fail('The conditional requirement must refer to an active checklist option in the same item.');
                    }
                }
            }
        }
    }

    private function normalize(array $input, array $previous): array
    {
        $old = ['group_key' => [], 'item_key' => [], 'field_key' => []];
        foreach ($previous as $g) {
            $old['group_key'][] = $g['group_key'];
            foreach ($g['items'] as $i) {
                $old['item_key'][] = $i['item_key'];
                foreach ($i['fields'] as $f) {
                    $old['field_key'][$f['field_key']] = $f['key'];
                }
            }
        }
        $seen = ['group_key' => [], 'item_key' => [], 'field_key' => []];
        $key = function ($entry, $kind) use ($old, &$seen) {
            $value = $entry[$kind] ?? null;
            $allowed = $kind === 'field_key' ? array_keys($old[$kind]) : $old[$kind];
            if ($value !== null && (! in_array($value, $allowed, true) || in_array($value, $seen[$kind], true))) {
                throw ValidationException::withMessages(['groups' => 'Template keys are immutable and unique.']);
            }
            $value ??= substr($kind, 0, 1).'_'.Str::uuid();
            $seen[$kind][] = $value;

            return $value;
        };
        $groups = [];
        foreach ($input as $gi => $g) {
            $groupKey = $key($g, 'group_key');
            $items = [];
            foreach ($g['items'] as $ii => $item) {
                $itemKey = $key($item, 'item_key');
                $fields = [];
                foreach ($item['fields'] as $fi => $f) {
                    $fieldKey = $key($f, 'field_key');
                    $fields[] = ['field_key' => $fieldKey, 'key' => $old['field_key'][$fieldKey] ?? $fieldKey, 'label' => trim($f['label'] ?? ''), 'type' => $f['type'], 'units' => $f['type'] === 'number' ? array_values(array_unique($f['units'] ?? [])) : [], 'options' => $f['type'] === 'checklist' ? array_values(array_unique($f['options'] ?? [])) : [], 'required_when' => $f['type'] === 'text' ? ($f['required_when'] ?? null) : null, 'min_value' => $f['type'] === 'number' ? ($f['min_value'] ?? null) : null, 'max_value' => $f['type'] === 'number' ? ($f['max_value'] ?? null) : null, 'is_multiple' => $f['type'] === 'checklist' && ($f['is_multiple'] ?? false), 'is_active' => $f['is_active'], 'sort_order' => $fi];
                }
                $types = array_values(array_unique(array_map('intval', $item['machine_types'] ?? [])));
                $items[] = ['item_key' => $itemKey, 'name' => trim($item['name'] ?? ''), 'standard' => trim($item['standard'] ?? ''), 'machine_types' => $types, 'types_restricted' => ($item['types_restricted'] ?? false) || count($types) > 0, 'is_active' => $item['is_active'], 'fields' => $fields, 'sort_order' => $ii];
            }
            $groups[] = ['group_key' => $groupKey, 'title' => trim($g['title'] ?? ''), 'is_active' => $g['is_active'], 'items' => $items, 'sort_order' => $gi];
        }
        foreach ($old as $kind => $values) {
            if (array_diff($kind === 'field_key' ? array_keys($values) : $values, $seen[$kind])) {
                throw ValidationException::withMessages(['groups' => 'Existing groups, items, and fields cannot be deleted. Disable them instead.']);
            }
        }

        return $groups;
    }

    private function materialize(RefInspectionTemplate $template): void
    {
        foreach ($template->documentGroups() as $i => $g) {
            $group = RefInspectionGroup::create(['ref_inspection_template_id' => $template->id, 'group_key' => $g['group_key'], 'title' => $g['title'], 'code' => $g['code'], 'sort_order' => $i]);
            foreach ($g['items'] as $item) {
                $row = RefInspectionItem::create(['ref_inspection_template_id' => $template->id, 'ref_inspection_group_id' => $group->id, 'group_code' => $g['code'], 'number' => $item['number'], 'item_key' => $item['item_key'], 'name' => $item['name'], 'standard' => $item['standard'], 'machine_types' => $item['machine_types'], 'types_restricted' => $item['types_restricted'] ?? count($item['machine_types']) > 0]);
                foreach ($item['fields'] as $fi => $f) {
                    RefInspectionField::create(['item_id' => $row->id, 'key' => $f['key'], 'field_key' => $f['field_key'], 'label' => $f['label'], 'type' => $f['type'], 'unit' => $f['units'][0] ?? null, 'units' => $f['units'], 'options' => $f['options'], 'required_when' => $f['required_when'], 'sort' => $fi, 'is_multiple' => $f['is_multiple'], 'min_value' => $f['min_value'], 'max_value' => $f['max_value']]);
                }
            }
        }
    }

    private function assertDraft(RefInspectionTemplate $template, int $revision): void
    {
        abort_unless($template->status === 'draft', 409, 'Only draft versions can be edited.');
        abort_unless($template->lock_version === $revision, 409, 'Another user changed this draft. Reload before saving.');
    }

    private function audit(RefInspectionTemplate $template, User $actor, string $action, array $changes): void
    {
        DB::table('inspection_template_audits')->insert(['ref_inspection_template_id' => $template->id, 'user_id' => $actor->id, 'actor_name' => $actor->name, 'action' => $action, 'changes' => json_encode($changes, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
