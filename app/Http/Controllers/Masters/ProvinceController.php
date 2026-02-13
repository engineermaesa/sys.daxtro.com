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
        return $this->render('pages.masters.provinces.index');
    }

    public function list(Request $request)
    {
        $query = Province::with('regional');

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            $provinces = $query->get();
            return response()->json([
                'status' => true,
                'data' => $provinces,
            ]);
        }

        return DataTables::of($query)
            ->addColumn('regional_name', fn($row) => $row->regional->name ?? '')
            ->addColumn('actions', function ($row) {
                $edit = route('masters.provinces.form', $row->id);
                $del  = route('masters.provinces.delete', $row->id);
                return "<a href='".$edit."' class='btn btn-sm btn-primary'><i class='bi bi-pencil'></i> Edit</a>".
                       " <a href='".$del."' data-id='".$row->id."' data-table='provincesTable' class='btn btn-sm btn-danger delete-data'><i class='bi bi-trash'></i> Delete</a>";
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form(Request $request, $id = null)
    {
        $form_data = $id ? Province::findOrFail($id) : new Province();
        $regionals = Regional::orderBy('name')->get();

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'data' => [
                    'form_data' => $form_data,
                    'regionals' => $regionals,
                ],
            ]);
        }

        return $this->render('pages.masters.provinces.form', compact('form_data', 'regionals'));
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
