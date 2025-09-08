<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\Branch;
use App\Models\Masters\Company;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Branches';
        return $this->render('pages.masters.branches.index');
    }

    public function list(Request $request)
    {
        return DataTables::of(Branch::with('company'))
            ->addColumn('company_name', fn($row) => $row->company->name ?? '')
            ->addColumn('address', fn($row) => $row->address ?? '')
            ->addColumn('actions', function ($row) {
                $edit = route('masters.branches.form', $row->id);
                $del  = route('masters.branches.delete', $row->id);

                return '
                    <a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="'.$del.'" data-id="'.$row->id.'" data-table="branchesTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form($id = null)
    {
        $form_data = $id ? Branch::findOrFail($id) : new Branch();
        $companies = Company::all();
        return $this->render('pages.masters.branches.form', compact('form_data', 'companies'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'company_id' => 'required',
            'name'       => 'required',
            'code'       => 'required',
            'address'    => 'nullable',
        ]);

        $branch = $id ? Branch::findOrFail($id) : new Branch();
        $before = $id ? $branch->toArray() : null;

        $branch->company_id = $request->company_id;
        $branch->name = $request->name;
        $branch->code = $request->code;
        $branch->address = $request->address;
        $branch->save();

        $after = $branch->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_branch' : 'create_branch',
            $id ? 'Updated branch' : 'Created new branch',
            $branch,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Branch saved successfully');
    }

    public function delete($id)
    {
        $branch = Branch::findOrFail($id);

        $hasRelation = $branch->regions()->exists();
        if ($hasRelation) {
            return response()->json([
                'status' => false,
                'message' => 'Company cannot be deleted because it has related.'
            ], 400);
        }

        ActivityLogger::writeLog(
            'delete_branch',
            'Deleted branch',
            $branch,
            $branch->toArray(),
            request()->user()
        );

        $branch->delete();

        return $this->setJsonResponse('Branch deleted successfully');
    }
}
