<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\Part;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PartController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Parts';
        return $this->render('pages.masters.parts.index');
    }

    public function list(Request $request)
    {
        $query = Part::query();

        // If DataTables server-side is calling (POST or has `draw`), return Yajra envelope
        if ($request->isMethod('post') || $request->has('draw')) {
            return DataTables::of($query)
                ->addColumn('actions', function ($row) {
                    try {
                        $edit = route('masters.parts.form', $row->id);
                    } catch (\Exception $e) {
                        $edit = url('api/masters/parts/form/' . $row->id);
                    }

                    try {
                        $del = route('masters.parts.delete', $row->id);
                    } catch (\Exception $e) {
                        $del = url('api/masters/parts/delete/' . $row->id);
                    }

                    return "<a href='" . $edit . "' class='btn btn-sm btn-primary'><i class='bi bi-pencil'></i> Edit</a>" .
                        " <a href='" . $del . "' data-id='" . $row->id . "' data-table='partsTable' class='btn btn-sm btn-danger delete-data'><i class='bi bi-trash'></i> Delete</a>";
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
        // Otherwise return plain JSON for API GET consumers
        $parts = $query->get();
        return response()->json([
            'status' => true,
            'data' => $parts,
        ]);
    }

    

    public function form(Request $request, $id = null)
    {
        $form_data = $id ? Part::findOrFail($id) : new Part();

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'data' => [
                    'form_data' => $form_data,
                ],
            ]);
        }

        return $this->render('pages.masters.parts.form', compact('form_data'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'sku'   => 'required',
            'name'  => 'required',
            'price' => 'required|numeric',
        ]);

        $part = $id ? Part::findOrFail($id) : new Part();
        $before = $id ? $part->toArray() : null;

        $part->name  = $request->name;
        $part->price = $request->price;
        $part->sku   = $request->sku;
        $part->save();

        $after = $part->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_part' : 'create_part',
            $id ? 'Updated part' : 'Created new part',
            $part,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Part saved successfully');
    }

    public function delete($id)
    {
        $part = Part::findOrFail($id);

        ActivityLogger::writeLog(
            'delete_part',
            'Deleted part',
            $part,
            $part->toArray(),
            request()->user()
        );

        $part->delete();

        return $this->setJsonResponse('Part deleted successfully');
    }
}
