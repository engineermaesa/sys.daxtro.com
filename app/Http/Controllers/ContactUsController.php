<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Masters\{Region, Province, CustomerType, Industry};
use App\Models\Leads\{Lead, LeadSegment, LeadSource, LeadStatus};

class ContactUsController extends Controller
{
    public function create()
    {
        // Replace HTTP calls with direct database calls
        $sources    = LeadSource::orderBy('name')->get()->toArray() ?? [];
        $segments   = LeadSegment::orderBy('name')->get()->toArray() ?? [];
        $regions    = Region::with('province:id,name')->orderBy('name')->get()->toArray() ?? [];
        $provinces  = Province::orderBy('name')->pluck('name');
        $customerTypes = CustomerType::all();
        $industries = Industry::all();
        $jabatans = \App\Models\Masters\Jabatan::all();

        return view('contact-us', compact('sources', 'segments', 'regions', 'provinces', 'customerTypes', 'industries', 'jabatans'));
    }

    public function store(Request $request)
    {
        \Log::info('Contact form submission started', ['request' => $request->all()]);
        $request->validate([
            'source_id'  => 'required|exists:lead_sources,id',
            'segment_id' => 'nullable|exists:lead_segments,id',
            'region_id'  => 'required|exists:ref_regions,id',
            'province'   => 'required|in:' . Province::pluck('name')->implode(','),
            'factory_city_id' => [
                'nullable',
                function($attribute, $value, $fail) {
                    if ($value !== 'ALL' && ! \App\Models\Masters\Region::where('id', $value)->exists()) {
                        $fail("$attribute is invalid");
                    }
                },
            ],
            'factory_province' => 'nullable|string',
            'factory_industry_id' => [
                'nullable',
                function($attribute, $value, $fail) {
                    if ($value !== null && $value !== 'other' && !Industry::where('id', $value)->exists()) {
                        $fail("$attribute is invalid");
                    }
                },
            ],
            'factory_other_industry' => 'required_if:factory_industry_id,other|nullable|string|max:150',
            'industry_remark' => 'nullable|string',
            'title'      => 'required|in:Mr,Mrs',
            'name'       => 'required',
            'company'    => 'nullable|string|max:150',
            'customer_type' => 'nullable|exists:ref_customer_types,name',
            'contact_reason' => 'nullable|string',
            'business_reason' => 'nullable|string',
            'competitor_offer' => 'nullable|string',
            'phone'      => 'required',
            'email'      => 'required|email',
            'industry_id' => [
                'nullable',
                function($attribute, $value, $fail) {
                    if ($value !== null && $value !== 'other' && !Industry::where('id', $value)->exists()) {
                        $fail("$attribute is invalid");
                    }
                },
            ],
            'other_industry' => 'required_if:industry_id,other|nullable|string|max:150',
            'jabatan_id' => 'nullable|exists:ref_jabatans,id',
            'needs'      => 'required',
            'tonase'     => 'nullable|numeric',
            'tonage_remark' => 'nullable|string',
            'agent_title' => 'nullable|in:Mr,Mrs,Ms,Dr',
            'agent_name' => 'nullable|string|max:150',
            'spk_canvassing' => 'nullable|string|max:255',
        ]);

        try {
            // Handle region_id for "ALL"
            $regionId = $request->region_id === 'ALL' ? null : $request->region_id;
            
            // Handle factory_city_id for "ALL"
            $factoryCityId = $request->factory_city_id === 'ALL' ? null : $request->factory_city_id;
            
            // Handle industry "other" option
            $industryId = $request->industry_id === 'other' ? null : $request->industry_id;
            $industryName = $request->industry_id === 'other' ? $request->other_industry : null;
            
            // Handle factory industry "other" option
            $factoryIndustryId = $request->factory_industry_id === 'other' ? null : $request->factory_industry_id;
            $factoryIndustryName = $request->factory_industry_id === 'other' ? $request->factory_other_industry : null;

            \Log::info('Processed form data', [
            'regionId' => $regionId,
            'factoryCityId' => $factoryCityId,
            'industryId' => $industryId,
            'factoryIndustryId' => $factoryIndustryId,
        ]);

            // Create the lead directly in database
            $lead = Lead::create([
                'source_id' => $request->source_id,
                'segment_id' => $request->segment_id,
                'region_id' => $regionId,
                'status_id' => LeadStatus::PUBLISHED,
                'name' => trim($request->title . ' ' . $request->name),
                'phone' => $request->phone,
                'email' => $request->email,
                'company' => $request->company,
                'needs' => $request->needs,
                'province' => $request->province,
                'factory_city_id' => $factoryCityId,
                'factory_province' => $request->factory_province,
                'factory_industry_id' => $factoryIndustryId,
                'factory_other_industry' => $factoryIndustryName, // Fixed field name
                'industry_remark' => $request->industry_remark,
                'customer_type' => $request->customer_type,
                'contact_reason' => $request->contact_reason,
                'business_reason' => $request->business_reason,
                'competitor_offer' => $request->competitor_offer,
                'industry_id' => $industryId,
                'other_industry' => $industryName,
                'jabatan_id' => $request->jabatan_id,
                'tonase' => $request->tonase,
                'tonage_remark' => $request->tonage_remark,
                'agent_title' => $request->agent_title,
                'agent_name' => $request->agent_name,
                'spk_canvassing' => $request->spk_canvassing,
                'published_at' => now(),
            ]);


            \Log::info('Lead created successfully', ['lead_id' => $lead->id]);

            return redirect()->route('contact-us')
                ->with('success', 'Request submitted successfully.');
            
        } catch (\Exception $e) {
            \Log::error('Contact form submission error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors('Submission failed: ' . $e->getMessage())->withInput();
        }
    }
}