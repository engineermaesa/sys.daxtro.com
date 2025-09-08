<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()
    {
        return view('pages.users.index');
    }
}
