<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\RefSurveyTemplate;
use App\Models\User;
use App\Services\Aftersales\SurveyTemplates;
use App\Services\Aftersales\TicketSurveys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SurveyTemplateController extends Controller
{
    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
    }

    private function payload(RefSurveyTemplate $template, bool $full = true): array
    {
        $data = $template->only(['id', 'variant', 'version', 'status', 'title', 'lock_version', 'created_at', 'updated_at', 'activated_at']);
        if ($full) {
            $data['content'] = $template->content;
        }
        $data['modified_by'] = User::find($template->updated_by_user_id ?? $template->created_by_user_id)?->name ?? 'System';

        return $data;
    }

    public function index(Request $request)
    {
        $this->authorizeManager($request);
        $variant = $request->validate(['variant' => 'sometimes|in:site,phone_call'])['variant'] ?? 'site';
        $versions = RefSurveyTemplate::where('variant', $variant)->orderByDesc('version')->get()->map(fn ($t) => $this->payload($t, false));
        $audit = DB::table('survey_template_audits as a')->join('ref_survey_templates as t', 't.id', '=', 'a.ref_survey_template_id')->where('t.variant', $variant)->select('a.id', 'a.actor_name', 'a.action', 'a.created_at', 't.version', 't.variant')->orderByDesc('a.id')->paginate(20);

        return response()->json(['data' => ['versions' => $versions, 'audit' => $audit]]);
    }

    public function show(Request $request, RefSurveyTemplate $surveyTemplate)
    {
        $this->authorizeManager($request);

        return response()->json(['data' => $this->payload($surveyTemplate)]);
    }

    public function createDraft(Request $request, SurveyTemplates $service)
    {
        $this->authorizeManager($request);

        $variant = $request->validate(['variant' => 'sometimes|in:site,phone_call'])['variant'] ?? 'site';

        return response()->json(['data' => $this->payload($service->createDraft($request->user(), $variant))]);
    }

    public function update(Request $request, RefSurveyTemplate $surveyTemplate, SurveyTemplates $service)
    {
        $this->authorizeManager($request);
        $input = $request->validate([
            'lock_version' => 'required|integer|min:0', 'title' => 'required|string|max:255', 'content' => 'required|array:sections,scale_labels',
            'content.scale_labels' => 'required|array|size:3', 'content.scale_labels.*' => 'nullable|string|max:100',
            'content.sections' => 'present|array|max:100', 'content.sections.*.section_key' => 'nullable|string|max:64',
            'content.sections.*.title' => 'nullable|string|max:255', 'content.sections.*.is_active' => 'required|boolean', 'content.sections.*.questions' => 'present|array|max:200',
            'content.sections.*.questions.*.question_key' => 'nullable|string|max:64', 'content.sections.*.questions.*.label' => 'nullable|string|max:1000', 'content.sections.*.questions.*.hint' => 'nullable|string|max:2000',
            'content.sections.*.questions.*.type' => 'required|in:rating,yes_no,short_text,long_text,choice', 'content.sections.*.questions.*.required' => 'required|boolean', 'content.sections.*.questions.*.is_active' => 'required|boolean',
            'content.sections.*.questions.*.options' => 'present|array|max:100', 'content.sections.*.questions.*.options.*' => 'required|string|max:255',
        ]);

        return response()->json(['data' => $this->payload($service->save($surveyTemplate->id, $input, $request->user()))]);
    }

    public function preview(Request $request, RefSurveyTemplate $surveyTemplate)
    {
        $this->authorizeManager($request);
        $input = $request->validate(['lock_version' => 'required|integer|min:0']);
        abort_unless($surveyTemplate->lock_version === (int) $input['lock_version'], 409, 'The template changed. Reload before previewing.');
        $token = Crypt::encryptString(json_encode(['kind' => 'survey', 'id' => $surveyTemplate->id, 'revision' => $surveyTemplate->lock_version, 'user' => $request->user()->id, 'expires' => now()->addMinutes(30)->timestamp], JSON_THROW_ON_ERROR));

        return response()->json(['data' => $this->payload($surveyTemplate), 'sections' => $surveyTemplate->documentSections(), 'preview_token' => $token]);
    }

    public function activate(Request $request, RefSurveyTemplate $surveyTemplate, SurveyTemplates $service)
    {
        $this->authorizeManager($request);
        $input = $request->validate(['lock_version' => 'required|integer|min:0', 'confirmed' => 'required|accepted', 'preview_token' => 'required|string']);
        try {
            $token = json_decode(Crypt::decryptString($input['preview_token']), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Illuminate\Contracts\Encryption\DecryptException|\JsonException $e) {
            abort(422, 'Preview the latest draft first.');
        }
        abort_unless(($token['kind'] ?? null) === 'survey' && ($token['id'] ?? null) === $surveyTemplate->id && ($token['user'] ?? null) === $request->user()->id && ($token['revision'] ?? null) === (int) $input['lock_version'] && ($token['expires'] ?? 0) >= now()->timestamp, 422, 'Preview the latest draft before activating it.');

        return response()->json(['data' => $this->payload($service->activate($surveyTemplate->id, (int) $input['lock_version'], $request->user()))]);
    }

    public function questionScores(Request $request)
    {
        $this->authorizeManager($request);
        $input = $request->validate(['technician_id' => 'nullable|integer|exists:users,id']);
        $query = DB::table('ticket_satisfaction_answers as a')->join('ticket_satisfactions as s', 's.id', '=', 'a.ticket_satisfaction_id')->join('tickets as t', 't.id', '=', 's.ticket_id')
            ->leftJoin('ref_survey_templates as template', 'template.id', '=', 's.ref_survey_template_id')
            ->whereNull('s.deleted_at')->whereNull('t.deleted_at')->where('a.type', 'rating')->whereNotNull('a.value_number');
        TicketSurveys::constrainQuestionScores($query);
        if (isset($input['technician_id'])) {
            $query->where('t.assigned_technician_id', $input['technician_id']);
        }
        $labels = [];
        foreach (RefSurveyTemplate::orderBy('version')->get() as $t) {
            foreach ($t->content['sections'] as $s) {
                foreach ($s['questions'] as $q) {
                    $labels[$t->variant][$q['question_key']] = $q['label'];
                }
            }
        }
        $scores = $query->select('a.question_key', 'template.variant')->selectRaw('COUNT(*) as response_count, AVG(a.value_number) as average_score')->groupBy('a.question_key', 'template.variant')->get()->map(fn ($row) => ['question_key' => $row->question_key, 'variant' => $row->variant ?? 'site', 'label' => $labels[$row->variant ?? 'site'][$row->question_key] ?? $row->question_key, 'response_count' => (int) $row->response_count, 'average_score' => round((float) $row->average_score, 2)]);

        return response()->json(['data' => $scores]);
    }
}
