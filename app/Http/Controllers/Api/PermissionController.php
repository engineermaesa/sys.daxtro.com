<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPermission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', UserPermission::class);

        return response()->json(UserPermission::all());
    }

    public function store(Request $request)
    {
        $this->authorize('create', UserPermission::class);

        $data = $request->validate([
            'name' => 'required',
            'code' => 'required',
            'description' => 'nullable',
        ]);

        $permission = UserPermission::create($data);

        return response()->json($permission, 201);
    }

    public function update(Request $request, UserPermission $permission)
    {
        $this->authorize('update', $permission);

        $data = $request->validate([
            'name' => 'sometimes',
            'code' => 'sometimes',
            'description' => 'nullable',
        ]);

        $permission->update($data);

        return response()->json($permission);
    }

    public function destroy(UserPermission $permission)
    {
        $this->authorize('delete', $permission);

        $permission->delete();

        return response()->json(['message' => 'deleted']);
    }
}
