<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\Province;
use App\Models\Masters\Regional;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProvinceController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Provinces';
        $listUrl = url('/api/masters/provinces/list');
        $apiFormUrl = url('/api/masters/provinces/form');

        return $this->render('pages.masters.provinces.index', compact('listUrl', 'apiFormUrl'));
    }

    public function list(Request $request)
    {
        $query = Province::with('regional');
        // If it's a DataTables server-side request, process via Yajra DataTables
        if ($request->has('draw')) {
            return DataTables::of($query)
                ->addColumn('regional_name', fn($row) => $row->regional->name ?? '')
                ->addColumn('actions', function ($row) {
                    try {
                        $edit = route('masters.provinces.form', $row->id);
                    } catch (\Exception $e) {
                        $edit = '#';
                    }

                    try {
                        $del = route('masters.provinces.delete', $row->id);
                    } catch (\Exception $e) {
                        $del = '#';
                    }

                    $buttons = "<a href='" . $edit . "' class='btn btn-sm btn-primary'><i class='bi bi-pencil'></i> Edit</a>";
                    if ($del !== '#') {
                        $buttons .= " <a href='" . $del . "' data-id='" . $row->id . "' data-table='provincesTable' class='btn btn-sm btn-danger delete-data'><i class='bi bi-trash'></i> Delete</a>";
                    }

                    return $buttons;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        // For API or explicit JSON requests, return plain JSON list
        if ($request->is('api/*') || $request->wantsJson()) {
            $provinces = $query->get();
            return response()->json([
                'status' => true,
                'data' => $provinces,
            ]);
        }

        // Otherwise render view
        return $this->render('pages.masters.provinces.index');
    }

    public function form(Request $request, $id = null)
    {
        $form_data = $id ? Province::findOrFail($id) : new Province();
        $regionals = Regional::orderBy('name')->get();

        // Decide whether to return a view or JSON.
        // - If client explicitly requests view via ?format=view -> render view
        // - If request is a typical browser request (Accept prefers HTML) -> render view
        // - Otherwise (api/* path, ajax, wantsJson) -> return JSON
        $forceView = $request->query('format') === 'view';
        $isApiPath = $request->is('api/*');
        $acceptHeader = strtolower($request->header('Accept', ''));
        $prefersHtml = str_contains($acceptHeader, 'text/html') && ! str_contains($acceptHeader, 'application/json');
        $expectsJson = $request->wantsJson() || $request->ajax() || str_contains($acceptHeader, 'application/json');

        // Prepare URLs for the view (use web named routes when available, otherwise fallback to API URLs)
        try {
            $saveUrl = route('masters.provinces.save', $id);
        } catch (\Exception $e) {
            $saveUrl = url('/api/masters/provinces/save' . ($id ? '/' . $id : ''));
        }

        try {
            $backUrl = route('masters.provinces.index');
        } catch (\Exception $e) {
            $backUrl = url('/masters/provinces');
        }

        if ($forceView) {
            return $this->render('pages.masters.provinces.form', compact('form_data', 'regionals', 'saveUrl', 'backUrl'));
        }

        // If request is for API path or explicitly expects JSON, return JSON.
        if ($isApiPath || $expectsJson) {
            return response()->json([
                'status' => true,
                'data' => [
                    'form_data' => $form_data,
                    'regionals' => $regionals,
                ],
            ]);
        }

        // Otherwise render view for standard browser requests.
        return $this->render('pages.masters.provinces.form', compact('form_data', 'regionals', 'saveUrl', 'backUrl'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'regional_id' => 'required|exists:ref_regionals,id',
            'name'        => 'required',
        ]);

        $province = $id ? Province::findOrFail($id) : new Province();
        $before = $id ? $province->toArray() : null;

        $province->regional_id = $request->regional_id;
        $province->name        = $request->name;
        $province->save();

        $after = $province->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_province' : 'create_province',
            $id ? 'Updated province' : 'Created new province',
            $province,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Province saved successfully');
    }

    public function delete($id)
    {
        $province = Province::findOrFail($id);

        if ($province->regions()->exists()) {
            return response()->json([
                'status'  => false,
                'message' => 'Province cannot be deleted because it has related records.'
            ], 400);
        }

        ActivityLogger::writeLog(
            'delete_province',
            'Deleted province',
            $province,
            $province->toArray(),
            request()->user()
        );

        $province->delete();

        return $this->setJsonResponse('Province deleted successfully');
    }
}
