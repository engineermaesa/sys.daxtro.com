<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class RefSurveyTemplate extends Model
{
    protected $fillable = ['version', 'status', 'title', 'content', 'lock_version', 'created_by_user_id', 'updated_by_user_id', 'activated_by_user_id', 'activated_at'];

    protected $casts = ['content' => 'array', 'lock_version' => 'integer', 'activated_at' => 'datetime'];

    public static function active(): self
    {
        return static::where('status', 'active')->firstOrFail();
    }

    public function documentSections(): array
    {
        return collect($this->content['sections'])->filter(fn ($s) => $s['is_active'])->values()->map(function ($s, $i) {
            $s['code'] = RefAgreementTemplate::sectionCode($i + 1);
            $s['questions'] = array_values(array_filter($s['questions'], fn ($q) => $q['is_active']));

            return $s;
        })->all();
    }

    public function questions(): array
    {
        return collect($this->documentSections())->flatMap(fn ($s) => $s['questions'])->all();
    }

    public static function initialContent(): array
    {
        $question = fn ($key, $label, $type, $required = true, $core = false) => ['question_key' => $key, 'label' => $label, 'hint' => '', 'type' => $type, 'options' => [], 'required' => $required, 'is_core' => $core, 'is_active' => true];

        return ['scale_labels' => ['Kurang Puas', 'Puas', 'Sangat Puas'], 'sections' => [
            ['section_key' => 'ratings', 'title' => 'Ratings', 'is_active' => true, 'questions' => collect(TicketSatisfaction::ASPECTS)->map(fn ($label, $key) => $question($key, $label, 'rating'))->values()->all()],
            ['section_key' => 'machine_condition', 'title' => 'Machine condition', 'is_active' => true, 'questions' => [$question('machine_ok', 'Is the machine working properly after service?', 'yes_no', true, true)]],
            ['section_key' => 'comments', 'title' => 'Comments & Suggestions', 'is_active' => true, 'questions' => [$question('feedback', 'Please share any comments or suggestions.', 'long_text', false)]],
        ]];
    }
}
