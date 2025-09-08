<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\{Lead, LeadStatus, LeadSource, LeadSegment};
use App\Models\Masters\{Region, Province};
use Illuminate\Support\Str;

class LeadRegisterController extends Controller
{
    public function store(Request $request)
    {
        $token = $request->header('X-API-TOKEN');
        if (!$token || $token !== config('services.lead_register_api_token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'source_id'  => 'required|exists:lead_sources,id',
            'segment_id' => 'nullable|exists:lead_segments,id',
            'region_id'  => 'required|exists:ref_regions,id',
            'province'   => 'required|in:' . Province::pluck('name')->implode(','),
            'name'       => 'required',
            'company'    => 'nullable|string|max:150',
            'customer_type' => 'nullable|exists:ref_customer_types,name',
            'phone'      => 'required',
            'jabatan_id' => 'nullable|exists:ref_jabatans,id',
            'needs'      => 'required',
            'tonase'     => 'nullable|numeric',
            'email'      => 'required|email',
        ]);

        $lead = Lead::create([
            'source_id'    => $request->source_id,
            'segment_id'   => $request->segment_id,
            'region_id'    => $request->region_id,
            'province'     => $request->province,
            'status_id'    => LeadStatus::PUBLISHED,
            'company'      => $request->company,
            'customer_type' => $request->customer_type,
            'jabatan_id'   => $request->jabatan_id,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'needs'        => $request->needs,
            'tonase'       => $request->tonase,
            'email'        => $request->email,
            'published_at' => now(),
        ]);

        return response()->json([
            'message' => 'Lead registered successfully',
            'data'    => $lead,
        ], 201);
    }

    protected function validateToken(Request $request)
    {
        $token = $request->header('X-API-TOKEN');
        if (!$token || $token !== config('services.lead_register_api_token')) {
            abort(response()->json(['message' => 'Unauthorized'], 401));
        }
    }

    public function sources(Request $request)
    {
        $this->validateToken($request);

        return response()->json([
            'response_id' => (string) Str::uuid(),
            'message'     => 'Success to get Source',
            'data'        => LeadSource::whereNotIn('name', ['Canvas', 'Visit', 'Expo'])->get(['id', 'name'])
        ]);
    }

    public function segments(Request $request)
    {
        $this->validateToken($request);

        return response()->json([
            'response_id' => (string) Str::uuid(),
            'message'     => 'Success to get Segment',
            'data'        => LeadSegment::all(['id', 'name']),
        ]);
    }

    public function regions(Request $request)
    {
        $this->validateToken($request);

        return response()->json([
            'response_id' => (string) Str::uuid(),
            'message'     => 'Success to get Region',
            'data'        => Region::with('province:id,name')
                ->get(['id', 'name', 'province_id']),
        ]);
    }
}
