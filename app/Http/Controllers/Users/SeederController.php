<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class SeederController extends Controller
{
    private function authorizeSuperAdmin()
    {
        if (auth()->user()->role?->code !== 'super_admin') {
            abort(403);
        }
    }

    public function run()
    {
        $this->authorizeSuperAdmin();

        Artisan::call('migrate:refresh', ['--seed' => true]);

        return redirect()->route('dashboard')->with('success', 'Database refreshed and seeded');
    }
}