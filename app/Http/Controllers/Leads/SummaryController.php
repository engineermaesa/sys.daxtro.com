<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Services\AutoTrashService;
use App\Services\MyLeadQueryService;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function index(Request $request)
    {
        AutoTrashService::triggerIfNeeded();

        return response()->json(MyLeadQueryService::getSummary($request));
    }
}
