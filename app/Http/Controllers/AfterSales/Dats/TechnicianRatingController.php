<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Services\Aftersales\TechnicianScore;
use Illuminate\Http\Request;

class TechnicianRatingController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.view-own'), 403);

        return response()->json(['data' => TechnicianScore::overallRating((int) $request->user()->id)]);
    }
}
