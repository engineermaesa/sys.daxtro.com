<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\{Lead, LeadStatus, LeadSource, LeadSegment};
use App\Models\Masters\Region;

class PublicLeadController extends Controller
{
    public function create()
    {
        $sources  = LeadSource::all();
        $segments = LeadSegment::all();
        $regions  = Region::all();

        return view('auth.lead-register', compact('sources', 'segments', 'regions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'source_id'  => 'required',
            'segment_id' => 'required',
            'region_id'  => 'required',
            'name'       => 'required',
            'phone'      => 'required',
            'email'      => 'required|email',
        ]);

        Lead::create([
            'source_id'    => $request->source_id,
            'segment_id'   => $request->segment_id,
            'region_id'    => $request->region_id,
            'status_id'    => LeadStatus::PUBLISHED,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'published_at' => now(),
        ]);

        return redirect()->route('login')->with('success', 'Thank you, your information has been submitted.');
    }
}
