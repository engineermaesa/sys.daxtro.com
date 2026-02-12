<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Http\Classes\ActivityLogger;
use App\Services\AutoTrashService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Leads\{Lead, LeadActivityList, LeadClaim, LeadStatus, LeadStatusLog, LeadSource, LeadSegment, LeadPicExtension};
use App\Models\Masters\{Branch, Region, Product, Province, CustomerType, Industry};
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public function available(Request $request)
    {
        $branches = Branch::all();
        $regions  = Region::with('province:id,name')
            ->get(['id', 'name', 'province_id', 'branch_id']);

        // If request comes from API (route starting with /api/) or expects JSON, return JSON
        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'branches' => $branches,
                'regions' => $regions
            ]);
        }

        return view('pages.leads.available', compact('branches', 'regions'));
    }

    public function availableList(Request $request)
    {
        $user = auth()->user();

        $leads = Lead::with(['region', 'source', 'segment', 'status'])
            ->where('status_id', LeadStatus::PUBLISHED);

        if (!in_array($user->role?->code, ['super_admin'])) {
            $leads->where(function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($q) =>
                        $q->where('branch_id', $user->branch_id)
                    );
            });
        }

        if ($request->filled('region_id')) {
            $leads->where('region_id', $request->region_id);
        }

        // Filter
        if ($request->filled('branch_id')) {
            $leads->whereHas('region.branch', function ($q) use ($request) {
                $q->where('id', $request->branch_id);
            });
        }

        if ($request->filled('region_id')) {
            $leads->whereHas('region', function ($q) use ($request) {
                $q->where('id', $request->region_id);
            });
        }

        return DataTables::of($leads)
            ->addColumn('region_name', fn($row) => $row->region->name ?? '')
            ->addColumn('branch_name', fn($row) => $row->region->branch->name ?? '')
            ->addColumn('source_name', fn($row) => $row->source->name ?? '')
            ->addColumn('segment_name', fn($row) => $row->segment->name ?? 'Not Set')
            ->addColumn('status_name', fn($row) => $row->status->name ?? '')
            ->addColumn('published_at', fn($row) => $row->published_at)
            ->addColumn('actions', function ($row) {
                $editUrl  = route('leads.form', $row->id);
                $claimUrl = route('leads.claim', $row->id);

                $btnId = 'availableActionsDropdown' . $row->id;

                // $html  = '<div class="dropdown">';
                // $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                // $html .= '    <i class="bi bi-three-dots-vertical"></i> Actions';
                // $html .= '  </button>';
                // $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
                // $html .= '    <a class="dropdown-item" href="' . e($editUrl) . '"><i class="bi bi-pencil-square mr-2"></i> View</a>';
                // $html .= '    <a class="dropdown-item claim-lead" href="' . e($claimUrl) . '"><i class="bi bi-check-circle mr-2"></i> Claim</a>';
                // $html .= '  </div>';
                // $html .= '</div>';
                $html = '    <a class="text-[#115640] font-semibold" href="' . e($claimUrl) . '"><i class="bi bi-check-circle mr-2"></i> Claim</a>';

                return $html;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form(Request $request, $id = null)
    {
        $form_data = $id
            ? Lead::with([
                'status',
                'source',
                'segment',
                'region',
                'product',
                'meetings.expense.details.expenseType',
                'meetings.expense.financeRequest',
                'meetings.attachment',
                'quotation.items',
                'quotation.proformas',
                'quotation.order.orderItems',
                'quotation.reviews.reviewer',
                'picExtensions',
                'factoryCity',
            ])->findOrFail($id)
            : new Lead();


        $sources  = LeadSource::all();
        $segments = LeadSegment::all();
        $customerTypes = CustomerType::all();
        $industries = Industry::all();
        $jabatans  = \App\Models\Masters\Jabatan::all();
        $regions  = Region::with('province:id,name')
            ->get(['id', 'name', 'province_id', 'branch_id']);
        $products = Product::all();
        $provinces = Province::orderBy('name')->pluck('name');

        $meetings  = $id ? $form_data->meetings->sortByDesc('scheduled_start_at') : collect();
        $quotation = $id ? $form_data->quotation : null;
        $order     = $quotation?->order;

        // If called via API or expects JSON, return structured JSON payload suitable for Postman
        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'form_data' => $form_data ? $form_data->toArray() : null,
                'sources' => $sources,
                'segments' => $segments,
                'customerTypes' => $customerTypes,
                'industries' => $industries,
                'jabatans' => $jabatans,
                'regions' => $regions,
                'products' => $products,
                'provinces' => $provinces,
                'meetings' => $meetings,
                'quotation' => $quotation,
                'order' => $order,
            ]);
        }

        return $this->render('pages.leads.form', compact('form_data', 'sources', 'segments', 'customerTypes', 'industries', 'jabatans', 'regions', 'products', 'provinces', 'meetings', 'quotation', 'order'));
    }

    public function save(Request $request, $id = null)
    {
        try {
            $user    = auth()->user();
            $isSales = $user->role->code === 'sales';

            // 1. Build validation rules
            $segmentRule = $isSales && !$id ? 'required' : 'nullable';
            $rules = [
                'source_id'   => 'required',
                'segment_id'  => $segmentRule,
                'province'    => "nullable",
                'region_id'   => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if ($value !== 'ALL' && ! Region::where('id', $value)->exists()) {
                            $fail("$attribute is invalid");
                        }
                    },
                ],
                'factory_city_id' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if ($value !== 'ALL' && ! Region::where('id', $value)->exists()) {
                            $fail("$attribute is invalid");
                        }
                    },
                ],
                'factory_province' => 'nullable|string',
                // 'factory_industry_id' => 'nullable|exists:ref_industries,id',
                'factory_industry_id' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if ($value !== null && $value !== 'other' && !Industry::where('id', $value)->exists()) {
                            $fail("$attribute is invalid");
                        }
                    },
                ],
                'factory_other_industry' => 'required_if:factory_industry_id,other|nullable|string|max:150',
                'industry_remark' => 'nullable|string',
                'title'       => 'required|in:Mr,Mrs',
                'name'        => 'required',
                'company'     => 'nullable|string|max:150',
                'company_address' => 'required|string',
                'customer_type' => 'nullable|exists:ref_customer_types,name',
                'contact_reason' => 'nullable|string',
                'business_reason' => 'nullable|string',
                'competitor_offer' => 'nullable|string',
                'phone'       => 'required',
                'email'       => 'nullable|email',
                'industry_id' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if ($value !== null && $value !== 'other' && !Industry::where('id', $value)->exists()) {
                            $fail("$attribute is invalid");
                        }
                    },
                ],
                'other_industry' => 'required_if:industry_id,other|nullable|string|max:150',
                'jabatan_id'  => 'nullable|exists:ref_jabatans,id',
                'product_id'  => 'nullable|exists:ref_products,id',
                'needs'       => 'required',
                'tonase'      => 'nullable|numeric',
                'tonage_remark' => 'nullable|string',
                'agent_title' => 'nullable|in:Mr,Mrs,Ms,Dr',
                'agent_name' => 'nullable|string|max:150',
                'spk_canvassing' => 'nullable|string|max:255',
                'pic_extensions.*.title.*' => 'nullable|in:Mr,Mrs',
                'pic_extensions.*.nama.*'  => 'nullable|string',
                'pic_extensions.*.jabatan_id.*' => 'nullable|exists:ref_jabatans,id',
                'pic_extensions.*.email.*' => 'nullable|email',
                'pic_extensions.*.phone.*' => 'nullable|string',
            ];

            // If submitting multiple leads at once, validate the arrays
            if (is_array($request->input('source_id'))) {
                $rules = [
                    'source_id.*'  => 'required',
                    'segment_id.*' => $segmentRule,
                    'province.*'   => "nullable",
                    'region_id.*'  => [
                        'nullable',
                        function ($attribute, $value, $fail) {
                            if ($value !== 'ALL' && ! Region::where('id', $value)->exists()) {
                                $fail("$attribute is invalid");
                            }
                        },
                    ],
                    'factory_city_id.*' => [
                        'nullable',
                        function ($attribute, $value, $fail) {
                            if ($value !== 'ALL' && ! Region::where('id', $value)->exists()) {
                                $fail("$attribute is invalid");
                            }
                        },
                    ],
                    'factory_province.*' => 'nullable|string',
                    'factory_industry_id.*' => [
                        'nullable',
                        function ($attribute, $value, $fail) {
                            if ($value !== null && $value !== 'other' && !Industry::where('id', $value)->exists()) {
                                $fail("$attribute is invalid");
                            }
                        },
                    ],
                    'factory_other_industry.*' => 'required_if:factory_industry_id.*,other|nullable|string|max:150',
                    'industry_remark.*' => 'nullable|string|max:500',
                    'title.*'      => 'required|in:Mr,Mrs',
                    'name.*'       => 'required',
                    'company.*'    => 'nullable|string|max:150',
                    'company_address.*'    => 'nullable|string',
                    'customer_type.*' => 'nullable|exists:ref_customer_types,name',
                    'contact_reason.*' => 'nullable|string',
                    'business_reason.*' => 'nullable|string',
                    'competitor_offer.*' => 'nullable|string',
                    'phone.*'      => 'required',
                    'email.*'      => 'nullable|email',
                    'industry_id.*' => [
                        'nullable',
                        function ($attribute, $value, $fail) {
                            if ($value !== null && $value !== 'other' && !Industry::where('id', $value)->exists()) {
                                $fail("$attribute is invalid");
                            }
                        },
                    ],
                    'other_industry.*' => 'required_if:industry_id.*,other|nullable|string|max:150',
                    'jabatan_id.*' => 'nullable|exists:ref_jabatans,id',
                    'product_id.*' => 'nullable|exists:ref_products,id',
                    'needs.*'      => 'required',
                    'tonase.*'     => 'nullable|numeric',
                    'tonage_remark.*' => 'nullable|string',
                    'agent_title.*' => 'nullable|in:Mr,Mrs,Ms,Dr',
                    'agent_name.*' => 'nullable|string|max:150',
                    'spk_canvassing.*' => 'nullable|string|max:255',
                ];
            }

            $request->validate($rules);

            // 2. Handle multiple leads
            if (is_array($request->input('source_id')) && !$id) {
                $ids = [];

                foreach ($request->source_id as $i => $srcId) {
                    $lead = new Lead();

                    // Add null checks for each array access
                    $rawRegion = isset($request->region_id[$i]) && $request->region_id[$i] === 'ALL'
                        ? null
                        : ($request->region_id[$i] ?? null);

                    $region = $rawRegion ? Region::with('province')->find($rawRegion) : null;
                    $branchId = $region?->branch_id;
                    $provinceName = $region?->province?->name;

                    // Add null checks for factory fields
                    $rawFactoryCity = isset($request->factory_city_id[$i]) && $request->factory_city_id[$i] === 'ALL'
                        ? null
                        : ($request->factory_city_id[$i] ?? null);
                    $factoryCity = $rawFactoryCity ? Region::with('province')->find($rawFactoryCity) : null;

                    // Now set the properties with null coalescing
                    $lead->source_id = $srcId;
                    $lead->segment_id = $request->segment_id[$i] ?? null;
                    $lead->region_id = $rawRegion;
                    $lead->branch_id = $branchId;
                    $lead->province = $rawRegion ? $provinceName : null;
                    $lead->status_id = $isSales ? LeadStatus::COLD : LeadStatus::PUBLISHED;
                    $lead->factory_city_id = $rawFactoryCity;
                    $lead->factory_province = $factoryCity ? $factoryCity->province->name : ($request->factory_province[$i] ?? null);
                    $lead->factory_industry_id = $request->factory_industry_id[$i] ?? null;
                    $lead->industry_remark = $request->industry_remark[$i] ?? null;
                    $lead->company = $request->company[$i] ?? null;
                    $lead->company_address = $request->company_address[$i] ?? null;
                    $lead->customer_type = $request->customer_type[$i] ?? null;
                    $lead->contact_reason = $request->contact_reason[$i] ?? null;
                    $lead->business_reason = $request->business_reason[$i] ?? null;
                    $lead->competitor_offer = $request->competitor_offer[$i] ?? null;
                    $lead->name = trim(($request->title[$i] ?? '') . ' ' . ($request->name[$i] ?? ''));
                    $lead->phone = $request->phone[$i] ?? null;
                    $lead->email = $request->email[$i] ?? null;

                    if (isset($request->industry_id[$i]) && $request->industry_id[$i] === 'other') {
                        $lead->industry_id = null;
                        $lead->other_industry = $request->other_industry[$i] ?? null;
                    } else {
                        $lead->industry_id = $request->industry_id[$i] ?? null;
                        $lead->other_industry = null;
                    }
                    if (isset($request->factory_industry_id[$i]) && $request->factory_industry_id[$i] === 'other') {
                        $lead->factory_industry_id = null; // Not an array
                        $lead->factory_other_industry = $request->factory_other_industry[$i] ?? null;
                    } else {
                        $lead->factory_industry_id = $request->factory_industry_id[$i] ?? null;
                        $lead->factory_other_industry = null;
                    }
                    $lead->jabatan_id = $request->jabatan_id[$i] ?? null;
                    $lead->product_id = $request->product_id[$i] ?? null;
                    $lead->needs = $request->needs[$i] ?? null;
                    $lead->tonase = $request->tonase[$i] ?? null;
                    $lead->tonage_remark = $request->tonage_remark[$i] ?? null;
                    $lead->agent_title = $request->agent_title[$i] ?? null;
                    $lead->agent_name = $request->agent_name[$i] ?? null;
                    $lead->spk_canvassing = $request->spk_canvassing[$i] ?? null;
                    $lead->published_at = now();

                    $lead->save();

                    $extras = $request->input('pic_extensions.' . $i, []);
                    if ($extras) {
                        $count = count($extras['nama'] ?? []);
                        for ($x = 0; $x < $count; $x++) {
                            if (($extras['nama'][$x] ?? '') === '' && ($extras['email'][$x] ?? '') === '' && ($extras['phone'][$x] ?? '') === '') {
                                continue;
                            }
                            $lead->picExtensions()->create([
                                'nama'  => $extras['nama'][$x] ?? null,
                                'jabatan_id' => $extras['jabatan_id'][$x] ?? null,
                                'email' => $extras['email'][$x] ?? null,
                                'phone' => $extras['phone'][$x] ?? null,
                                'title' => $extras['title'][$x] ?? null,
                            ]);
                        }
                    }

                    if ($isSales) {
                        LeadClaim::create([
                            'lead_id'    => $lead->id,
                            'sales_id'   => $user->id,
                            'claimed_at' => now(),
                        ]);
                    }

                    ActivityLogger::writeLog(
                        'create_lead',
                        'Created new lead',
                        $lead,
                        ['after' => $lead->fresh()->toArray()],
                        $user
                    );

                    $ids[] = $lead->id;
                }
                \Log::info('Request data:', $request->all());

                return $this->setJsonResponse('Leads saved successfully', ['ids' => $ids]);
            }

            // 3. Single-lead create or update
            $lead   = $id ? Lead::findOrFail($id) : new Lead();
            $before = $id ? $lead->toArray() : null;

            $rawRegion = $request->region_id === 'ALL'
                ? null
                : $request->region_id;
            $region = $rawRegion ? Region::with('province')->find($rawRegion) : null;
            $branchId = $region?->branch_id;
            $provinceName = $region?->province?->name;

            $lead->source_id    = $request->source_id;
            $lead->segment_id   = $request->segment_id;
            $lead->region_id    = $rawRegion;
            $lead->branch_id    = $branchId;
            $lead->province     = $rawRegion ? $provinceName : null;
            if (! $id) {
                $lead->status_id = $isSales ? LeadStatus::COLD : LeadStatus::PUBLISHED;
            }
            $rawFactoryRegion = ($request->factory_city_id ?? null) === 'ALL'
                ? null
                : ($request->factory_city_id ?? null);
            $factoryRegion = $rawFactoryRegion ? Region::with('province')->find($rawFactoryRegion) : null;
            $lead->factory_city_id = $rawFactoryRegion;
            $lead->factory_province = $factoryRegion ? $factoryRegion->province->name : $request->factory_province;
            $lead->factory_industry_id = $request->factory_industry_id;
            $lead->industry_remark = $request->industry_remark;
            $lead->company      = $request->company;
            $lead->company_address = $request->company_address;
            $lead->customer_type = $request->customer_type;
            $lead->contact_reason = $request->contact_reason;
            $lead->business_reason = $request->business_reason;
            $lead->competitor_offer = $request->competitor_offer;
            $lead->name         = trim($request->title . ' ' . $request->name);
            $lead->phone        = $request->phone;
            $lead->email        = $request->email;
            if ($request->industry_id === 'other') {
                $lead->industry_id   = null;
                $lead->other_industry = $request->other_industry;
            } else {
                $lead->industry_id   = $request->industry_id;
                $lead->other_industry = null;
            }
            if ($request->factory_industry_id === 'other') {
                $lead->factory_industry_id = null;
                $lead->factory_other_industry = $request->factory_other_industry;
            } else {
                $lead->factory_industry_id = $request->factory_industry_id;
                $lead->factory_other_industry = null;
            }
            $lead->jabatan_id   = $request->jabatan_id;
            $lead->product_id   = $request->product_id;
            $lead->needs        = $request->needs;
            $lead->tonase       = $request->tonase;
            $lead->tonage_remark = $request->tonage_remark;
            $lead->agent_title = $request->agent_title;
            $lead->agent_name = $request->agent_name;
            $lead->spk_canvassing = $request->spk_canvassing;
            $lead->published_at = $id ? $lead->published_at : now();
            $lead->save();

            if (! $id && $isSales) {
                LeadClaim::create([
                    'lead_id'    => $lead->id,
                    'sales_id'   => $user->id,
                    'claimed_at' => now(),
                ]);
            }

            $lead->picExtensions()->delete();
            $extras = $request->input('pic_extensions.0', []);
            if ($extras) {
                $count = count($extras['nama'] ?? []);
                for ($x = 0; $x < $count; $x++) {
                    if (($extras['nama'][$x] ?? '') === '' && ($extras['email'][$x] ?? '') === '' && ($extras['phone'][$x] ?? '') === '') {
                        continue;
                    }
                    $lead->picExtensions()->create([
                        'nama'  => $extras['nama'][$x] ?? null,
                        'jabatan_id' => $extras['jabatan_id'][$x] ?? null,
                        'email' => $extras['email'][$x] ?? null,
                        'phone' => $extras['phone'][$x] ?? null,
                        'title' => $extras['title'][$x] ?? null,
                    ]);
                }
            }

            $after = $lead->fresh()->toArray();
            ActivityLogger::writeLog(
                $id ? 'update_lead' : 'create_lead',
                $id ? 'Updated lead' : 'Created new lead',
                $lead,
                ['before' => $before, 'after' => $after],
                $user
            );

            return $this->setJsonResponse('Lead saved successfully');
        } catch (\Exception $e) {
            \Log::error('Lead Save Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all() // Log the request data
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error saving lead: ' . $e->getMessage()
            ], 500);
        }
    }


    public function claim($id)
    {
        $lead = Lead::findOrFail($id);

        LeadClaim::create([
            'lead_id'    => $lead->id,
            'sales_id'   => request()->user()->id,
            'claimed_at' => now(),
        ]);

        $lead->update(['status_id' => LeadStatus::COLD]);

        LeadStatusLog::create([
            'lead_id'   => $lead->id,
            'status_id' => LeadStatus::COLD,
        ]);

        return $this->setJsonResponse('Lead claimed successfully');
    }

    public function trash($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->update(['status_id' => LeadStatus::TRASH_COLD]);

        LeadStatusLog::create([
            'lead_id'   => $lead->id,
            'status_id' => LeadStatus::TRASH_COLD,
        ]);

        return $this->setJsonResponse('Lead moved to trash');
    }

    public function my()
    {
        AutoTrashService::triggerIfNeeded();
            
        $user = auth()->user();

        $claims = LeadClaim::whereNull('released_at')
            ->with('lead');

        if ($user->role?->code === 'sales') {
            $claims->where('sales_id', $user->id);
        }

        $counts = $claims->get()
            ->groupBy(fn($claim) => $claim->lead->status_id)
            ->map->count();

        $cold = $counts[LeadStatus::COLD] ?? 0;
        $warm = $counts[LeadStatus::WARM] ?? 0;
        $hot  = $counts[LeadStatus::HOT] ?? 0;
        $deal = $counts[LeadStatus::DEAL] ?? 0;

        $all = $cold + $warm + $hot + $deal;
        return view('pages.leads.my', [
            'leadCounts' => [
                'all'  => $all,
                'cold' => $counts[LeadStatus::COLD] ?? 0,
                'warm' => $counts[LeadStatus::WARM] ?? 0,
                'hot'  => $counts[LeadStatus::HOT] ?? 0,
                'deal' => $counts[LeadStatus::DEAL] ?? 0,
            ],
            'activities' => LeadActivityList::all(),
        ]);
    }
    

    public function myCounts(Request $request)
    {
        // Trigger auto-trash if needed (non-blocking)
        AutoTrashService::triggerIfNeeded();
        
        $claims = LeadClaim::whereNull('released_at');

        if ($request->user()->role?->code === 'sales') {
            $claims->where('sales_id', $request->user()->id);
        }

        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        $cold = (clone $claims)
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::COLD))
            ->where('claimed_at', '>=', now()->subDays(10))
            ->count();

        $warmQuery = (clone $claims)
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::WARM))
            ->where('claimed_at', '>=', now()->subDays(30));
        if ($start && $end) {
            $warmQuery->whereHas('lead.quotation', fn($q) => $q->firstApprovalBetween($start, $end));
        }
        $warm = $warmQuery->count();

        $hotQuery = (clone $claims)
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::HOT));
        if ($start && $end) {
            $hotQuery->whereHas('lead.quotation', fn($q) => $q->bookingFeeBetween($start, $end));
        }
        $hot = $hotQuery->count();

        $dealQuery = (clone $claims)
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::DEAL));
        if ($start && $end) {
            $dealQuery->whereHas('lead.quotation', fn($q) => $q->firstTermPaidBetween($start, $end));
        }
        $deal = $dealQuery->count();

        return response()->json([
            'cold' => $cold,
            'warm' => $warm,
            'hot'  => $hot,
            'deal' => $deal,
        ]);
    }

    public function manageCounts(Request $request)
    {
        if ($request->user()->role?->code === 'sales') {
            abort(403);
        }

        $user = $request->user();
        $leads = Lead::query();

        // Auto-apply user's branch_id for branch managers and similar roles
        $branchId = $request->filled('branch_id') ? $request->branch_id : null;
        if (!$branchId && $user->branch_id && in_array($user->role?->code, ['branch_manager', 'finance', 'accountant', 'purchasing'])) {
            $branchId = $user->branch_id;
        }

        if ($branchId) {
            $leads->whereHas('region.branch', fn($q) => $q->where('id', $branchId));
        }

        if ($request->filled('region_id')) {
            $leads->where('region_id', $request->region_id);
        }

        if ($request->filled('sales_id')) {
            $leads->whereHas('claims', function ($q) use ($request) {
                $q->where('sales_id', $request->sales_id)
                    ->whereNull('released_at');
            });
        }

        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        // Apply branch_id filter specifically for each status lead if provided
        $coldQuery = (clone $leads)
            ->where('status_id', LeadStatus::COLD);
        if ($branchId) {
            $coldQuery->where('branch_id', $branchId);
        }
        $cold = $coldQuery->count();

        $warmQuery = (clone $leads)
            ->where('status_id', LeadStatus::WARM);
        if ($branchId) {
            $warmQuery->where('branch_id', $branchId);
        }
        if ($start && $end) {
            $warmQuery->whereHas('quotation', fn($q) => $q->firstApprovalBetween($start, $end));
        }
        $warm = $warmQuery->count();

        $hotQuery = (clone $leads)
            ->where('status_id', LeadStatus::HOT);
        if ($branchId) {
            $hotQuery->where('branch_id', $branchId);
        }
        if ($start && $end) {
            $hotQuery->whereHas('quotation', fn($q) => $q->bookingFeeBetween($start, $end));
        }
        $hot = $hotQuery->count();

        $dealQuery = (clone $leads)
            ->where('status_id', LeadStatus::DEAL);
        if ($branchId) {
            $dealQuery->where('branch_id', $branchId);
        }
        if ($start && $end) {
            $dealQuery->whereHas('quotation', fn($q) => $q->firstTermPaidBetween($start, $end));
        }
        $deal = $dealQuery->count();

        return response()->json([
            'cold' => $cold,
            'warm' => $warm,
            'hot'  => $hot,
            'deal' => $deal,
        ]);
    }

    public function manage()
    {
        $user = request()->user();
        $userRole = $user->role?->code;
        if ($userRole === 'sales') {
            abort(403);
        }

        // Trigger auto-trash if needed (non-blocking)
        AutoTrashService::triggerIfNeeded();

        $branches = Branch::all();
        $regions  = Region::all();


        $userBranchId = $user->branch_id && in_array($userRole, ['branch_manager', 'finance', 'accountant', 'purchasing']) 
            ? $user->branch_id : null;

        $countsQuery = Lead::select('status_id', DB::raw('COUNT(*) as cnt'))
            ->whereIn('status_id', [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL,
            ]);

        if ($userBranchId) {
            $countsQuery->where('branch_id', $userBranchId);
        }

        $counts = $countsQuery->groupBy('status_id')->pluck('cnt', 'status_id');

        $leadCounts = [
        'cold' => $counts[LeadStatus::COLD] ?? 0,
        'warm' => $counts[LeadStatus::WARM] ?? 0,
        'hot'  => $counts[LeadStatus::HOT]  ?? 0,
        'deal' => $counts[LeadStatus::DEAL] ?? 0,
    ];

        $activities = \App\Models\Leads\LeadActivityList::all();

        return view('pages.leads.manage', compact('branches', 'regions', 'leadCounts', 'activities', 'user', 'userBranchId'));
    }

    public function manageList(Request $request)
    {
        if ($request->user()->role?->code === 'sales') {
            abort(403);
        }

        // Trigger auto-trash if needed (non-blocking)
        AutoTrashService::triggerIfNeeded();

        $leads = Lead::with([
            'region.branch',
            'region.regional',
            'source',
            'industry',
            'segment',
            'status',
            'quotation',
            'industry',
            'claims' => function ($query) {
                $query->whereNull('released_at')
                    ->latest('claimed_at')
                    ->with('sales');
            }
        ]);


        $branchId = $request->filled('branch_id') ? $request->branch_id : null;
        if (!$branchId && $user->branch_id && in_array($user->role?->code, ['branch_manager', 'finance', 'accountant', 'purchasing'])) {
            $branchId = $user->branch_id;
        }

        if ($branchId) {
            $leads->where(function($q) use ($branchId) {
                $q->whereHas('region.branch', function ($subq) use ($branchId) {
                    $subq->where('id', $branchId);
                })
                ->orWhere('branch_id', $branchId);
            });
        }

        if ($request->filled('region_id')) {
            $leads->where('region_id', $request->region_id);
        }

        if ($request->filled('sales_id')) {
            $leads->whereHas('claims', function ($q) use ($request) {
                $q->where('sales_id', $request->sales_id)
                    ->whereNull('released_at');
            });
        }

        if ($request->filled('status_id')) {
            $leads->where('status_id', $request->status_id);
            
            // Apply day filters for Cold and Warm leads like in My Leads
            $status = (int) $request->status_id;
            if ($status === LeadStatus::COLD) {
                // Cold leads: only show leads claimed within last 10 days
                $leads->whereHas('claims', function ($q) {
                    $q->whereNull('released_at')
                      ->where('claimed_at', '>=', now()->subDays(10));
                });
            } elseif ($status === LeadStatus::WARM) {
                // Warm leads: only show leads claimed within last 30 days
                $leads->whereHas('claims', function ($q) {
                    $q->whereNull('released_at')
                      ->where('claimed_at', '>=', now()->subDays(30));
                });
            }
        }

        if ($branchId && $request->filled('status_id')) {
            $statusId = (int) $request->status_id;
            $branchIdInt = (int) $branchId;
            
            if (in_array($statusId, [LeadStatus::COLD, LeadStatus::WARM, LeadStatus::HOT, LeadStatus::DEAL])) {
                $leads->where('branch_id', $branchIdInt);
            }
        }

        if ($request->filled('start_date') && $request->filled('end_date') && $request->filled('status_id')) {
            $leads->whereHas('quotation', function ($q) use ($request) {
                $status = (int) $request->status_id;
                if ($status === LeadStatus::WARM) {
                    $q->firstApprovalBetween($request->start_date, $request->end_date);
                } elseif ($status === LeadStatus::HOT) {
                    $q->bookingFeeBetween($request->start_date, $request->end_date);
                } elseif ($status === LeadStatus::DEAL) {
                    $q->firstTermPaidBetween($request->start_date, $request->end_date);
                }
            });
        }

        $role = $request->user()->role?->code;

        return DataTables::of($leads)
            ->addColumn('sales_name', function ($lead) {
                return $lead->claims->first()?->sales?->name ?? '-';
            })
            ->addColumn('phone', fn($row) => $row->phone)
            ->addColumn('needs', fn($row) => $row->needs)
            ->addColumn('source_name', fn($row) => $row->source->name ?? '')
            ->addColumn('segment_name', fn($row) => $row->segment->name ?? '')
            ->addColumn('city_name', fn($row) => $row->region->name ?? 'All Regions')
            ->addColumn('regional_name', fn($row) => $row->region->regional->name ?? '-')
            ->addColumn('customer_type', function ($lead) {
                return $lead->customer_type ?? '-';
            })
            ->addColumn('existing_industries', function ($lead) {
                return $lead->industry->name ?? '-';
            })
            ->addColumn('product_description', function ($lead) {
                return $lead->product_id ? ($lead->product->description ?? '') : ($lead->needs ?? '');
            })
            ->addColumn('quotation_number', function ($lead) {
                return $lead->quotation->quotation_no ?? '-';
            })
            ->addColumn('quotation_price', function ($lead) {
                return $lead->quotation ? number_format($lead->quotation->grand_total ?? 0, 2) : '-';
            })
            ->addColumn('invoice_number', function ($lead) {
                return $lead->quotation?->proformas->first()?->invoice?->invoice_no ?? '-';
            })
            ->addColumn('invoice_price', function ($lead) {
                return $lead->quotation?->proformas->first()?->invoice ?
                    number_format($lead->quotation->proformas->first()->invoice->amount ?? 0, 2) : '-';
            })
           ->addColumn('quot_created', function($lead) {
                // Ambil dari published_at lead atau quotation published_at
                if ($lead->published_at) {
                    return \Carbon\Carbon::parse($lead->published_at)->format('d/m/Y');
                } elseif ($lead->quotation?->published_at) {
                    return \Carbon\Carbon::parse($lead->quotation->published_at)->format('d/m/Y');
                } else {
                    return '-';
                }
            })
            ->addColumn('quot_end_date', function($lead) {
                // Menggunakan updated_at sesuai permintaan
                return $lead->updated_at ? 
                    \Carbon\Carbon::parse($lead->updated_at)->format('d/m/Y') : '-';
            })
            ->addColumn('act_last_time', function($lead) {
                // Debug: lihat semua activity logs untuk lead ini
                $activities = $lead->activityLogs;
                
                if ($activities->isEmpty()) {
                    return '-';
                }
                
                // Ambil yang terbaru berdasarkan logged_at dan id
                $latestActivity = $activities->sortByDesc(function($activity) {
                    // Combine logged_at timestamp with id for precise sorting
                    return strtotime($activity->logged_at) . str_pad($activity->id, 10, '0', STR_PAD_LEFT);
                })->first();
                
                return $latestActivity ? 
                    \Carbon\Carbon::parse($latestActivity->logged_at)->format('d/m/Y') : '-';
            })
            ->addColumn('act_status', function($lead) {
                // Debug: lihat semua activity logs untuk lead ini
                $activities = $lead->activityLogs;
                
                if ($activities->isEmpty()) {
                    return '-';
                }
                
                // Ambil yang terbaru berdasarkan logged_at dan id
                $latestActivity = $activities->sortByDesc(function($activity) {
                    // Combine logged_at timestamp with id for precise sorting
                    return strtotime($activity->logged_at) . str_pad($activity->id, 10, '0', STR_PAD_LEFT);
                })->first();
                
                return $latestActivity?->activity?->name ?? '-';
            })
            ->addColumn('actions', function ($row) use ($role) {
                $editUrl   = route('leads.manage.form', $row->id);
                // $deleteUrl = route('leads.manage.delete', $row->id);
                $quote     = $row->quotation;
                $quoteUrl  = $quote ? route('quotations.show', $quote->id) : null;

                $btnId = 'manageActionsDropdown' . $row->id;

                $html  = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i> Actions';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
                $html .= '    <a class="dropdown-item" href="' . e($editUrl) . '"><i class="bi bi-pencil-square mr-2"></i> View Lead</a>';
                $activityUrl = route('leads.activity.logs', $row->id);
                $html .= '    <button type="button" class="dropdown-item btn-activity-log" data-url="' . e($activityUrl) . '"><i class="bi bi-list-check mr-2"></i> View / Add Activity</button>';

                if (in_array($role, ['branch_manager', 'sales_director', 'sales']) && $quote) {
                    $html .= '  <a class="dropdown-item" href="' . e($quoteUrl) . '"><i class="bi bi-file-earmark-text mr-2"></i> View Quotation</a>';
                    $logUrl = route('quotations.logs', $quote->id);
                    $html .= '  <button type="button" class="dropdown-item btn-quotation-log" data-url="' . e($logUrl) . '"><i class="bi bi-clock-history mr-2"></i> Quotation Log</button>';
                }

                $claim = $row->claims()->whereNull('released_at')->latest('claimed_at')->first();
                $meeting = $row->meetings()->latest()->first();

                if ($claim && $row->status_id === LeadStatus::COLD && ! $meeting) {
                    $coldTrashUrl = route('leads.my.cold.trash', $claim->id);
                    $html .= '  <button class="dropdown-item text-danger trash-lead" data-url="' . e($coldTrashUrl) . '"><i class="bi bi-trash mr-2"></i> Trash Lead</button>';
                }

                if ($claim && $row->status_id === LeadStatus::WARM && (! $quote || $quote->status !== 'published')) {
                    $warmTrashUrl = route('leads.my.warm.trash', $claim->id);
                    $html .= '  <button class="dropdown-item text-danger trash-lead" data-url="' . e($warmTrashUrl) . '"><i class="bi bi-trash mr-2"></i> Trash Lead</button>';
                }

                // $html .= '  <a href="' . e($deleteUrl) . '" data-id="' . $row->id . '" data-table="none" class="dropdown-item text-danger delete-data"><i class="bi bi-trash mr-2"></i> Delete Lead</a>';

                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value'] != '') {
                    $search = $request->search['value'];
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('needs', 'like', "%{$search}%")
                        ->orWhere('city_name', 'like', "%{$search}%")
                        ->orWhereHas('source', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('claims.sales', fn($sq) => $sq->where('name', 'like', "%{$search}%")) // Changed from claims.user to claims.sales
                        ->orWhereHas('quotation', fn($sq) => $sq->where('quotation_no', 'like', "%{$search}%")) // Changed from quotation_number to quotation_no
                        ->orWhereHas('quotation.proformas.invoice', fn($sq) => $sq->where('invoice_no', 'like', "%{$search}%")); // Changed from invoice_number to invoice_no
                    });
                }
            })
            ->rawColumns(['actions', 'status_name'])
            ->make(true);
    }

    public function delete($id)
    {
        $lead = Lead::with(['claims', 'statusLogs'])->findOrFail($id);

        $user         = request()->user();
        $isSuperAdmin = $user->role?->code === 'super_admin';

        if ($lead->claims()->exists() && ! $isSuperAdmin) {
            return response()->json([
                'status'  => false,
                'message' => 'Lead cannot be deleted because it has been claimed.'
            ], 400);
        }

        DB::transaction(function () use ($lead) {
            // Remove related records permanently to satisfy DB constraints
            $lead->claims()->withTrashed()->forceDelete();
            $lead->statusLogs()->withTrashed()->forceDelete();

            $lead->delete();
        });

        ActivityLogger::writeLog(
            'delete_lead',
            'Deleted lead',
            $lead,
            $lead->toArray(),
            $user
        );

        return $this->setJsonResponse('Lead deleted successfully');
    }

    public function availableExport(Request $request)
    {
        $user = $request->user();

        $leads = Lead::with(['region.branch', 'source', 'segment'])
            ->where('status_id', LeadStatus::PUBLISHED);

        if (! in_array($user->role?->code, ['super_admin'])) {
            $leads->where(function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($q) =>
                        $q->where('branch_id', $user->branch_id)
                    );
            });
        }

        if ($request->filled('branch_id')) {
            $leads->whereHas('region.branch', fn($q) => $q->where('id', $request->branch_id));
        }

        if ($request->filled('region_id')) {
            $leads->where('region_id', $request->region_id);
        }

        $rows   = [];
        $rows[] = ['Published At', 'Sales Name', 'Name', 'Branch', 'Region', 'Source', 'Segment', 'Customer Type', 'Product Description', 'Quotation Number', 'Quotation Price', 'Invoice', 'Invoice Price'];

        foreach ($leads->orderByDesc('id')->get() as $lead) {
            $rows[] = [
                $lead->published_at,
                $claim?->sales?->name ?? '-',
                $lead->name,
                $lead->region->branch->name ?? '',
                $lead->region->name ?? '',
                $lead->source->name ?? '',
                $lead->segment->name ?? '',
                $lead->customer_type ?? '',
                $lead->product_id ? ($lead->product->name ?? '') : ($lead->needs ?? ''),
                $lead->quotation ? ($lead->quotation->number ?? '-') : '-',
                $lead->quotation ? ($lead->quotation->total_price ? number_format($lead->quotation->total_price, 2) : '-') : '-',
                $lead->invoice ? ($lead->invoice->number ?? '-') : '-',
                $lead->invoice ? ($lead->invoice->total_price ? number_format($lead->invoice->total_price, 2) : '-') : '-',
            ];
        }

        $file = $this->createXlsx($rows);

        return response()->download($file, 'available_leads_' . date('Ymd_His') . '.xlsx')->deleteFileAfterSend(true);
    }

    public function manageExport(Request $request)
    {
        if ($request->user()->role?->code === 'sales') {
            abort(403);
        }

        $leads = Lead::with([
            'region.branch',
            'source',
            'segment',
            'claims.sales',
            'quotation',
            'quotation.proformas' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'quotation.proformas.invoice'
        ])
            ->when(
                $request->filled('status_id'),
                fn($q) =>
                $q->where('status_id', $request->status_id)
            );

        if ($request->filled('branch_id')) {
            $leads->whereHas('region.branch', fn($q) => $q->where('id', $request->branch_id));
        }

        if ($request->filled('region_id')) {
            $leads->where('region_id', $request->region_id);
        }

        if ($request->filled('sales_id')) {
            $leads->whereHas('claims', function ($q) use ($request) {
                $q->where('sales_id', $request->sales_id)
                    ->whereNull('released_at');
            });
        }

        $rows   = [];
        $rows[] = [
            'Published At',
            'Sales Name',
            'Customer Name',
            'Branch',
            'Region',
            'Source',
            'Segment',
            'Customer Type',
            'Product Description',
            'Quotation Number',
            'Quotation Price',
            'Invoice',
            'Invoice Price'
        ];

        foreach ($leads->orderByDesc('id')->get() as $lead) {
            $claim = $lead->claims()->latest()->first();

            $quotation = $lead->quotation;

            // Get latest proforma and its invoice
            $latestProforma = $quotation?->proformas->first();
            $invoice = $latestProforma?->invoice;

            $rows[] = [
                $lead->published_at, // published at
                $claim?->sales?->name ?? '-', // sales name
                $lead->name, // customer name
                $lead->region->branch->name ?? '', // branch region
                $lead->region->name ?? '', // region name
                $lead->source->name ?? '', // source name
                $lead->segment->name ?? '', // segment name
                $lead->customer_type ?? '', // customer type
                $lead->product_id ? ($lead->product->description ?? '') : ($lead->needs ?? ''), // product description
                $quotation ? $quotation->quotation_no : '-',
                $quotation ? number_format($quotation->grand_total ?? 0, 2) : '-',
                $invoice ? $invoice->invoice_no : '-',
                $invoice ? number_format($invoice->amount ?? 0, 2) : '-'
            ];
        }

        $file = $this->createXlsx($rows);

        return response()->download($file, 'leads_' . date('Ymd_His') . '.xlsx')->deleteFileAfterSend(true);
    }

    private function columnLetter(int $number): string
    {
        $letter = '';
        while ($number > 0) {
            $mod    = ($number - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $number = (int)(($number - $mod) / 26);
        }
        return $letter;
    }

    private function buildSheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $rIndex => $row) {
            $xml .= '<row r="' . ($rIndex + 1) . '">';
            foreach ($row as $cIndex => $value) {
                $cell = $this->columnLetter($cIndex + 1) . ($rIndex + 1);
                $xml  .= '<c r="' . $cell . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $value) . '</t></is></c>';
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private function createXlsx(array $rows): string
    {
        $contentTypes = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
            <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
            <Default Extension="xml" ContentType="application/xml"/>
            <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
            <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
        </Types>
        XML;

        $rels = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
            <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
        </Relationships>
        XML;

        $workbook = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
            <sheets>
                <sheet name="Sheet1" sheetId="1" r:id="rId1"/>
            </sheets>
        </workbook>
        XML;

        $workbookRels = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
            <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
        </Relationships>
        XML;

        $sheet = $this->buildSheetXml($rows);

        $tempFile = tempnam(sys_get_temp_dir(), 'leads_');
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return $tempFile;
    }

    public function myColdList(Request $request)
    {
        // Trigger auto-trash if needed (non-blocking)
        AutoTrashService::triggerIfNeeded();
        
        $user = $request->user();

        $claims = LeadClaim::whereNull('released_at')
            ->with(['lead.region.regional', 'lead.source', 'lead.segment', 'lead.meetings', 'sales']);

        if ($user->role?->code === 'sales') {
            $claims->where('sales_id', $user->id);
        }

        $claims->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::COLD))
               ->where('claimed_at', '>=', now()->subDays(10));

        return DataTables::of($claims)
            ->addColumn('name', fn($row) => $row->lead->name ?? '')
            ->addColumn('sales_name', fn($row) => $row->sales->name ?? '')
            ->addColumn('phone', fn($row) => $row->lead->phone ?? '')
            ->addColumn('source', fn($row) => $row->lead->source->name ?? '')
            ->addColumn('needs', fn($row) => $row->lead->needs ?? '')
            ->addColumn('segment_name', fn($row) => $row->lead->segment->name ?? '')
            ->addColumn('city_name', fn($row) => $row->lead->region->name ?? 'All Regions')
            ->addColumn('regional_name', fn($row) => $row->lead->region->regional->name ?? '')
            ->addColumn('meeting_status', function ($row) {
                $meeting = $row->lead->meetings()->latest()->first();
                if (!$meeting) {
                    return '<span class="badge badge-secondary">No Meeting</span>';
                }

                $status = $meeting->status ?? 'pending';
                $badgeClass = [
                    'pending' => 'badge-warning',
                    'approved' => 'badge-success',
                    'rejected' => 'badge-danger',
                    'cancelled' => 'badge-secondary'
                ][$status] ?? 'badge-secondary';

                return '<span class="badge ' . $badgeClass . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                $lead = $row->lead;
                $editUrl = route('leads.form', $lead->id);
                $btnId = 'coldActionsDropdown' . $lead->id;

                $html = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i>';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-menu-right">';
                $html .= '    <a class="dropdown-item" href="' . e($editUrl) . '"><i class="bi bi-pencil-square mr-2"></i> View</a>';

                $activityUrl = route('leads.activity.logs', $lead->id);
                $html .= '    <button type="button" class="dropdown-item btn-activity-log" data-url="' . e($activityUrl) . '"><i class="bi bi-list-check mr-2"></i> Activity Log</button>';

                $meeting = $lead->meetings()->latest()->first();
                if (!$meeting) {
                    $coldTrashUrl = route('leads.my.cold.trash', $row->id);
                    $html .= '  <button class="dropdown-item text-danger trash-lead" data-url="' . e($coldTrashUrl) . '"><i class="bi bi-trash mr-2"></i> Trash Lead</button>';
                } else {
                    $cancelUrl = route('leads.meeting.cancel', $meeting->id);
                    $html .= '  <button class="dropdown-item text-warning cancel-meeting" data-url="' . e($cancelUrl) . '" data-online="' . ($meeting->is_online ? 1 : 0) . '" data-status="' . ($meeting->status ?? 'pending') . '"><i class="bi bi-x-circle mr-2"></i> Cancel Meeting</button>';
                }

                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['meeting_status', 'actions'])
            ->make(true);
    }

    public function myWarmList(Request $request)
    {
        // Trigger auto-trash if needed (non-blocking)
        AutoTrashService::triggerIfNeeded();
        
        $user = $request->user();

        $claims = LeadClaim::whereNull('released_at')
            ->with(['lead.industry', 'lead.segment', 'lead.quotation', 'sales']);

        if ($user->role?->code === 'sales') {
            $claims->where('sales_id', $user->id);
        }

        $claims->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::WARM))
               ->where('claimed_at', '>=', now()->subDays(30));

        // Apply date filtering if provided
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $claims->whereHas('lead.quotation', function ($q) use ($request) {
                $q->firstApprovalBetween($request->start_date, $request->end_date);
            });
        }

        return DataTables::of($claims)
            ->addColumn('claimed_at', fn($row) => $row->claimed_at)
            ->addColumn('lead_name', fn($row) => $row->lead->name ?? '')
            ->addColumn('industry_name', fn($row) => $row->lead->industry->name ?? null)
            ->addColumn('other_industry', fn($row) => $row->lead->other_industry ?? null)
            ->addColumn('segment_name', fn($row) => $row->lead->segment->name ?? '')
            ->addColumn('meeting_status', function ($row) {
                return '<span class="badge badge-warning">Warm</span>';
            })
            ->addColumn('actions', function ($row) {
                $lead = $row->lead;
                $editUrl = route('leads.form', $lead->id);
                $btnId = 'warmActionsDropdown' . $lead->id;

                $html = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i>';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-menu-right">';
                $html .= '    <a class="dropdown-item" href="' . e($editUrl) . '"><i class="bi bi-pencil-square mr-2"></i> View</a>';

                $activityUrl = route('leads.activity.logs', $lead->id);
                $html .= '    <button type="button" class="dropdown-item btn-activity-log" data-url="' . e($activityUrl) . '"><i class="bi bi-list-check mr-2"></i> Activity Log</button>';

                if ($lead->quotation) {
                    $quoteUrl = route('quotations.show', $lead->quotation->id);
                    $html .= '  <a class="dropdown-item" href="' . e($quoteUrl) . '"><i class="bi bi-file-earmark-text mr-2"></i> View Quotation</a>';

                    $logUrl = route('quotations.logs', $lead->quotation->id);
                    $html .= '  <button type="button" class="dropdown-item btn-quotation-log" data-url="' . e($logUrl) . '"><i class="bi bi-clock-history mr-2"></i> Quotation Log</button>';
                }

                if (!$lead->quotation || $lead->quotation->status !== 'published') {
                    $warmTrashUrl = route('leads.my.warm.trash', $row->id);
                    $html .= '  <button class="dropdown-item text-danger trash-lead" data-url="' . e($warmTrashUrl) . '"><i class="bi bi-trash mr-2"></i> Trash Lead</button>';
                }

                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['meeting_status', 'actions'])
            ->make(true);
    }

    public function myHotList(Request $request)
    {
        // Trigger auto-trash if needed (non-blocking)
        AutoTrashService::triggerIfNeeded();
        
        $user = $request->user();

        $claims = LeadClaim::whereNull('released_at')
            ->with(['lead.industry', 'lead.segment', 'lead.quotation', 'sales']);

        if ($user->role?->code === 'sales') {
            $claims->where('sales_id', $user->id);
        }

        $claims->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::HOT));

        // Apply date filtering if provided
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $claims->whereHas('lead.quotation', function ($q) use ($request) {
                $q->bookingFeeBetween($request->start_date, $request->end_date);
            });
        }

        return DataTables::of($claims)
            ->addColumn('claimed_at', fn($row) => $row->claimed_at)
            ->addColumn('lead_name', fn($row) => $row->lead->name ?? '')
            ->addColumn('industry_name', fn($row) => $row->lead->industry->name ?? null)
            ->addColumn('other_industry', fn($row) => $row->lead->other_industry ?? null)
            ->addColumn('segment_name', fn($row) => $row->lead->segment->name ?? '')
            ->addColumn('meeting_status', function ($row) {
                return '<span class="badge badge-danger">Hot</span>';
            })
            ->addColumn('actions', function ($row) {
                $lead = $row->lead;
                $editUrl = route('leads.form', $lead->id);
                $btnId = 'hotActionsDropdown' . $lead->id;

                $html = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i>';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-right">';
                $html .= '    <a class="dropdown-item" href="' . e($editUrl) . '"><i class="bi bi-pencil-square mr-2"></i> View</a>';

                $activityUrl = route('leads.activity.logs', $lead->id);
                $html .= '    <button type="button" class="dropdown-item btn-activity-log" data-url="' . e($activityUrl) . '"><i class="bi bi-list-check mr-2"></i> Activity Log</button>';

                if ($lead->quotation) {
                    $quoteUrl = route('quotations.show', $lead->quotation->id);
                    $html .= '  <a class="dropdown-item" href="' . e($quoteUrl) . '"><i class="bi bi-file-earmark-text mr-2"></i> View Quotation</a>';

                    $logUrl = route('quotations.logs', $lead->quotation->id);
                    $html .= '  <button type="button" class="dropdown-item btn-quotation-log" data-url="' . e($logUrl) . '"><i class="bi bi-clock-history mr-2"></i> Quotation Log</button>';
                }

                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['meeting_status', 'actions'])
            ->make(true);
    }

    public function myDealList(Request $request)
    {
        // Trigger auto-trash if needed (non-blocking)
        AutoTrashService::triggerIfNeeded();
        
        $user = $request->user();

        $claims = LeadClaim::whereNull('released_at')
            ->with(['lead.industry', 'lead.segment', 'lead.quotation', 'sales']);

        if ($user->role?->code === 'sales') {
            $claims->where('sales_id', $user->id);
        }

        $claims->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::DEAL));

        // Apply date filtering if provided
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $claims->whereHas('lead.quotation', function ($q) use ($request) {
                $q->firstTermPaidBetween($request->start_date, $request->end_date);
            });
        }

        return DataTables::of($claims)
            ->addColumn('claimed_at', fn($row) => $row->claimed_at)
            ->addColumn('lead_name', fn($row) => $row->lead->name ?? '')
            ->addColumn('industry_name', fn($row) => $row->lead->industry->name ?? null)
            ->addColumn('other_industry', fn($row) => $row->lead->other_industry ?? null)
            ->addColumn('segment_name', fn($row) => $row->lead->segment->name ?? '')
            ->addColumn('meeting_status', function ($row) {
                return '<span class="badge badge-success">Deal</span>';
            })
            ->addColumn('actions', function ($row) {
                $lead = $row->lead;
                $editUrl = route('leads.form', $lead->id);
                $btnId = 'dealActionsDropdown' . $lead->id;

                $html = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i>';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-menu-right">';
                $html .= '    <a class="dropdown-item" href="' . e($editUrl) . '"><i class="bi bi-pencil-square mr-2"></i> View</a>';

                $activityUrl = route('leads.activity.logs', $lead->id);
                $html .= '    <button type="button" class="dropdown-item btn-activity-log" data-url="' . e($activityUrl) . '"><i class="bi bi-list-check mr-2"></i> Activity Log</button>';

                if ($lead->quotation) {
                    $quoteUrl = route('quotations.show', $lead->quotation->id);
                    $html .= '  <a class="dropdown-item" href="' . e($quoteUrl) . '"><i class="bi bi-file-earmark-text mr-2"></i> View Quotation</a>';

                    $logUrl = route('quotations.logs', $lead->quotation->id);
                    $html .= '  <button type="button" class="dropdown-item btn-quotation-log" data-url="' . e($logUrl) . '"><i class="bi bi-clock-history mr-2"></i> Quotation Log</button>';
                }

                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['meeting_status', 'actions'])
            ->make(true);
    }
}
