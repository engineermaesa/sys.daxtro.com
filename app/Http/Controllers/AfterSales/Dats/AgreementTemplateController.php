<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\RefAgreementTemplate;
use App\Models\User;
use App\Services\Aftersales\AgreementTemplates;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class AgreementTemplateController extends Controller
{
    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
    }

    private function payload(RefAgreementTemplate $template): array
    {
        $data = $template->only(['id', 'version', 'status', 'lock_version', 'title', 'closing_statement', 'created_at', 'updated_at', 'activated_at']);
        $data['sections'] = $template->editableSections();
        $data['modified_by'] = User::find($template->updated_by_user_id ?? $template->created_by_user_id)?->name ?? 'System';

        return $data;
    }

    public function index(Request $request)
    {
        $this->authorizeManager($request);
        $versions = RefAgreementTemplate::orderByDesc('version')->get()->map(fn ($t) => $this->payload($t));
        $audits = DB::table('agreement_template_audits as a')->join('ref_agreement_templates as t', 't.id', '=', 'a.ref_agreement_template_id')
            ->select('a.id', 'a.ref_agreement_template_id', 'a.actor_name', 'a.action', 'a.created_at', 't.version')->orderByDesc('a.id')->paginate(20);

        return response()->json(['data' => ['versions' => $versions, 'audit' => $audits]]);
    }

    public function show(Request $request, RefAgreementTemplate $agreementTemplate)
    {
        $this->authorizeManager($request);

        return response()->json(['data' => $this->payload($agreementTemplate)]);
    }

    public function createDraft(Request $request, AgreementTemplates $service)
    {
        $this->authorizeManager($request);

        return response()->json(['data' => $this->payload($service->createDraft($request->user()))]);
    }

    public function update(Request $request, RefAgreementTemplate $agreementTemplate, AgreementTemplates $service)
    {
        $this->authorizeManager($request);
        $input = $request->validate([
            'lock_version' => 'required|integer|min:0',
            'title' => 'required|string|max:255', 'closing_statement' => 'nullable|string|max:10000',
            'sections' => 'present|array|max:100',
            'sections.*.section_key' => 'nullable|string|max:64',
            'sections.*.title' => 'nullable|string|max:255',
            'sections.*.is_active' => 'required|boolean',
            'sections.*.clauses' => 'present|array|max:200',
            'sections.*.clauses.*.key' => 'nullable|string|max:64',
            'sections.*.clauses.*.text' => 'nullable|string|max:10000',
            'sections.*.clauses.*.is_active' => 'required|boolean',
        ]);

        return response()->json(['data' => $this->payload($service->save($agreementTemplate->id, $input, $request->user()))]);
    }

    public function preview(Request $request, RefAgreementTemplate $agreementTemplate)
    {
        $this->authorizeManager($request);
        $input = $request->validate(['lock_version' => 'required|integer|min:0']);
        abort_unless($agreementTemplate->lock_version === (int) $input['lock_version'], 409, 'The template changed. Reload before previewing.');
        $token = Crypt::encryptString(json_encode([
            'id' => $agreementTemplate->id, 'revision' => $agreementTemplate->lock_version,
            'user' => $request->user()->id, 'expires' => now()->addMinutes(30)->timestamp,
        ], JSON_THROW_ON_ERROR));

        return response()->json(['data' => $this->payload($agreementTemplate), 'sections' => $agreementTemplate->documentSections(), 'preview_token' => $token]);
    }

    public function activate(Request $request, RefAgreementTemplate $agreementTemplate, AgreementTemplates $service)
    {
        $this->authorizeManager($request);
        $input = $request->validate(['lock_version' => 'required|integer|min:0', 'confirmed' => 'required|accepted', 'preview_token' => 'required|string']);
        try {
            $preview = json_decode(Crypt::decryptString($input['preview_token']), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $e) {
            abort(422, 'Preview this version before activating it.');
        }
        abort_unless(($preview['id'] ?? null) === $agreementTemplate->id && ($preview['user'] ?? null) === $request->user()->id
            && ($preview['revision'] ?? null) === (int) $input['lock_version'] && ($preview['expires'] ?? 0) >= now()->timestamp,
            422, 'Preview the latest saved draft before activating it.');

        return response()->json(['data' => $this->payload($service->activate($agreementTemplate->id, (int) $input['lock_version'], $request->user()))]);
    }
}
