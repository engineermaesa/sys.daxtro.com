<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\Region;
use App\Models\Masters\Regional;
use App\Models\Masters\Province;
use App\Models\Masters\Branch;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RegionController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Regions';
        $listUrl = url('/api/masters/regions/list');
        $apiFormUrl = url('/api/masters/regions/form');

        try {
            $formUrl = route('masters.regions.form');
        } catch (\Exception $e) {
            $formUrl = $apiFormUrl;
        }

        return $this->render('pages.masters.regions.index', compact('listUrl', 'apiFormUrl', 'formUrl'));
    }

    public function list(Request $request)
    {
                $query = Region::with(['regional', 'province', 'branch']);

                // If DataTables is calling server-side (sends `draw`) produce Yajra DataTables response
                if ($request->has('draw')) {
                    return DataTables::of($query)
                        ->addColumn('regional_name', fn($r) => $r->regional->name ?? '')
                        ->addColumn('province_name', fn($r) => $r->province->name ?? '')
                        ->addColumn('branch_name', fn($r) => $r->branch->name ?? '')
                        ->addColumn('actions', function ($r) {
                            try {
                                $edit = route('masters.regions.form', $r->id);
                            } catch (\Exception $e) {
                                $edit = url('/api/masters/regions/form/'.$r->id);
                            }

                            try {
                                $del = route('masters.regions.delete', $r->id);
                            } catch (\Exception $e) {
                                $del = url('/api/masters/regions/delete/'.$r->id);
                            }

                            return "<a href='".$edit."' class='btn btn-sm btn-primary'><i class='bi bi-pencil'></i> Edit</a>".
                                   " <a href='".$del."' data-id='".$r->id."' data-table='regionsTable' class='btn btn-sm btn-danger delete-data'><i class='bi bi-trash'></i> Delete</a>";
                        })
                        ->rawColumns(['actions'])
                        ->make(true);
                }

                // Non-DataTables API/JSON clients
                if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
                        $regions = $query->get();
                        return response()->json([
                                'status' => true,
                                'data' => $regions,
                        ]);
                }

                // Fallback: render view (if called directly)
                return $this->render('pages.masters.regions.index');
    }

        public function form(Request $request, $id = null)
        {
                $form_data = $id
                    ? Region::findOrFail($id)
                    : new Region();

                $regionals = Regional::orderBy('name')->get();
                // if editing: load only provinces for selected regional, else empty
                $provinces = $form_data->regional_id
                    ? Province::where('regional_id', $form_data->regional_id)->orderBy('name')->get()
                    : collect();

                $branches  = Branch::orderBy('name')->get();

                if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => true,
                        'data' => [
                            'form_data' => $form_data,
                            'regionals' => $regionals,
                            'provinces' => $provinces,
                            'branches' => $branches,
                        ],
                    ]);
                }

                // Prepare safe URLs for blade view (avoid route() exceptions)
                try {
                    $saveUrl = route('masters.regions.save', $form_data->id ?? null);
                } catch (\Exception $e) {
                    $saveUrl = url('/api/masters/regions/save'.($form_data->id ? '/'.$form_data->id : ''));
                }

                try {
                    $backUrl = route('masters.regions.index');
                } catch (\Exception $e) {
                    $backUrl = url('/masters/regions');
                }

                return $this->render('pages.masters.regions.form', compact(
                    'form_data', 'regionals', 'provinces', 'branches', 'saveUrl', 'backUrl'
                ));
        }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'regional_id' => 'required|exists:ref_regionals,id',
            'province_id' => 'required|exists:ref_provinces,id',
            'branch_id'   => 'required|exists:ref_branches,id',
            'name'        => 'required',
            'code'        => 'required',
        ]);

        $region = $id
          ? Region::findOrFail($id)
          : new Region();

        $before = $id ? $region->toArray() : null;

        $region->regional_id = $request->regional_id;
        $region->province_id = $request->province_id;
        $region->branch_id   = $request->branch_id;
        $region->name        = $request->name;
        $region->code        = $request->code;
        $region->save();

        $after = $region->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_region' : 'create_region',
            $id ? 'Updated region'    : 'Created new region',
            $region,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Region saved successfully');
    }

    public function delete($id)
    {
        $region = Region::findOrFail($id);

        if ($region->leads()->exists()) {
            return response()->json([
                'status'  => false,
                'message' => 'Region cannot be deleted because it has lead records!'
            ], 400);
        }

        ActivityLogger::writeLog(
            'delete_region',
            'Deleted region',
            $region,
            $region->toArray(),
            request()->user()
        );

        $region->delete();

        return $this->setJsonResponse('Region deleted successfully');
    }

    /**
     * AJAX: return provinces for a given regional
     */
    public function provinces(Request $request)
    {
        $request->validate([
            'regional_id' => 'required|exists:ref_regionals,id'
        ]);

        $provinces = Province::where('regional_id', $request->regional_id)
                             ->orderBy('name')
                             ->get(['id','name']);

        return response()->json($provinces);
    }
}
