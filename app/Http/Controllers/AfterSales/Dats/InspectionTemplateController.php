<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\RefInspectionTemplate;
use App\Models\Masters\ProductType;
use App\Models\User;
use App\Services\Aftersales\InspectionTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class InspectionTemplateController extends Controller
{
    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
    }

    private function payload(RefInspectionTemplate $template, bool $full = true): array
    {
        $data = $template->only(['id', 'version', 'status', 'title', 'lock_version', 'created_at', 'updated_at', 'activated_at']);
        $data['modified_by'] = User::find($template->updated_by_user_id ?? $template->created_by_user_id)?->name ?? 'System';
        if ($full) {
            $data += $template->only(['groups', 'diagnosis_section_title', 'diagnosis_fields']);
            $types = ProductType::orderBy('name')->get(['id', 'name']);
            $data['product_types'] = $types;
            $warnings = [];
            foreach ($template->documentGroups() as $g) {
                foreach ($g['items'] as $item) {
                    if ($item['types_restricted'] ?? count($item['machine_types']) > 0) {
                        $missing = $types->whereNotIn('id', $item['machine_types'])->pluck('name')->all();
                        if ($missing) {
                            $warnings[] = ['item_key' => $item['item_key'], 'item' => $item['name'], 'excluded_types' => $missing];
                        }
                    }
                }
            }
            $data['warnings'] = $warnings;
        }

        return $data;
    }

    public function index(Request $request)
    {
        $this->authorizeManager($request);
        $versions = RefInspectionTemplate::orderByDesc('version')->get()->map(fn ($t) => $this->payload($t, false));
        $audit = DB::table('inspection_template_audits as a')->join('ref_inspection_templates as t', 't.id', '=', 'a.ref_inspection_template_id')->select('a.id', 'a.actor_name', 'a.action', 'a.created_at', 't.version')->orderByDesc('a.id')->paginate(20);

        return response()->json(['data' => ['versions' => $versions, 'audit' => $audit]]);
    }

    public function show(Request $request, RefInspectionTemplate $inspectionTemplate)
    {
        $this->authorizeManager($request);

        return response()->json(['data' => $this->payload($inspectionTemplate)]);
    }

    public function createDraft(Request $request, InspectionTemplates $service)
    {
        $this->authorizeManager($request);

        return response()->json(['data' => $this->payload($service->createDraft($request->user()))]);
    }

    public function update(Request $request, RefInspectionTemplate $inspectionTemplate, InspectionTemplates $service)
    {
        $this->authorizeManager($request);
        $rules = [
            'lock_version' => 'required|integer|min:0', 'title' => 'required|string|max:255',
            'diagnosis_section_title' => 'nullable|string|max:255', 'diagnosis_fields' => 'required|array:problem,cause,action',
            'groups' => 'present|array|max:100', 'groups.*.group_key' => 'nullable|string|max:40',
            'groups.*.title' => 'nullable|string|max:255', 'groups.*.is_active' => 'required|boolean', 'groups.*.items' => 'present|array|max:200',
            'groups.*.items.*.item_key' => 'nullable|string|max:40', 'groups.*.items.*.name' => 'nullable|string|max:255',
            'groups.*.items.*.standard' => 'nullable|string|max:10000', 'groups.*.items.*.is_active' => 'required|boolean',
            'groups.*.items.*.machine_types' => 'present|array', 'groups.*.items.*.machine_types.*' => 'integer|exists:ref_product_types,id',
            'groups.*.items.*.types_restricted' => 'sometimes|boolean', 'groups.*.items.*.fields' => 'present|array|max:100',
            'groups.*.items.*.fields.*.field_key' => 'nullable|string|max:40', 'groups.*.items.*.fields.*.label' => 'nullable|string|max:255',
            'groups.*.items.*.fields.*.type' => 'required|in:number,text,checklist', 'groups.*.items.*.fields.*.units' => 'present|array|max:10',
            'groups.*.items.*.fields.*.units.*' => 'required|string|max:20', 'groups.*.items.*.fields.*.options' => 'present|array|max:100',
            'groups.*.items.*.fields.*.options.*' => 'required|string|max:255', 'groups.*.items.*.fields.*.required_when' => 'nullable|string|max:255',
            'groups.*.items.*.fields.*.min_value' => 'nullable|numeric|between:-99999999999,99999999999', 'groups.*.items.*.fields.*.max_value' => 'nullable|numeric|between:-99999999999,99999999999',
            'groups.*.items.*.fields.*.is_multiple' => 'required|boolean', 'groups.*.items.*.fields.*.is_active' => 'required|boolean',
        ];
        foreach (array_keys(InspectionTemplates::DIAGNOSIS) as $key) {
            $rules["diagnosis_fields.$key"] = 'required|array:label,hint';
            $rules["diagnosis_fields.$key.label"] = 'nullable|string|max:255';
            $rules["diagnosis_fields.$key.hint"] = 'nullable|string|max:2000';
        }

        return response()->json(['data' => $this->payload($service->save($inspectionTemplate->id, $request->validate($rules), $request->user()))]);
    }

    public function preview(Request $request, RefInspectionTemplate $inspectionTemplate)
    {
        $this->authorizeManager($request);
        $input = $request->validate(['lock_version' => 'required|integer|min:0']);
        abort_unless($inspectionTemplate->lock_version === (int) $input['lock_version'], 409, 'The template changed. Reload before previewing.');
        $token = Crypt::encryptString(json_encode(['kind' => 'inspection', 'id' => $inspectionTemplate->id, 'revision' => $inspectionTemplate->lock_version, 'user' => $request->user()->id, 'expires' => now()->addMinutes(30)->timestamp], JSON_THROW_ON_ERROR));

        return response()->json(['data' => $this->payload($inspectionTemplate), 'groups' => $inspectionTemplate->documentGroups(), 'preview_token' => $token]);
    }

    public function activate(Request $request, RefInspectionTemplate $inspectionTemplate, InspectionTemplates $service)
    {
        $this->authorizeManager($request);
        $input = $request->validate(['lock_version' => 'required|integer|min:0', 'confirmed' => 'required|accepted', 'preview_token' => 'required|string']);
        try {
            $token = json_decode(Crypt::decryptString($input['preview_token']), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Illuminate\Contracts\Encryption\DecryptException|\JsonException $e) {
            abort(422, 'Preview the latest draft before activating it.');
        }
        abort_unless(($token['kind'] ?? null) === 'inspection' && ($token['id'] ?? null) === $inspectionTemplate->id && ($token['user'] ?? null) === $request->user()->id && ($token['revision'] ?? null) === (int) $input['lock_version'] && ($token['expires'] ?? 0) >= now()->timestamp, 422, 'Preview the latest draft before activating it.');

        return response()->json(['data' => $this->payload($service->activate($inspectionTemplate->id, (int) $input['lock_version'], $request->user()))]);
    }
}
