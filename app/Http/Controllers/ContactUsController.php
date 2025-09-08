<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Masters\{Province, CustomerType};

class ContactUsController extends Controller
{
    protected array $headers;

    public function __construct()
    {
        $this->headers = ['X-API-TOKEN' => config('services.lead_register_api_token')];
    }

    public function create()
    {
        $sources    = Http::withHeaders($this->headers)->get(route('api.leads.sources'))->json('data') ?? [];
        $segments   = Http::withHeaders($this->headers)->get(route('api.leads.segments'))->json('data') ?? [];
        $regions    = Http::withHeaders($this->headers)->get(route('api.leads.regions'))->json('data') ?? [];
        $provinces  = Province::orderBy('name')->pluck('name');
        $customerTypes = CustomerType::all();
        $jabatans = \App\Models\Masters\Jabatan::all();

        return view('contact-us', compact('sources', 'segments', 'regions', 'provinces', 'customerTypes', 'jabatans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'source_id'  => 'required|exists:lead_sources,id',
            'segment_id' => 'nullable|exists:lead_segments,id',
            'region_id'  => 'required|exists:ref_regions,id',
            'province'   => 'required|in:' . Province::pluck('name')->implode(','),
            'title'      => 'required|in:Mr,Mrs',
            'name'       => 'required',
            'company'    => 'nullable|string|max:150',
            'customer_type' => 'nullable|exists:ref_customer_types,name',
            'phone'      => 'required',
            'email'      => 'required|email',
            'jabatan_id' => 'nullable|exists:ref_jabatans,id',
            'needs'      => 'required',
            'tonase'     => 'nullable|numeric',
        ]);

        $request->merge(['name' => trim($request->title . ' ' . $request->name)]);

        $response = Http::withHeaders($this->headers)
            ->post(route('api.leads.register'), $request->only([
                'source_id', 'segment_id', 'region_id', 'province', 'name',
                'company', 'customer_type', 'jabatan_id', 'phone', 'email', 'needs', 'tonase'
            ]));

        if ($response->successful()) {
            return redirect()->route('contact-us')
                ->with('success', 'Request submitted.');
        }

        return back()->withErrors('Submission failed.');
    }
}
