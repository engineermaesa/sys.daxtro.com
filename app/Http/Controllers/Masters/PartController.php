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

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            $parts = $query->get();
            return response()->json([
                'status' => true,
                'data' => $parts,
            ]);
        }

        return DataTables::of($query)
            ->addColumn('actions', function ($row) {
                $edit = route('masters.parts.form', $row->id);
                $del  = route('masters.parts.delete', $row->id);

                return "<a href='".$edit."' class='btn btn-sm btn-primary'><i class='bi bi-pencil'></i> Edit</a>".
                       " <a href='".$del."' data-id='".$row->id."' data-table='partsTable' class='btn btn-sm btn-danger delete-data'><i class='bi bi-trash'></i> Delete</a>";
            })
            ->rawColumns(['actions'])
            ->make(true);
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
            'name'  => 'required',
            'price' => 'required|numeric',
        ]);

        $part = $id ? Part::findOrFail($id) : new Part();
        $before = $id ? $part->toArray() : null;

        $part->name  = $request->name;
        $part->price = $request->price;
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
