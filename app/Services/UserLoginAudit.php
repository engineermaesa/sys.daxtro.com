<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserLoginAudit
{
    public function record(Request $request, Authenticatable $user): void
    {
        DB::table('user_login_audits')->insert([
            'user_id' => $user->getAuthIdentifier(),
            'ip_address' => $request->ip(),
            'user_agent_hash' => $request->userAgent() ? hash('sha256', $request->userAgent()) : null,
            'logged_in_at' => now(),
        ]);
    }
}
