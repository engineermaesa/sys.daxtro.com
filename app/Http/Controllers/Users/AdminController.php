<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Masters\Company;
use App\Models\Masters\Branch;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AdminController extends Controller
{
    public function index()
    {
        $allowed = ['super_admin', 'branch_manager', 'finance_director', 'accountant_director', 'sales_director'];
        $userRole = request()->user()->role?->code;

        if (! in_array($userRole, $allowed)) {
            abort(403);
        }

        $this->pageTitle = 'Manage Admins';
        $roles     = UserRole::all();
        $companies = Company::all();
        $branches  = Branch::all();

        return $this->render('pages.users.admins.index', compact('roles', 'companies', 'branches'));
    }

    public function list(Request $request)
    {
        $user = $request->user();

        $query = User::with(['role', 'branch.company']);

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // Filter
        if ($request->filled('company_id')) {
            $query->whereHas('branch', fn($q) => $q->where('company_id', $request->company_id));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }


        if ($user->role?->code === 'branch_manager') {
            $query->where('branch_id', $user->branch_id);
        }
        
        return DataTables::of($query)
            ->addColumn('role_name', fn($row) => $row->role->name ?? '')
            ->addColumn('company_name', fn($row) => $row->branch?->company?->name ?? '')
            ->addColumn('branch_name', fn($row) => $row->branch?->name ?? '')
            ->addColumn('target', fn($row) => $row->target !== null ? number_format($row->target, 2) : null)
            ->addColumn('created_by_data', fn($row) => $row->created_by?->name ?? '')
            ->addColumn('updated_by_data', fn($row) => $row->updated_by?->name ?? '')
            ->addColumn('actions', function ($row) {
                $edit = route('users.form', $row->id);
                $del  = route('users.delete', $row->id);
                return '<a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a> '
                    .'<a href="'.$del.'" data-id="'.$row->id.'" data-table="adminsTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form($id = null)
    {
        $data      = $id ? User::with(['branch.company'])->findOrFail($id) : new User();
        $roles     = UserRole::all();
        $companies = Company::all();
        $branches  = Branch::all();
        return $this->render('pages.users.admins.form', compact('data', 'roles', 'companies', 'branches'));
    }

    public function save(Request $request, $id = null)
    {
        $authUser  = $request->user();        
        $roleCode  = $request->user()->role->code;                        

        $branchReq = $roleCode === 'branch_manager';        

        // Override branch_id from session if role is branch_manager
        $branchId = $branchReq ? $authUser->branch_id : $request->branch_id;

        $rules = [
            'role_id'   => 'required|exists:user_roles,id',
            'branch_id' => ($branchReq ? 'required' : 'nullable|exists:ref_branches,id'),
            'name'      => 'required|string|max:100',
            'nip'       => 'required|string|max:50|unique:users,nip'.($id ? ','.$id : ''),
            'email'     => 'required|email|unique:users,email'.($id ? ','.$id : ''),
            'phone'     => 'nullable|string|max:20',
            'target'    => 'nullable|numeric|min:0',
        ];

        if ($id) {
            $rules['password'] = 'nullable|string|min:6|confirmed';
        } else {
            $rules['password'] = 'required|string|min:6|confirmed';
        }

        $request->merge([
            'branch_id' => $branchId,
        ]);

        $request->validate($rules);

        try {
            DB::beginTransaction();
            $user   = $id ? User::findOrFail($id) : new User();
            $before = $id ? $user->toArray() : null;

            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
            }
            
            $user->role_id      = $request->role_id;
            $user->company_id   = $request->company_id;
            $user->branch_id    = $branchId;
            $user->name         = $request->name;
            $user->email        = $request->email;
            $user->nip          = $request->nip;
            $user->phone        = $request->phone;
            $user->target       = $request->target ?: null;
            $user->created_by   = $id ? $user->created_by : $authUser->id;
            $user->updated_by   = $authUser->id;
            $user->save();

            $after = $user->fresh()->toArray();

            ActivityLogger::writeLog(
                $id ? 'update_admin' : 'create_admin',
                $id ? 'Updated admin user' : 'Created admin user',
                $user,
                ['before' => $before, 'after' => $after],
                $authUser
            );

            DB::commit();
            return $this->setJsonResponse('User saved successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->setJsonResponse('Failed to save user', [], 500, $th);
        }
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        ActivityLogger::writeLog('delete_admin', 'Deleted admin account', $user, $user->toArray(), request()->user());
        return $this->setJsonResponse('User deleted successfully');
    }

    public function branchesByCompany($companyId)
    {
        $branches = Branch::where('company_id', $companyId)->get(['id', 'name']);
        return response()->json($branches);
    }

    public function regionsByBranch($branchId)
    {
        $regions = Region::where('branch_id', $branchId)->get(['id', 'name']);
        return response()->json($regions);
    }

    public function salesByBranch($branchId)
    {
        $salesRoleId = UserRole::where('code', 'sales')->value('id');
        $users = User::where('role_id', $salesRoleId)
            ->where('branch_id', $branchId)
            ->get(['id', 'name']);

        return response()->json($users);
    }
}
