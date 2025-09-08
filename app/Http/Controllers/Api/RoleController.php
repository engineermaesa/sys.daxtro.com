<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserRole;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', UserRole::class);

        return response()->json(UserRole::all());
    }

    public function store(Request $request)
    {
        $this->authorize('create', UserRole::class);

        $data = $request->validate([
            'name' => 'required',
            'code' => 'required',
            'description' => 'nullable',
        ]);

        $role = UserRole::create($data);

        return response()->json($role, 201);
    }

    public function update(Request $request, UserRole $role)
    {
        $this->authorize('update', $role);

        $data = $request->validate([
            'name' => 'sometimes',
            'code' => 'sometimes',
            'description' => 'nullable',
        ]);

        $role->update($data);

        return response()->json($role);
    }

    public function destroy(UserRole $role)
    {
        $this->authorize('delete', $role);

        $role->delete();

        return response()->json(['message' => 'deleted']);
    }
}
