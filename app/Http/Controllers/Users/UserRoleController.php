<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\UserRole;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserRoleController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'User Roles';
        return $this->render('pages.users.roles.index');
    }

    public function list(Request $request)
    {
        return DataTables::of(UserRole::query())
            ->addColumn('actions', function ($row) {
                $edit = route('users.roles.form', $row->id);
                $del  = route('users.roles.delete', $row->id);
                if ($row->id == 1) return '';
                return '<a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a> '
                    .'<a href="'.$del.'" data-id="'.$row->id.'" data-table="rolesTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form($id = null)
    {
        $form_data = $id ? UserRole::findOrFail($id) : new UserRole();
        return $this->render('pages.users.roles.form', compact('form_data'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:user_roles,name'.($id ? ','.$id : ''),
        ]);

        $role   = $id ? UserRole::findOrFail($id) : new UserRole();
        $before = $id ? $role->toArray() : null;
        $role->name = $request->name;
        $role->save();
        $after = $role->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_user_role' : 'create_user_role',
            $id ? 'Updated user role' : 'Created new user role',
            $role,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Role saved successfully');
    }

    public function delete($id)
    {
        $role = UserRole::findOrFail($id);
        ActivityLogger::writeLog('delete_user_role', 'Deleted user role', $role, $role->toArray(), request()->user());
        $role->delete();
        return $this->setJsonResponse('Role deleted successfully');
    }
}
