<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class RefSurveyTemplate extends Model
{
    public const VARIANTS = ['site', 'phone_call'];

    protected $fillable = ['variant', 'version', 'status', 'title', 'content', 'lock_version', 'created_by_user_id', 'updated_by_user_id', 'activated_by_user_id', 'activated_at'];

    protected $casts = ['content' => 'array', 'lock_version' => 'integer', 'activated_at' => 'datetime'];

    public static function active(string $variant = 'site'): self
    {
        return static::where('variant', $variant)->where('status', 'active')->firstOrFail();
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

    public static function phoneContent(): array
    {
        $content = self::initialContent();
        $labels = ['timeliness' => 'Call Timeliness', 'work_speed' => 'Speed of Resolving the Issue',
            'solution_quality' => 'Quality of the Solution', 'explanation_quality' => 'Explanation of the Solution'];
        $content['sections'][0]['questions'] = array_values(array_filter($content['sections'][0]['questions'], fn ($q) => ! in_array($q['question_key'], ['work_neatness', 'grooming'], true)));
        foreach ($content['sections'][0]['questions'] as &$question) {
            $question['label'] = $labels[$question['question_key']] ?? $question['label'];
        }
        $content['sections'][1]['questions'][0]['label'] = 'Issue resolved by phone?';

        return $content;
    }
}
