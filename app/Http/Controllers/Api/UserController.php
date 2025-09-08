<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        return response()->json(User::with('role')->get());
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        return response()->json($user->load('role'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $role      = \App\Models\UserRole::find($request->role_id);
        $roleCode  = $role?->code;
        $branchReq = $roleCode === 'branch_manager';

        $data = $request->validate([
            'role_id'   => 'required|exists:user_roles,id',
            'branch_id' => ($branchReq ? 'required|' : 'nullable|').'exists:ref_branches,id',
            'name'      => 'required',
            'nip'       => 'required|unique:users,nip',
            'email'     => 'required|email',
            'phone'     => 'nullable',
            'password'  => 'required',
        ]);

        $data['password'] = bcrypt($data['password']);

        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $role      = $request->filled('role_id') ? \App\Models\UserRole::find($request->role_id) : $user->role;
        $roleCode  = $role?->code;
        $branchReq = $roleCode === 'branch_manager';

        $data = $request->validate([
            'role_id'   => 'sometimes|exists:user_roles,id',
            'branch_id' => ($branchReq ? 'required|' : 'nullable|').'exists:ref_branches,id',
            'name'      => 'sometimes',
            'nip'       => 'sometimes|unique:users,nip,'.$user->id,
            'email'     => 'sometimes|email',
            'phone'     => 'nullable',
            'password'  => 'sometimes',
        ]);

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'deleted']);
    }
}
