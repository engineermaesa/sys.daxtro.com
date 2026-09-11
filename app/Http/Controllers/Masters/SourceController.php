<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Classes\ActivityLogger;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadSource;
use App\Models\Masters\Agent;
use Illuminate\Http\Request;

class SourceController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Source';
        return $this->render('pages.masters.sources.index');
    }

    public function list(Request $request)
    {
        $sources = LeadSource::orderBy('name')->get()->map(function (LeadSource $source) {
            return [
                'id' => $source->id,
                'name' => $source->name,
                'actions' => $this->buildSourceActions($source),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $sources,
        ]);
    }

    private function buildSourceActions(LeadSource $source): string
    {
        $editUrl = route('masters.sources.form', $source->id);
        $deleteUrl = route('masters.sources.delete', $source->id);

        $html  = '<div class="dropdown">';
        $html .= '<button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! text-[#1E1E1E]! hover:text-white! dropdown-toggle" type="button" data-toggle="dropdown">';
        $html .= '<i class="bi bi-three-dots"></i>';
        $html .= '</button>';
        $html .= '<div class="dropdown-menu dropdown-menu-right rounded-lg!">';
        $html .= '<a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($editUrl) . '">';
        $html .= '<i class="bi bi-pencil"></i> Edit';
        $html .= '</a>';
        $html .= '<button type="button" class="dropdown-item delete-source-data cursor-pointer flex! items-center! gap-2! text-[#900B09]!" data-id="' . e($source->id) . '" data-url="' . e($deleteUrl) . '">';
        $html .= '<i class="bi bi-trash"></i> Delete';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function form($id = null)
    {
        $form_data = $id ? LeadSource::findOrFail($id) : new LeadSource();
        return $this->render('pages.masters.sources.form', compact('form_data'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $source = $id ? LeadSource::findOrFail($id) : new LeadSource();
        $before = $id ? $source->toArray() : null;

        $source->name = $request->name;
        $source->save();

        $after = $source->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_source' : 'create_source',
            $id ? 'Updated lead source' : 'Created new lead source',
            $source,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Source saved successfully');
    }

    public function delete($id)
    {
        $source = LeadSource::findOrFail($id);

        $inUse = Lead::where('source_id', $id)->exists() || Agent::where('source_id', $id)->exists();
        if ($inUse) {
            return response()->json([
                'status' => false,
                'message' => 'Source cannot be deleted because it is still used by leads or agents.',
            ], 400);
        }

        ActivityLogger::writeLog(
            'delete_source',
            'Deleted lead source',
            $source,
            $source->toArray(),
            request()->user()
        );

        $source->delete();

        return $this->setJsonResponse('Source deleted successfully');
    }
}
