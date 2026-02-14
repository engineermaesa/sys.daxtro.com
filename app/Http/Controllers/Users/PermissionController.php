<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\UserPermission;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Permissions';
        return $this->render('pages.users.permissions.index');
    }

    public function list(Request $request)
    {
        $query = UserPermission::query();

        // if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
        //     $perms = $query->get();
        //     return response()->json([
        //         'status' => true,
        //         'data' => $perms,
        //     ]);
        // }

        return DataTables::of($query)
            ->addColumn('actions', function ($row) {
                $edit = route('users.permissions.form', $row->id);
                $del  = route('users.permissions.delete', $row->id);
                return '<a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a> '
                    .'<a href="'.$del.'" data-id="'.$row->id.'" data-table="permissionsTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form(Request $request, $id = null)
    {
        $form_data = $id ? UserPermission::findOrFail($id) : new UserPermission();

        // if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
        //     return response()->json([
        //         'status' => true,
        //         'data' => [
        //             'form_data' => $form_data,
        //         ],
        //     ]);
        // }

        return $this->render('pages.users.permissions.form', compact('form_data'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:100|unique:user_permissions,code'.($id ? ','.$id : ''),
        ]);

        $perm   = $id ? UserPermission::findOrFail($id) : new UserPermission();
        $before = $id ? $perm->toArray() : null;
        $perm->name = $request->name;
        $perm->code = $request->code;
        $perm->description = $request->description;
        $perm->save();
        $after = $perm->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_permission' : 'create_permission',
            $id ? 'Updated permission' : 'Created new permission',
            $perm,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Permission saved successfully');
    }

    public function delete($id)
    {
        $perm = UserPermission::findOrFail($id);
        ActivityLogger::writeLog('delete_permission', 'Deleted permission', $perm, $perm->toArray(), request()->user());
        $perm->delete();
        return $this->setJsonResponse('Permission deleted successfully');
    }
}
