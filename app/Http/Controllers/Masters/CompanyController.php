<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\Company;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CompanyController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Companies';
        return $this->render('pages.masters.companies.index');
    }

    public function list(Request $request)
    {
        return DataTables::of(Company::query())
            ->addColumn('actions', function ($row) {
                $edit = route('masters.companies.form', $row->id);
                $del  = route('masters.companies.delete', $row->id);

                return '
                    <a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="'.$del.'" data-id="'.$row->id.'" data-table="companiesTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form($id = null)
    {
        $form_data = $id ? Company::findOrFail($id) : new Company();
        return $this->render('pages.masters.companies.form', compact('form_data'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name'    => 'required',
            'address' => 'nullable',
            'phone'   => 'nullable',
        ]);

        $company = $id ? Company::findOrFail($id) : new Company();
        $before = $id ? $company->toArray() : null;

        $company->name = $request->name;
        $company->address = $request->address;
        $company->phone = $request->phone;
        $company->save();

        $after = $company->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_company' : 'create_company',
            $id ? 'Updated company' : 'Created new company',
            $company,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Company saved successfully');
    }

    public function delete($id)
    {
        $company = Company::findOrFail($id);

        $hasRelation = $company->branches()->exists() || $company->accounts()->exists();
        if ($hasRelation) {
            return response()->json([
                'status' => false,
                'message' => 'Company cannot be deleted because it has related.'
            ], 400);
        }

        ActivityLogger::writeLog(
            'delete_company',
            'Deleted company',
            $company,
            $company->toArray(),
            request()->user()
        );

        $company->delete();

        return $this->setJsonResponse('Company deleted successfully');
    }
}
