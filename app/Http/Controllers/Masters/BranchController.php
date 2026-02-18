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
        $listUrl = url('/api/masters/branches/list');
        $apiFormUrl = url('/api/masters/branches/form');

        return $this->render('pages.masters.branches.index', compact('listUrl', 'apiFormUrl'));
    }

    public function list(Request $request)
    {
        $query = Branch::with('company');
        // If DataTables server-side request (has draw), process with Yajra so computed columns are included
        if ($request->has('draw')) {
            return DataTables::of($query)
                ->addColumn('company_name', fn($row) => $row->company->name ?? '')
                ->addColumn('address', fn($row) => $row->address ?? '')
                ->addColumn('target', fn($row) => $row->target !== null ? number_format($row->target, 2) : null)
                ->addColumn('actions', function ($row) {
                    try {
                        $edit = route('masters.branches.form', $row->id);
                    } catch (\Exception $e) {
                        $edit = '#';
                    }

                    try {
                        $del = route('masters.branches.delete', $row->id);
                    } catch (\Exception $e) {
                        $del = '#';
                    }

                    $buttons = "<a href='" . $edit . "' class='btn btn-sm btn-primary'><i class='bi bi-pencil'></i> Edit</a>";
                    if ($del !== '#') {
                        $buttons .= " <a href='" . $del . "' data-id='" . $row->id . "' data-table='branchesTable' class='btn btn-sm btn-danger delete-data'><i class='bi bi-trash'></i> Delete</a>";
                    }

                    return $buttons;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        // For API or explicit JSON requests without DataTables draw, return plain JSON list
        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            $branches = $query->get();
            return response()->json([
                'status' => true,
                'data' => $branches,
            ]);
        }

        return DataTables::of($query)
            ->addColumn('company_name', fn($row) => $row->company->name ?? '')
            ->addColumn('address', fn($row) => $row->address ?? '')
            ->addColumn('target', fn($row) => $row->target !== null ? number_format($row->target, 2) : null)
            ->addColumn('actions', function ($row) {
                $edit = route('masters.branches.form', $row->id);
                $del  = route('masters.branches.delete', $row->id);

                return "<a href='".$edit."' class='btn btn-sm btn-primary'><i class='bi bi-pencil'></i> Edit</a>".
                       " <a href='".$del."' data-id='".$row->id."' data-table='branchesTable' class='btn btn-sm btn-danger delete-data'><i class='bi bi-trash'></i> Delete</a>";
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form(Request $request, $id = null)
    {
        $form_data = $id ? Branch::findOrFail($id) : new Branch();
        $companies = Company::all();
        // Decide between view or JSON response, similar to ProvinceController
        $forceView = $request->query('format') === 'view';
        $isApiPath = $request->is('api/*');
        $acceptHeader = strtolower($request->header('Accept', ''));
        $prefersHtml = str_contains($acceptHeader, 'text/html') && ! str_contains($acceptHeader, 'application/json');
        $expectsJson = $request->wantsJson() || $request->ajax() || str_contains($acceptHeader, 'application/json');

        // Prepare safe URLs for the view
        try {
            $saveUrl = route('masters.branches.save', $id);
        } catch (\Exception $e) {
            $saveUrl = url('/api/masters/branches/save' . ($id ? '/' . $id : ''));
        }

        try {
            $backUrl = route('masters.branches.index');
        } catch (\Exception $e) {
            $backUrl = url('/masters/branches');
        }

        if ($forceView) {
            return $this->render('pages.masters.branches.form', compact('form_data', 'companies', 'saveUrl', 'backUrl'));
        }

        if ($isApiPath || $expectsJson) {
            return response()->json([
                'status' => true,
                'data' => [
                    'form_data' => $form_data,
                    'companies' => $companies,
                ],
            ]);
        }

        return $this->render('pages.masters.branches.form', compact('form_data', 'companies', 'saveUrl', 'backUrl'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'company_id' => 'required',
            'name'       => 'required',
            'code'       => 'required',
            'address'    => 'nullable',
            'target'     => 'nullable|numeric|min:0',
        ]);

        $branch = $id ? Branch::findOrFail($id) : new Branch();
        $before = $id ? $branch->toArray() : null;

        $branch->company_id = $request->company_id;
        $branch->name = $request->name;
        $branch->code = $request->code;
        $branch->address = $request->address;
        $branch->target = $request->target;
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
