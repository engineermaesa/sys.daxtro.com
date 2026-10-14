<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\RefSurveyTemplate;
use App\Models\Aftersales\TicketSatisfaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SurveyResponses
{
    public function validate(Request $request, RefSurveyTemplate $template, bool $admin = false): array
    {
        $identity = $request->validate(['signer_name' => 'required|string|max:255', 'signer_position' => 'required|string|max:255', 'signature' => ($admin ? 'nullable' : 'required').'|string', 'machine_issue_note' => 'nullable|string|max:10000'] + ($admin ? ['reason' => 'required|string|max:255'] : []));
        $questions = collect($template->questions())->keyBy('question_key');
        $values = $request->input('answers');
        if ($values === null && $template->version === 1) {
            $values = $request->only($questions->keys()->all());
        }
        if (! is_array($values)) {
            throw ValidationException::withMessages(['answers' => 'Submit answers for the pinned survey template.']);
        }
        if (array_diff(array_keys($values), $questions->keys()->all())) {
            throw ValidationException::withMessages(['answers' => 'An answer refers to an unknown or inactive question.']);
        }
        $rules = [];
        $attributes = [];
        foreach ($questions as $key => $q) {
            $typeRules = match ($q['type']) {
                'rating' => ['integer', 'between:1,5'], 'yes_no' => ['boolean'], 'short_text' => ['string', 'max:500'], 'long_text' => ['string', 'max:10000'], 'choice' => ['string', Rule::in($q['options'])],
            };
            $rules['answers.'.$key] = array_merge([$q['required'] ? 'required' : 'nullable'], $typeRules);
            $attributes['answers.'.$key] = $q['label'];
        }
        Validator::make(['answers' => $values], $rules, [], $attributes)->validate();
        $legacy = [];
        $answers = [];
        foreach ($questions as $key => $q) {
            $value = $values[$key] ?? null;
            if ($value === '') {
                $value = null;
            }
            $number = $value !== null && in_array($q['type'], ['rating', 'yes_no'], true) ? (int) $value : null;
            $text = $value !== null && ! in_array($q['type'], ['rating', 'yes_no'], true) ? (string) $value : null;
            $answers[] = ['question_key' => $key, 'type' => $q['type'], 'value_number' => $number, 'value_text' => $text, 'value_options' => null];
            if (array_key_exists($key, TicketSatisfaction::ASPECTS) && $q['type'] === 'rating') {
                $legacy[$key] = $number;
            }
            if ($key === 'feedback' && in_array($q['type'], ['short_text', 'long_text'])) {
                $legacy['feedback'] = $text;
            }
        }
        $machineOk = (bool) ($values['machine_ok'] ?? false);
        if (! $machineOk && blank($identity['machine_issue_note'] ?? null)) {
            throw ValidationException::withMessages(['machine_issue_note' => 'Please explain what is still not working.']);
        }

        return ['attributes' => $identity + $legacy + ['machine_ok' => $machineOk, 'ref_survey_template_id' => $template->id], 'answers' => $answers];
    }

    public function store(TicketSatisfaction $response, array $answers): void
    {
        $response->answers()->createMany($answers);
    }
}
