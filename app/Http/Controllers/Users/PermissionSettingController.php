<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\UserRole;
use App\Models\UserPermission;
use App\Models\UserRolePermission;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class PermissionSettingController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Access Privileges';
        return $this->render('pages.settings.permissions-settings.index');
    }

    public function list(Request $request)
    {
        $query = UserRole::withCount('permissions');
        return DataTables::of($query)
            ->addColumn('actions', function ($row) {
                $edit = route('settings.permissions-settings.form', $row->id);
                return '<a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>';
            })
            ->editColumn('updated_at', fn($row) => $row->updated_at ? $row->updated_at->format('d M Y H:i') : '-')
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form($roleId)
    {
        $role        = UserRole::findOrFail($roleId);
        $permissions = UserPermission::orderBy('name')->get();
        $assigned    = UserRolePermission::where('role_id', $roleId)->get();
        return $this->render('pages.settings.permissions-settings.form', compact('role', 'permissions', 'assigned'));
    }

    public function save(Request $request, $roleId)
    {
        $permissions = $request->input('permissions', []);
        $role = UserRole::findOrFail($roleId);

        try {
            DB::beginTransaction();
            $before = UserRolePermission::where('role_id', $roleId)->get()->toArray();
            UserRolePermission::where('role_id', $roleId)->delete();
            foreach ($permissions as $pid) {
                UserRolePermission::create(['role_id' => $roleId, 'permission_id' => $pid]);
            }
            DB::commit();
            $after = UserRolePermission::where('role_id', $roleId)->get()->toArray();
            ActivityLogger::writeLog('update_role_permissions', 'Updated role permissions', $role, ['before'=>$before,'after'=>$after], $request->user());
            return $this->setJsonResponse('Permissions updated successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->setJsonResponse('Failed to update permissions', [], 500, $e);
        }
    }
}
