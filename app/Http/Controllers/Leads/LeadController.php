<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Http\Classes\ActivityLogger;
use App\Services\AutoTrashService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Leads\{Lead, LeadActivityList, LeadClaim, LeadStatus, LeadStatusLog, LeadSource, LeadSegment};
// LeadPicExtension
use App\Models\Masters\{Agent, Branch, Region, Product, Province, CustomerType, Industry};
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function available(Request $request)
    {
        $regions  = Region::with('province:id,name')
            ->get(['id', 'name', 'province_id', 'branch_id']);
        // return full branch list (sales_director should be able to assign any branch)
        $branches = Branch::orderBy('name')->get();

        $leadSources = LeadSource::orderBy('name')->get();

        // If request comes from API (route starting with /api/) or expects JSON, return JSON
        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'branches' => $branches,
                'regions' => $regions
            ]);
        }

        return view('pages.leads.available', compact(
            'branches',
            'regions',
            'leadSources'
        ));
    }

    public function availableList(Request $request)
    {
        $user = $request->user();

        $leads = Lead::with([
            'region',
            'region.branch',
            'branch',
            'source',
            'segment',
            'status',
            'industry',
            'quotation',
        ])
            ->where('status_id', LeadStatus::PUBLISHED);

        // Restrict available leads to user's branch unless user is super_admin or sales_director
        if (!in_array($user->role?->code, ['super_admin', 'sales_director'])) {
            $leads->where(function ($q) use ($user) {
                $q->where(function ($qq) {
                        $qq->whereNull('region_id')->whereNull('branch_id');
                    })
                    ->orWhere('branch_id', $user->branch_id)
                    ->orWhereHas('region', function ($q) use ($user) {
                        $q->where('branch_id', $user->branch_id);
                    });
            });
        }

        if ($request->filled('branch_id')) {
            $leads->whereHas('region.branch', function ($q) use ($request) {
                $q->where('id', $request->branch_id);
            });
        }

        if ($request->filled('region_id')) {
            $leads->where('region_id', $request->region_id);
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $leads->whereDate('published_at', '>=', $request->start_date)
                    ->whereDate('published_at', '<=', $request->end_date);
            } elseif ($request->filled('start_date')) {
                $leads->whereDate('published_at', '>=', $request->start_date);
            } else {
                $leads->whereDate('published_at', '<=', $request->end_date);
            }
        }

        // Source filter
        if ($request->filled('source_id')) {
            $source = $request->source_id;
            is_array($source)
                ? $leads->whereIn('source_id', $source)
                : $leads->where('source_id', $source);
        }

        if ($request->filled('industry_id')) {
            $leads->where('industry_id', $request->industry_id);
        }

        if ($request->filled('q')) {
            $term = $request->q;
            $leads->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('region', function ($qr) use ($term) {
                        $qr->where('name', 'like', "%{$term}%")
                            ->orWhereHas('branch', function ($qb) use ($term) {
                                $qb->where('name', 'like', "%{$term}%");
                            });
                    })
                    ->orWhereHas('source', function ($qs) use ($term) {
                        $qs->where('name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('segment', function ($qseg) use ($term) {
                        $qseg->where('name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('industry', function ($qind) use ($term) {
                        $qind->where('name', 'like', "%{$term}%");
                    });
            });
        }

        $leads->orderByDesc('published_at');

        return DataTables::of($leads)
            ->addColumn('region_name', fn($row) => $row->region->name ?? '')
            ->addColumn('branch_name', function ($row) {
                if (!empty($row->branch_id) && $row->branch) {
                    return $row->branch->name;
                }

                // return $row->region->branch->name ?? 'Unassigned Branch';
                return 'Unassigned Branch';
            })
            // ->addColumn('branch_name', fn($row) => $row->region->branch->name ?? '')
            ->addColumn('source_name', fn($row) => $row->source->name ?? '')
            // Fallback ke customer_type kalau segment belum di-set
            ->addColumn('segment_name', fn($row) => $row->segment->name ?? $row->customer_type ?? 'Not Set')
            ->addColumn('industry_name', fn($row) => $row->industry->name ?? 'Not Set') // ✅ INI YANG KAMU MAU
            ->addColumn('status_name', fn($row) => $row->status->name ?? '')
            ->addColumn('data_status', function ($row) {
                [$passed, $label] = $this->evaluateLeadDataCompleteness($row);

                return $passed . '/6';
            })
            ->addColumn('data_validation', function ($row) {
                [$passed, $label] = $this->evaluateLeadDataCompleteness($row);

                return $label;
            })
            ->addColumn('published_at', fn($row) => $row->published_at)
            ->addColumn('actions', function ($row) use ($user) {

                $editUrl  = route('leads.form', $row->id);
                $claimUrl = route('leads.claim', $row->id);

                $html  = '<a class="flex items-center gap-1 text-[#1E1E1E]! px-3! py-1! border border-[#D9D9D9] rounded-lg" href="' . e($editUrl) . '">'
                    . view('components.icon.detail')->render() .
                    ' View </a>';

                [$passed, $dataValidation] = $this->evaluateLeadDataCompleteness($row);

                $canShowClaim = true;

                if ($user && $user->role?->code === 'sales' && $dataValidation !== 'Complete') {
                    $canShowClaim = false;
                }

                if ($canShowClaim && auth()->user()->role_id == 2 && $row->branch_id == auth()->user()->branch_id) {
                    $html .= '<a class="text-white bg-[#115640] px-3 py-1 rounded-lg font-medium claim-lead flex items-center gap-1 justify-start" href="' . e($claimUrl) . '">
                                <i class="bi bi-check-circle"></i> Claim
                            </a>';
                }

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
                'agent',
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
        // branches list for Sales Director assignment (allow selecting any branch)
        $branches = Branch::orderBy('name')->get();
        $agents = $this->leadFormAgents(auth()->user());
        $agentOptions = $this->leadFormAgentOptions($agents);

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
                'agents' => $agentOptions,
                'meetings' => $meetings,
                'quotation' => $quotation,
                'order' => $order,
            ]);
        }

        return $this->render('pages.leads.form', compact('form_data', 'sources', 'segments', 'customerTypes', 'industries', 'jabatans', 'regions', 'products', 'provinces', 'meetings', 'quotation', 'order', 'branches', 'agents', 'agentOptions'));
    }

    public function save(Request $request, $id = null)
    {
        try {
            $user    = $request->user();
            $isSales = $user->role->code === 'sales';
            // Allow both Sales Director and Super Admin to perform branch assignment
            $isSalesDirector = in_array($user->role->code, ['sales_director', 'super_admin']);
            $isMyForm = $request->routeIs('leads.my.save');
            $agentRule = [
                'nullable',
                Rule::exists('ref_agents', 'id')
                    ->where('branch_id', $user->branch_id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ];

            // 1. Build validation rules
            $segmentRule = $isSales && !$id
                ? 'required|integer|exists:lead_segments,id'
                : 'nullable|integer|exists:lead_segments,id';
            $rules = [
                'source_id'   => 'required',
                'agent_id'    => $agentRule,
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
                'assignment' => 'nullable|in:published,cold',
                'assignment_branch' => 'nullable|exists:ref_branches,id',
            ];

            // If submitting multiple leads at once, validate the arrays
            if (is_array($request->input('source_id'))) {
                $rules = [
                    'source_id.*'  => 'required',
                    'agent_id.*'   => $agentRule,
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
                    'assignment.*' => 'nullable|in:published,cold',
                    'assignment_branch.*' => 'nullable|exists:ref_branches,id',
                ];
            }

            $request->validate($rules);

            if (! $id) {
                if (is_array($request->input('phone'))) {
                    $phones = collect($request->input('phone', []))
                        ->filter(function ($phone, $index) use ($request, $user) {
                            return ! $this->selectedLeadAgent($request->input("agent_id.{$index}"), $user)
                                && $phone !== null
                                && $phone !== '';
                        })
                        ->values();

                    if ($phones->isNotEmpty() && Lead::whereIn('phone', $phones->all())->exists()) {
                        throw new \Exception('Nomor Telepon Tersebut Sudah Ada Di Leads');
                    }
                } elseif (! $this->selectedLeadAgent($request->agent_id, $user) && Lead::where('phone', $request->phone)->exists()) {
                    throw new \Exception('Nomor Telepon Tersebut Sudah Ada Di Leads');
                }
            }

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
                    $branchId = $user->branch_id;
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
                    // determine status with optional Sales Director override
                    $assignment = $request->assignment[$i] ?? null;
                    if ($isSalesDirector && $assignment) {
                        $lead->status_id = $assignment === 'published' ? LeadStatus::PUBLISHED : LeadStatus::COLD;
                    } else {
                        $lead->status_id = $isMyForm ? LeadStatus::COLD : ($isSales ? LeadStatus::COLD : LeadStatus::PUBLISHED);
                    }

                    // Sales Director can assign branch directly (JKT/MKS/SBY)
                    $assignedBranch = $request->assignment_branch[$i] ?? null;
                    if ($isSalesDirector && $assignedBranch) {
                        $lead->branch_id = $assignedBranch;
                        // clear region/province when assigning by branch
                        $lead->region_id = null;
                        $lead->province = null;
                        // when assigning to a branch, keep the lead available/published
                        $lead->status_id = LeadStatus::PUBLISHED;
                    }
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

                    if ($agent = $this->selectedLeadAgent($request->agent_id[$i] ?? null, $user)) {
                        $this->applyAgentToLead($lead, $agent);
                    } else {
                        $lead->agent_id = null;
                    }

                    // Jika segment_id belum diisi tapi Customer Type ada,
                    // coba mapping ke master lead_segments berdasarkan nama yang sama.
                    if (! $lead->segment_id && $lead->customer_type) {
                        $mappedSegmentId = DB::table('lead_segments')
                            ->where('name', $lead->customer_type)
                            ->value('id');
                        if ($mappedSegmentId) {
                            $lead->segment_id = $mappedSegmentId;
                        }
                    }

                    if ($isSales || $isMyForm) {
                        $lead->first_sales_id = $user->id;
                    }
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

                    if ($isSales || $isMyForm) {
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
                Log::info('Request data:', $request->all());

                return $this->setJsonResponse('Leads saved successfully', ['ids' => $ids]);
            }

            // 3. Single-lead create or update
            $lead   = $id ? Lead::findOrFail($id) : new Lead();
            $before = $id ? $lead->toArray() : null;

            $rawRegion = $request->region_id === 'ALL'
                ? null
                : $request->region_id;
            $region = $rawRegion ? Region::with('province')->find($rawRegion) : null;
            $branchId = $id ? $lead->branch_id : $user->branch_id;
            $provinceName = $region?->province?->name;

            $lead->source_id    = $request->source_id;
            $lead->segment_id   = $request->segment_id;
            $lead->region_id    = $rawRegion;
            $lead->branch_id    = $branchId;
            $lead->province     = $rawRegion ? $provinceName : null;
            $assignedBranchSingle = $request->assignment_branch ?? null;

            if (! $id) {
                // default status
                $defaultStatus = $isMyForm ? LeadStatus::COLD : ($isSales ? LeadStatus::COLD : LeadStatus::PUBLISHED);
                // allow Sales Director to override via assignment field
                $assignmentSingle = $request->assignment ?? null;
                if ($isSalesDirector && $assignmentSingle) {
                    $lead->status_id = $assignmentSingle === 'published' ? LeadStatus::PUBLISHED : LeadStatus::COLD;
                } else {
                    $lead->status_id = $defaultStatus;
                }
            }

            $assignedBranchSingle = $request->assignment_branch ?? null;
            if ($isSalesDirector && $assignedBranchSingle) {
                $lead->branch_id = $assignedBranchSingle;
                $lead->region_id = $rawRegion;
                $lead->province = $provinceName;

                // status dipaksa published hanya saat create
                if (! $id) {
                    $lead->status_id = LeadStatus::PUBLISHED;
                }
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

            if ($agent = $this->selectedLeadAgent($request->agent_id, $user)) {
                $this->applyAgentToLead($lead, $agent);
            } else {
                $lead->agent_id = null;
            }

            // Mapping otomatis segment berdasarkan Customer Type jika segment belum di-set.
            if (! $lead->segment_id && $lead->customer_type) {
                $mappedSegmentId = DB::table('lead_segments')
                    ->where('name', $lead->customer_type)
                    ->value('id');
                if ($mappedSegmentId) {
                    $lead->segment_id = $mappedSegmentId;
                }
            }
            $lead->published_at = $id ? $lead->published_at : now();

            if (! $id && ($isSales || $isMyForm)) {
                $lead->first_sales_id = $user->id;
            }
            // If updating existing lead and Sales Director assigned branch, apply branch only
            // (do NOT change status or existing claims/owners)
            if ($id && $isSalesDirector && $assignedBranchSingle) {
                $lead->branch_id = $assignedBranchSingle;
                $lead->region_id = null;
                $lead->province = null;
            }
            // Sales Director should not be set as first_sales by assignment
            $lead->save();

            if (! $id && ($isSales || $isMyForm)) {
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
            Log::error('Lead Save Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all() // Log the request data
            ]);

            $message = $e->getMessage() === 'Nomor Telepon Tersebut Sudah Ada Di Leads'
                ? $e->getMessage()
                : 'Error saving lead: ' . $e->getMessage();

            $statusCode = $e->getMessage() === 'Nomor Telepon Tersebut Sudah Ada Di Leads' ? 422 : 500;

            return response()->json([
                'error' => true,
                'message' => $message
            ], $statusCode);
        }
    }

    private function leadFormAgents($user)
    {
        if (! $user?->branch_id) {
            return collect();
        }

        return Agent::with(['region.province:id,name', 'source:id,name', 'customerType:id,name', 'jabatan:id,name'])
            ->where('branch_id', $user->branch_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function leadFormAgentOptions($agents)
    {
        return $agents->mapWithKeys(function (Agent $agent) {
            [$title, $name] = $this->splitAgentName($agent->name);
            $location = trim(($agent->region?->name ?? '-') . ', ' . ($agent->province ?? '-'));

            return [
                $agent->id => [
                    'id' => $agent->id,
                    'title' => $title,
                    'name' => $name,
                    'display_name' => $agent->name,
                    'phone' => $agent->phone,
                    'email' => $agent->email,
                    'jabatan_id' => $agent->jabatan_id,
                    'company' => $agent->company_name,
                    'company_address' => $agent->company_address,
                    'source_id' => $agent->source_id,
                    'customer_type' => $agent->customerType?->name,
                    'region_id' => $agent->region_id,
                    'region_name' => $agent->region?->name,
                    'province' => $agent->province,
                    'branch_id' => $agent->branch_id,
                    'label' => implode(' | ', [
                        $agent->name ?: '-',
                        $agent->phone ?: '-',
                        $agent->email ?: '-',
                        $location,
                    ]),
                ],
            ];
        });
    }

    private function selectedLeadAgent($agentId, $user): ?Agent
    {
        if (empty($agentId) || ! $user?->branch_id) {
            return null;
        }

        return Agent::with(['customerType:id,name'])
            ->whereKey($agentId)
            ->where('branch_id', $user->branch_id)
            ->where('is_active', true)
            ->first();
    }

    private function applyAgentToLead(Lead $lead, Agent $agent): void
    {
        [$title, $name] = $this->splitAgentName($agent->name);

        $lead->agent_id = $agent->id;
        $lead->source_id = $agent->source_id;
        $lead->region_id = $agent->region_id;
        $lead->branch_id = $agent->branch_id;
        $lead->province = $agent->province;
        $lead->company = $agent->company_name;
        $lead->company_address = $agent->company_address;
        $lead->customer_type = $agent->customerType?->name;
        $lead->name = trim($title . ' ' . $name);
        $lead->phone = $agent->phone;
        $lead->email = $agent->email;
        $lead->jabatan_id = $agent->jabatan_id;
    }

    private function splitAgentName(?string $name): array
    {
        $name = trim((string) $name);

        foreach (['Mrs', 'Mr'] as $title) {
            if (str_starts_with($name, $title . ' ')) {
                return [$title, trim(substr($name, strlen($title) + 1))];
            }
        }

        return ['Mr', $name];
    }

    protected function evaluateLeadDataCompleteness($lead): array
    {
        $checks = [
            // Primary contact info: name + at least one contact
            'primary_contact' => !empty($lead->name)
                && (!empty($lead->phone) || !empty($lead->email)),

            // Company details: company, address, city (region) and province
            'company_details' => !empty($lead->company)
                && !empty($lead->company_address)
                && !empty($lead->region_id)
                && !empty($lead->province),

            // Classification: source + customer type + industry (existing or other)
            'classification' => !empty($lead->source_id)
                && !empty($lead->customer_type)
                && (!empty($lead->industry_id) || !empty($lead->other_industry)),

            // Context: at least one of the context text fields filled
            'context' => !empty($lead->contact_reason)
                || !empty($lead->competitor_offer)
                || !empty($lead->business_reason)
                || !empty($lead->industry_remark),

            // Requirement: core need + tonase (capacity)
            'requirement' => !empty($lead->needs)
                && $lead->tonase !== null && $lead->tonase !== '',

            // Factory planning / extra detail: any of these filled
            'factory_plan' => !empty($lead->factory_city_id)
                || !empty($lead->factory_province)
                || !empty($lead->factory_industry_id)
                || !empty($lead->factory_other_industry)
                || !empty($lead->tonage_remark),
        ];

        $passed = count(array_filter($checks));

        if ($passed >= 5) {
            $label = 'Complete';
        } elseif ($passed === 4) {
            $label = 'Moderate';
        } else {
            $label = 'Incomplete';
        }

        return [$passed, $label];
    }

    public function claim($id)
    {
        $lead = Lead::findOrFail($id);

        $user = request()->user();

        // Prevent users from claiming leads assigned to other branches
        if ($lead->branch_id && $user && !in_array($user->role?->code, ['super_admin', 'sales_director'])) {
            if (! $user->branch_id || $lead->branch_id != $user->branch_id) {
                return $this->setJsonResponse('You are not allowed to claim leads from other branches', [], 403);
            }
        }

        // Server-side guard: sales can only claim leads with complete lead data (no quotation dependency)
        if ($user && $user->role?->code === 'sales') {
            [$passed, $label] = $this->evaluateLeadDataCompleteness($lead);

            if ($label !== 'Complete') {
                return $this->setJsonResponse('Lead data must be complete before claiming', [], 422);
            }
        }

        LeadClaim::create([
            'lead_id'    => $lead->id,
            'sales_id'   => $user?->id,
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

    public function my(Request $request)
    {
        AutoTrashService::triggerIfNeeded();

        $user = $request->user();
        $selfScopedRoles = ['sales', 'branch_manager', 'sales_director'];

        $claims = LeadClaim::whereNull('released_at')
            ->with('lead');

        if (in_array($user->role?->code, $selfScopedRoles, true)) {
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

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
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

        return view('pages.leads.my', [
            'leadCounts' => [
                'all'  => $all,
                'cold' => $counts[LeadStatus::COLD] ?? 0,
                'warm' => $counts[LeadStatus::WARM] ?? 0,
                'hot'  => $counts[LeadStatus::HOT] ?? 0,
                'deal' => $counts[LeadStatus::DEAL] ?? 0,
            ],
            'activities' => LeadActivityList::all(),
            'leadSources' => LeadSource::orderBy('name')->get()
        ]);
    }


    public function myCounts(Request $request)
    {
        // Trigger auto-trash if needed (non-blocking)
        AutoTrashService::triggerIfNeeded();
        $user = $request->user();
        $selfScopedRoles = ['sales', 'branch_manager', 'sales_director'];

        $claims = LeadClaim::whereNull('released_at');

        if (in_array($user->role?->code, $selfScopedRoles, true)) {
            $claims->where('sales_id', $user->id);
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

        return response()->json([
            'leadCounts' => $this->buildManageLeadCounts($request),
        ]);
    }

    public function manageSummary(Request $request)
    {
        if ($request->user()->role?->code === 'sales') {
            abort(403);
        }

        return response()->json($this->buildManageSummary($request));
    }

    public function manage(Request $request)
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

        $leadCounts = $this->buildManageLeadCounts($request);

        $activities = \App\Models\Leads\LeadActivityList::all();

        $salesQuery = User::query()
            ->whereHas('role', fn($q) => $q->where('code', 'sales'));

        $salesUsers = $salesQuery->orderBy('name')->get(['id', 'name', 'branch_id']);


        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'branches' => $branches,
                'regions' => $regions,
                'leadCounts' => $leadCounts,
                'activities' => $activities,
                'user' => $user,
                'userBranchId' => $userBranchId,
            ]);
        }

        return view('pages.leads.manage', compact('branches', 'regions', 'leadCounts', 'activities', 'user', 'userBranchId', 'salesUsers'));
    }

    private function resolveManageCountFilters(Request $request): array
    {
        $user = $request->user();
        // variabel restrictedRoles kalau mao ketatin ke role lainnya, tinggal tambahin aja
        $restrictedRoles = ['branch_manager'];

        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        if (! $branchId && $user?->branch_id && in_array($user->role?->code, $restrictedRoles)) {
            $branchId = (int) $user->branch_id;
        }

        $salesId = $request->filled('sales_id') ? (int) $request->input('sales_id') : null;
        $regionId = $request->filled('region_id') ? (int) $request->input('region_id') : null;
        $statusId = $request->filled('status_id') ? (int) $request->input('status_id') : null;
        $search = trim((string) $request->input('search', ''));

        $startDate = trim((string) $request->input('start_date', ''));
        $endDate = trim((string) $request->input('end_date', ''));

        $startDate = $startDate !== '' ? $startDate : null;
        $endDate = $endDate !== '' ? $endDate : null;

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'branch_id' => $branchId,
            'sales_id' => $salesId,
            'region_id' => $regionId,
            'status_id' => $statusId,
            'search' => $search,
            'period_start' => $startDate ? $startDate . ' 00:00:00' : null,
            'period_end' => $endDate ? $endDate . ' 23:59:59' : null,
        ];
    }

    private function buildManageActiveClaimsQuery(array $filters, array $with = [], ?string $stage = null)
    {
        $activeClaims = LeadClaim::query()
            ->with($with)
            ->join('leads', 'lead_claims.lead_id', '=', 'leads.id')
            ->join('lead_statuses', 'leads.status_id', '=', 'lead_statuses.id')
            ->whereIn('lead_statuses.id', [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL,
            ])
            ->whereNull('lead_claims.trash_note')
            ->whereNull('lead_claims.released_at')
            ->whereNull('lead_claims.deleted_at')
            ->when($filters['period_start'] && $filters['period_end'], function ($q) use ($filters) {
                $q->whereBetween('lead_claims.claimed_at', [$filters['period_start'], $filters['period_end']]);
            })
            ->when($filters['period_start'] && ! $filters['period_end'], function ($q) use ($filters) {
                $q->where('lead_claims.claimed_at', '>=', $filters['period_start']);
            })
            ->when(! $filters['period_start'] && $filters['period_end'], function ($q) use ($filters) {
                $q->where('lead_claims.claimed_at', '<=', $filters['period_end']);
            })
            ->when(! empty($filters['branch_id']) && empty($filters['sales_id']), function ($q) use ($filters) {
                $q->join('users as sales', 'lead_claims.sales_id', '=', 'sales.id')
                    ->where('sales.branch_id', $filters['branch_id']);
            })
            ->when(! empty($filters['sales_id']), function ($q) use ($filters) {
                $q->where('lead_claims.sales_id', $filters['sales_id']);
            })
            ->when(! empty($filters['region_id']), function ($q) use ($filters) {
                $q->where('leads.region_id', $filters['region_id']);
            })
            ->when(! empty($filters['status_id']), function ($q) use ($filters) {
                $q->where('leads.status_id', $filters['status_id']);
            })
            ->when(in_array($stage, ['cold', 'warm', 'hot', 'deal'], true), function ($q) use ($stage) {
                $statusMap = [
                    'cold' => LeadStatus::COLD,
                    'warm' => LeadStatus::WARM,
                    'hot'  => LeadStatus::HOT,
                    'deal' => LeadStatus::DEAL,
                ];

                $q->where('leads.status_id', $statusMap[$stage]);
            })
            ->select('lead_claims.*');

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $activeClaims->where(function ($q) use ($search) {
                $q->where('leads.name', 'like', "%{$search}%")
                    ->orWhere('leads.phone', 'like', "%{$search}%")
                    ->orWhere('leads.needs', 'like', "%{$search}%")
                    ->orWhere('leads.province', 'like', "%{$search}%")
                    ->orWhere('leads.customer_type', 'like', "%{$search}%")
                    ->orWhereHas('lead.region', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('lead.region.regional', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('lead.source', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('sales', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('lead.quotation', fn($sq) => $sq->where('quotation_no', 'like', "%{$search}%"))
                    ->orWhereHas('lead.quotation.proformas.invoice', fn($sq) => $sq->where('invoice_no', 'like', "%{$search}%"));
            });
        }

        return $activeClaims;
    }

    private function buildManageActiveClaimsCountQuery(array $filters)
    {
        return $this->buildManageActiveClaimsQuery($filters, ['lead']);
    }

    private function buildManageLeadCountsFromFilters(array $filters): array
    {
        $activeClaimRows = $this->buildManageActiveClaimsCountQuery($filters)->get();
        $uniqueLeads = $activeClaimRows->pluck('lead')->filter()->unique('id');
        $counts = $uniqueLeads->groupBy('status_id')->map->count();

        $cold = (int) ($counts[LeadStatus::COLD] ?? 0);
        $warm = (int) ($counts[LeadStatus::WARM] ?? 0);
        $hot = (int) ($counts[LeadStatus::HOT] ?? 0);
        $deal = (int) ($counts[LeadStatus::DEAL] ?? 0);

        return [
            'all' => $cold + $warm + $hot + $deal,
            'cold' => $cold,
            'warm' => $warm,
            'hot' => $hot,
            'deal' => $deal,
        ];
    }

    private function buildManageLeadCounts(Request $request): array
    {
        $filters = $this->resolveManageCountFilters($request);

        return $this->buildManageLeadCountsFromFilters($filters);
    }

    private function buildManageSummary(Request $request): array
    {
        $filters = $this->resolveManageCountFilters($request);
        $leadCounts = $this->buildManageLeadCountsFromFilters($filters);
        $initiationCodes = ['A01', 'A02', 'A03', 'A04'];

        $coldBase = $this->buildManageActiveClaimsQuery($filters)
            ->where('leads.status_id', LeadStatus::COLD);
        $coldInitiation = (clone $coldBase)
            ->whereHas('lead.activityLogs.activity', fn($q) => $q->whereIn('code', $initiationCodes))
            ->whereDoesntHave('lead.meetings')
            ->count();
        $coldRaw = (clone $coldBase)
            ->whereDoesntHave('lead.activityLogs.activity', fn($q) => $q->whereIn('code', $initiationCodes))
            ->whereDoesntHave('lead.meetings')
            ->count();
        $coldPending = (clone $coldBase)
            ->whereHas('lead.meetings', fn($q) => $q->where('is_online', 0)
                ->whereHas('expense', fn($sq) => $sq->where('status', 'submitted')))
            ->count();
        $coldRejected = (clone $coldBase)
            ->whereHas('lead.meetings', fn($q) => $q->where('is_online', 0)
                ->whereHas('expense', fn($sq) => $sq->where('status', 'rejected')))
            ->count();
        $coldMeetOnline = (clone $coldBase)
            ->whereHas('lead.meetings', fn($q) => $q->where('scheduled_end_at', '>', now())
                ->where('is_online', 1))
            ->count();
        $coldMeetOffline = (clone $coldBase)
            ->whereHas('lead.meetings', fn($q) => $q->where('scheduled_end_at', '>', now())
                ->where('is_online', 0))
            ->count();

        $warmBase = $this->buildManageActiveClaimsQuery($filters)
            ->where('leads.status_id', LeadStatus::WARM);
        $warmPending = (clone $warmBase)
            ->whereHas('lead.quotation', fn($q) => $q->whereIn('status', ['review', 'pending_finance']))
            ->count();
        $warmRejected = (clone $warmBase)
            ->whereHas('lead.quotation', fn($q) => $q->where('status', 'rejected'))
            ->count();
        $warmNoQuotation = (clone $warmBase)
            ->whereDoesntHave('lead.quotation')
            ->count();
        $warmPublished = (clone $warmBase)
            ->whereHas('lead.quotation', fn($q) => $q->where('status', 'published'))
            ->count();

        $hotBase = $this->buildManageActiveClaimsQuery($filters)
            ->where('leads.status_id', LeadStatus::HOT);
        $hotClaims = (clone $hotBase)->with([
            'lead.statusLogs' => fn($q) => $q->where('status_id', LeadStatus::HOT)->orderByDesc('created_at'),
        ])->get();

        $hotExpiringSoon = 0;
        $hotExpiringLater = 0;

        foreach ($hotClaims as $claim) {
            $hotLog = $claim->lead?->statusLogs?->first();
            $claimedAt = $claim->claimed_at ? \Illuminate\Support\Carbon::parse($claim->claimed_at) : null;
            $hotAt = $hotLog?->created_at ? \Illuminate\Support\Carbon::parse($hotLog->created_at) : null;
            $baseAt = $claimedAt ?? $hotAt;

            if (! $baseAt) {
                continue;
            }

            $daysLeft = \Illuminate\Support\Carbon::now()->startOfDay()->diffInDays(
                $baseAt->copy()->startOfDay()->addDays(30),
                false
            );

            if ($daysLeft < 0) {
                $daysLeft = 0;
            }

            if ($daysLeft <= 7) {
                $hotExpiringSoon++;
            } else {
                $hotExpiringLater++;
            }
        }

        return [
            'leadCounts' => $leadCounts,
            'cold' => [
                'total' => $leadCounts['cold'],
                'initiation' => $coldInitiation,
                'raw' => $coldRaw,
                'approval_status' => $coldPending + $coldRejected,
                'pending' => $coldPending,
                'rejected' => $coldRejected,
                'meet_online' => $coldMeetOnline,
                'meet_offline' => $coldMeetOffline,
                'meeting_scheduled' => $coldMeetOnline + $coldMeetOffline,
            ],
            'warm' => [
                'total' => $leadCounts['warm'],
                'approval_status' => $warmPending + $warmRejected,
                'pending' => $warmPending,
                'rejected' => $warmRejected,
                'no_quotation' => $warmNoQuotation,
                'quotation_published' => $warmPublished,
            ],
            'hot' => [
                'total' => $leadCounts['hot'],
                'expiring_7_days' => $hotExpiringSoon,
                'expiring_8_plus_days' => $hotExpiringLater,
            ],
            'deal' => [
                'total' => $leadCounts['deal'],
            ],
        ];
    }

    public function manageList(Request $request)
    {
        if ($request->user()->role?->code === 'sales') {
            abort(403);
        }

        // Trigger auto-trash if needed (non-blocking)
        AutoTrashService::triggerIfNeeded();

        $filters = $this->resolveManageCountFilters($request);
        $stage = $request->filled('stage') ? strtolower($request->stage) : null;
        $activeClaims = $this->buildManageActiveClaimsQuery($filters, [
            'sales',
            'lead.branch',
            'lead.firstSales',
            'lead.region.branch',
            'lead.region.regional',
            'lead.source',
            'lead.industry',
            'lead.segment',
            'lead.status',
            'lead.quotation',
            'lead.quotation.createdBy',
            'lead.quotation.proformas.invoice',
            'lead.activityLogs.activity',
            'lead.meetings',
        ], $stage);

        // =========================
        // PAGINATION
        // =========================

        $perPage = max(1, (int) $request->get('per_page', 10));

        $paginated = $activeClaims
            ->orderByDesc('lead_claims.claimed_at')
            ->orderByDesc('lead_claims.id')
            ->paginate($perPage);

        $role = $request->user()->role?->code;

        $cityIds = $paginated->getCollection()
            ->pluck('lead.factory_city_id')
            ->filter()
            ->unique()
            ->values();
        $cities = collect();
        $regionals = collect();
        $provinces = collect();

        if ($cityIds->isNotEmpty()) {
            $cities = DB::table('ref_regions')
                ->whereIn('id', $cityIds)
                ->select('id', 'name', 'regional_id', 'province_id')
                ->get()
                ->keyBy('id');

            $regionalIds = $cities->pluck('regional_id')->filter()->unique()->values();
            $provinceIds = $cities->pluck('province_id')->filter()->unique()->values();

            if ($regionalIds->isNotEmpty()) {
                $regionals = DB::table('ref_regionals')
                    ->whereIn('id', $regionalIds)
                    ->select('id', 'name')
                    ->get()
                    ->keyBy('id');
            }

            if ($provinceIds->isNotEmpty()) {
                $provinces = DB::table('ref_provinces')
                    ->whereIn('id', $provinceIds)
                    ->select('id', 'name')
                    ->get()
                    ->keyBy('id');
            }
        }

        // =========================
        // TRANSFORM DATA
        // =========================

        $paginated->getCollection()->transform(function ($claim) use ($role, $cities, $regionals, $provinces) {
            $lead = $claim->lead;       

            if (! $lead) {
                return null;
            }

            $city = $lead ? $cities->get($lead->factory_city_id) : null;

            $latestActivity = $lead->activityLogs
                ->sortByDesc(function ($activity) {
                    return strtotime($activity->logged_at) . str_pad($activity->id, 10, '0', STR_PAD_LEFT);
                })
                ->first();

            $meeting = $lead->meetings->first();
            $quote = $lead->quotation;

            $salesName = $claim?->sales?->name
                ?? $lead->firstSales?->name
                ?? $quote?->createdBy?->name
                ?? '-';

            // ================= ACTIONS =================
            $editUrl   = route('leads.manage.form', $lead->id);
            $quoteUrl  = $quote ? route('quotations.show', $quote->id) : null;
            $activityUrl = route('leads.activity.logs', $lead->id);

            $html  = '<div class="dropdown">';
            $html .= '<button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! text-[#1E1E1E]! hover:text-white! dropdown-toggle" type="button" data-toggle="dropdown">';
            $html .= '<i class="bi bi-three-dots"></i>';
            $html .= '</button>';
            $html .= '<div class="dropdown-menu dropdown-menu-right rounded-lg!">';
            $html .= '<a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($editUrl) . '"> ' . view('components.icon.detail')->render() . 'View Lead Detail</a>';
            $html .= '<button type="button" class="dropdown-item btn-activity-log cursor-pointer flex! items-center! gap-2! text-[#1E1E1E]!" data-url="' . e($activityUrl) . '">
            ' . view('components.icon.log')->render() . '
            View / Add Activity Log</button>';

            if (in_array($role, ['branch_manager', 'sales_director', 'sales', 'finance']) && $quote) {
                $html .= '<a class="dropdown-item flex! items-center! gap-2!" href="' . e($quoteUrl) . '">' . view('components.icon.view-quotation')->render() . ' View Quotation</a>';
            }

            if ($claim && $lead->status_id === LeadStatus::COLD && ! $meeting) {
                $trashUrl = route('leads.my.cold.trash', $claim->id);
                $html .= '<button class="dropdown-item text-danger trash-lead cursor-pointer flex! items-center! gap-2! text-[#900B09]! trash-lead" data-url="' . e($trashUrl) . '">' . view('components.icon.trash')->render() . 'Move To Trash Lead</button>';
            }

            if ($claim && $lead->status_id === LeadStatus::WARM && (! $quote || $quote->status !== 'published')) {
                $trashUrl = route('leads.my.warm.trash', $claim->id);
                $html .= '<button class="dropdown-item text-danger trash-lead cursor-pointer flex! items-center! gap-2! text-[#900B09]! trash-lead" data-url="' . e($trashUrl) . '">' . view('components.icon.trash')->render() . 'Trash Lead</button>';
            }

            $html .= '</div></div>';

            // ================= RETURN ARRAY =================
            return [
                'id' => $lead->id,
                'lead_name' => $lead->name ?? '-',
                'branch_id' => $lead->branch?->id,
                'branch_name' => $lead->branch?->name ?? '-',
                'sales_name' => $salesName,
                'phone' => $lead->phone,
                'claimed_at' => $claim?->claimed_at
                    ? \Carbon\Carbon::parse($claim->claimed_at)->format('d/m/Y')
                    : '-',
                'source_name' => $lead->source->name ?? '',
                'needs' => $lead->needs,
                'existing_industries' => $lead->industry->name ?? '-',
                'city_name' => $lead->region->name ?? 'All Regions',
                'province' => $lead->province ?? '-',
                'regional_name' => $lead->region->regional->name ?? '-',
                'customer_type' => $lead->customer_type ?? '-',
                'quotation_number' => $quote->quotation_no ?? '-',
                'quotation_price' => $quote ? number_format($quote->grand_total ?? 0, 2) : '-',
                'invoice_number' => $quote?->proformas->first()?->invoice?->invoice_no ?? '-',
                'invoice_price' => $quote?->proformas->first()?->invoice
                    ? number_format($quote->proformas->first()->invoice->amount ?? 0, 2)
                    : '-',
                'quot_created' => $lead->published_at
                    ? \Carbon\Carbon::parse($lead->published_at)->format('d/m/Y')
                    : '-',
                'quot_end_date' => $lead->updated_at
                    ? \Carbon\Carbon::parse($lead->updated_at)->format('d/m/Y')
                    : '-',
                'act_last_time' => $latestActivity
                    ? \Carbon\Carbon::parse($latestActivity->logged_at)->format('d/m/Y')
                    : '-',
                'act_status' => $latestActivity?->activity?->name ?? '-',
                'created_at' => $lead->created_at
                    ? \Carbon\Carbon::parse($lead->created_at)->format('d/m/Y')
                    : '-',
                'status_name' => $lead->status?->name ?? '-',
                'alternate_location' => [ $city ? [
                    'region_id' => $city->id,
                    'region_name' => $city->name,
                    'regional_id' => $city->regional_id,
                    'regional_name' => optional($regionals->get($city->regional_id))->name,
                    'province_id' => $city->province_id,
                    'province_name' => optional($provinces->get($city->province_id))->name,
                ] : null ],
                'actions' => $html
            ];
        });

        return response()->json([
            'data' => $paginated->items(),
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function delete(Request $request, $id)
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
        $request->validate([
            'export_mode' => 'nullable|in:selected,all_filtered',
            'lead_ids' => 'nullable|array',
            'lead_ids.*' => 'nullable|integer|distinct',
        ]);

        $selectedLeadIds = collect($request->input('lead_ids', []))
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $exportSelectedOnly = $request->input('export_mode') === 'selected' && ! empty($selectedLeadIds);

        $leads = Lead::with([
            'region',
            'region.branch',
            'source',
            'segment',
            'industry',
        ])
            ->where('status_id', LeadStatus::PUBLISHED);

        if (! in_array($user->role?->code, ['super_admin', 'sales_director'])) {
            $leads->where(function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($q) =>
                        $q->where('branch_id', $user->branch_id)
                    );
            });
        }

        if ($exportSelectedOnly) {
            $leads->whereIn('id', $selectedLeadIds);
        } else {
            if ($request->filled('branch_id')) {
                $leads->whereHas('region.branch', function ($q) use ($request) {
                    $q->where('id', $request->branch_id);
                });
            }

            if ($request->filled('region_id')) {
                $leads->where('region_id', $request->region_id);
            }

            // Date range filter (published_at) - same as availableList
            if ($request->filled('start_date') || $request->filled('end_date')) {
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $leads->whereDate('published_at', '>=', $request->start_date)
                        ->whereDate('published_at', '<=', $request->end_date);
                } elseif ($request->filled('start_date')) {
                    $leads->whereDate('published_at', '>=', $request->start_date);
                } else {
                    $leads->whereDate('published_at', '<=', $request->end_date);
                }
            }

            // Source filter
            if ($request->filled('source_id')) {
                $source = $request->source_id;
                is_array($source)
                    ? $leads->whereIn('source_id', $source)
                    : $leads->where('source_id', $source);
            }

            // Industry filter
            if ($request->filled('industry_id')) {
                $leads->where('industry_id', $request->industry_id);
            }

            // Global search (same as availableList)
            if ($request->filled('q')) {
                $term = $request->q;
                $leads->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhereHas('region', function ($qr) use ($term) {
                            $qr->where('name', 'like', "%{$term}%")
                                ->orWhereHas('branch', function ($qb) use ($term) {
                                    $qb->where('name', 'like', "%{$term}%");
                                });
                        })
                        ->orWhereHas('source', function ($qs) use ($term) {
                            $qs->where('name', 'like', "%{$term}%");
                        })
                        ->orWhereHas('segment', function ($qseg) use ($term) {
                            $qseg->where('name', 'like', "%{$term}%");
                        })
                        ->orWhereHas('industry', function ($qind) use ($term) {
                            $qind->where('name', 'like', "%{$term}%");
                        });
                });
            }
        }

        $rows   = [];
        $rows[] = [
            'Published At',
            'Name',
            'Branch',
            'Industry To Be',
            'Industry Existing',
            'Industry',
            'Product',
            'Tonage',
            'Regional',
            'Source',
            'Segment',
        ];

        foreach ($leads->orderByDesc('id')->get() as $lead) {
            $rows[] = [
                $lead->published_at,
                $lead->name,
                $lead->region->branch->name ?? '-',
                // Match "Industry To Be" column in table (industry_name)
                $lead->industry->name ?? '-',
                // "Industry Existing" - currently same data as view uses
                $lead->industry->name ?? '-',
                // "Industry" - same again (view shows industry.name)
                $lead->industry->name ?? '-',
                // Product column in view uses needs
                $lead->needs ?? '-',
                $lead->tonase ?? '-',
                $lead->region->name ?? '-',
                $lead->source->name ?? '-',
                // Same as Segment column in list: segment with fallback customer_type
                $lead->segment->name ?? $lead->customer_type ?? 'Not Set',
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

        $request->validate([
            'export_mode' => 'nullable|in:selected,all_filtered',
            'lead_ids' => 'nullable|array',
            'lead_ids.*' => 'nullable|integer|distinct',
            'stage' => 'nullable|in:all,cold,warm,hot,deal',
            'export_file_name' => 'nullable|string|max:500',
        ]);

        $selectedLeadIds = collect($request->input('lead_ids', []))
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $exportSelectedOnly = $request->input('export_mode') === 'selected' && ! empty($selectedLeadIds);
        $stage = strtolower($request->input('stage', 'all'));

        $leads = Lead::with([
            'branch',
            'region.branch',
            'region.regional',
            'source',
            'segment',
            'industry',
            'status',
            'firstSales',
            'product',
            'claims.sales',
            'activityLogs.activity',
            'quotation',
            'quotation.createdBy',
            'quotation.proformas' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'quotation.proformas.invoice'
        ]);

        // =========================
        // FILTER SECTION (selaras dengan manageList)
        // =========================

        $branchId = $request->filled('branch_id') ? $request->branch_id : null;

        if ($request->filled('stage')) {
            $stage = strtolower($request->stage);

            // echo "Filtering by stage: {$stage}\n";
            // \Log::info("Filtering by stage: {$stage}");

            \Illuminate\Support\Facades\Log::info("Filtering by stage: {$stage}");

            // $statusMap = [
            //     'cold' => LeadStatus::COLD,
            //     'warm' => LeadStatus::WARM,
            //     'hot'  => LeadStatus::HOT,
            //     'deal' => LeadStatus::DEAL,
            // ];

            $statusMap = LeadStatus::stageMap();

            if (isset($statusMap[$stage])) {
                $leads->where('status_id', $statusMap[$stage]);
            }
        }

        // By default, only export leads in these stages (cold, warm, hot, deal).
        // This prevents including 'Published' or other transient statuses unless
        // an explicit 'stage' or 'status_id' filter is provided in the request.
        if (! $request->filled('stage') && ! $request->filled('status_id')) {
            $leads->whereIn('status_id', [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL,
            ]);
        }

        if (! $branchId && $request->user()->branch_id && in_array($request->user()->role?->code, ['branch_manager', 'finance', 'accountant', 'purchasing'])) {
            $branchId = $request->user()->branch_id;
        }

        if ($branchId) {
            $leads->where(function ($q) use ($branchId) {
                $q->whereHas('region.branch', function ($subq) use ($branchId) {
                    $subq->where('id', $branchId);
                })->orWhere('branch_id', $branchId);
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

        // =========================
        // SEARCH (selaras dengan manageList)
        // =========================

        if ($request->filled('search')) {
            $search = $request->search;

            $leads->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('needs', 'like', "%{$search}%")
                    ->orWhere('customer_type', 'like', "%{$search}%")
                    ->orWhereHas('region', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('region.regional', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('source', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('claims.sales', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('quotation', fn($sq) => $sq->where('quotation_no', 'like', "%{$search}%"))
                    ->orWhereHas('quotation.proformas.invoice', fn($sq) => $sq->where('invoice_no', 'like', "%{$search}%"));
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
            'Invoice Price',
            'Stage',
        ];

        if (isset($statusMap[$stage])) {
            $leads->where('status_id', $statusMap[$stage]);
        }

        if ($exportSelectedOnly) {
            $leads->whereIn('id', $selectedLeadIds);
        } else {
            if ($request->filled('branch_id')) {
                $branchId = $request->branch_id;

                $leads->where(function ($q) use ($branchId) {
                    $q->whereHas('region.branch', function ($subq) use ($branchId) {
                        $subq->where('id', $branchId);
                    })->orWhere('branch_id', $branchId);
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

            $this->applyManageClaimedAtDateFilter($leads, $request);

            if ($request->filled('search')) {
                $search = $request->search;

                $leads->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('needs', 'like', "%{$search}%")
                        ->orWhere('customer_type', 'like', "%{$search}%")
                        ->orWhereHas('region', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('region.regional', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('source', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('claims.sales', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('quotation', fn($sq) => $sq->where('quotation_no', 'like', "%{$search}%"))
                        ->orWhereHas('quotation.proformas.invoice', fn($sq) => $sq->where('invoice_no', 'like', "%{$search}%"));
                });
            }
        }

        $stageColumns = [
            'all' => [
                ['label' => 'Lead Name', 'key' => 'lead_name'],
                ['label' => 'Branch Name', 'key' => 'branch_name'],
                ['label' => 'Sales Name', 'key' => 'sales_name'],
                ['label' => 'Telephone', 'key' => 'phone'],
                ['label' => 'Source', 'key' => 'source_name'],
                ['label' => 'Needs', 'key' => 'needs'],
                ['label' => 'Industry', 'key' => 'existing_industries'],
                ['label' => 'City', 'key' => 'city_name'],
                ['label' => 'Regional', 'key' => 'regional_name'],
                ['label' => 'Customer Type', 'key' => 'customer_type'],
                ['label' => 'ACT Last Time', 'key' => 'act_last_time'],
                ['label' => 'ACT Status', 'key' => 'act_status'],
                ['label' => 'Created At', 'key' => 'created_at'],
                ['label' => 'Claimed At', 'key' => 'claimed_at'],
                ['label' => 'Stage', 'key' => 'status_name'],
            ],
            'cold' => [
                ['label' => 'Lead Name', 'key' => 'lead_name'],
                ['label' => 'Branch Name', 'key' => 'branch_name'],
                ['label' => 'Sales Name', 'key' => 'sales_name'],
                ['label' => 'Telephone', 'key' => 'phone'],
                ['label' => 'Source', 'key' => 'source_name'],
                ['label' => 'Needs', 'key' => 'needs'],
                ['label' => 'Industry', 'key' => 'existing_industries'],
                ['label' => 'City', 'key' => 'city_name'],
                ['label' => 'Regional', 'key' => 'regional_name'],
                ['label' => 'Customer Type', 'key' => 'customer_type'],
                ['label' => 'ACT Last Time', 'key' => 'act_last_time'],
                ['label' => 'ACT Status', 'key' => 'act_status'],
                ['label' => 'Created At', 'key' => 'created_at'],
                ['label' => 'Claimed At', 'key' => 'claimed_at'],
            ],
            'warm' => [
                ['label' => 'Lead Name', 'key' => 'lead_name'],
                ['label' => 'Branch Name', 'key' => 'branch_name'],
                ['label' => 'Sales Name', 'key' => 'sales_name'],
                ['label' => 'Telephone', 'key' => 'phone'],
                ['label' => 'Source', 'key' => 'source_name'],
                ['label' => 'Needs', 'key' => 'needs'],
                ['label' => 'Industry', 'key' => 'existing_industries'],
                ['label' => 'City', 'key' => 'city_name'],
                ['label' => 'Regional', 'key' => 'regional_name'],
                ['label' => 'Customer Type', 'key' => 'customer_type'],
                ['label' => 'Quotation Number', 'key' => 'quotation_number'],
                ['label' => 'Quotation Price', 'key' => 'quotation_price'],
                ['label' => 'Quotation Created', 'key' => 'quot_created'],
                ['label' => 'Quotation End Date', 'key' => 'quot_end_date'],
                ['label' => 'ACT Last Time', 'key' => 'act_last_time'],
                ['label' => 'ACT Status', 'key' => 'act_status'],
                ['label' => 'Created At', 'key' => 'created_at'],
                ['label' => 'Claimed At', 'key' => 'claimed_at'],
            ],
            'hot' => [
                ['label' => 'Lead Name', 'key' => 'lead_name'],
                ['label' => 'Branch Name', 'key' => 'branch_name'],
                ['label' => 'Sales Name', 'key' => 'sales_name'],
                ['label' => 'Telephone', 'key' => 'phone'],
                ['label' => 'Source', 'key' => 'source_name'],
                ['label' => 'Needs', 'key' => 'needs'],
                ['label' => 'Industry', 'key' => 'existing_industries'],
                ['label' => 'City', 'key' => 'city_name'],
                ['label' => 'Regional', 'key' => 'regional_name'],
                ['label' => 'Customer Type', 'key' => 'customer_type'],
                ['label' => 'Quotation Number', 'key' => 'quotation_number'],
                ['label' => 'Quotation Price', 'key' => 'quotation_price'],
                ['label' => 'Invoice', 'key' => 'invoice_number'],
                ['label' => 'Invoice Price', 'key' => 'invoice_price'],
                ['label' => 'Quotation Created', 'key' => 'quot_created'],
                ['label' => 'Quotation End Date', 'key' => 'quot_end_date'],
                ['label' => 'ACT Last Time', 'key' => 'act_last_time'],
                ['label' => 'ACT Status', 'key' => 'act_status'],
                ['label' => 'Created At', 'key' => 'created_at'],
                ['label' => 'Claimed At', 'key' => 'claimed_at'],
            ],
            'deal' => [
                ['label' => 'Lead Name', 'key' => 'lead_name'],
                ['label' => 'Branch Name', 'key' => 'branch_name'],
                ['label' => 'Sales Name', 'key' => 'sales_name'],
                ['label' => 'Telephone', 'key' => 'phone'],
                ['label' => 'Source', 'key' => 'source_name'],
                ['label' => 'Needs', 'key' => 'needs'],
                ['label' => 'Industry', 'key' => 'existing_industries'],
                ['label' => 'City', 'key' => 'city_name'],
                ['label' => 'Regional', 'key' => 'regional_name'],
                ['label' => 'Customer Type', 'key' => 'customer_type'],
                ['label' => 'Quotation Number', 'key' => 'quotation_number'],
                ['label' => 'Quotation Price', 'key' => 'quotation_price'],
                ['label' => 'Invoice', 'key' => 'invoice_number'],
                ['label' => 'Invoice Price', 'key' => 'invoice_price'],
                ['label' => 'Quotation Created', 'key' => 'quot_created'],
                ['label' => 'Quotation End Date', 'key' => 'quot_end_date'],
                ['label' => 'ACT Last Time', 'key' => 'act_last_time'],
                ['label' => 'ACT Status', 'key' => 'act_status'],
                ['label' => 'Created At', 'key' => 'created_at'],
                ['label' => 'Claimed At', 'key' => 'claimed_at'],
            ],
        ];

        $rows = [];
        $columns = $stageColumns[$stage] ?? $stageColumns['all'];
        $rows[] = array_map(fn($column) => $column['label'], $columns);

        foreach ($leads->orderByDesc('created_at')->orderByDesc('id')->get() as $lead) {
            $latestActivity = $lead->activityLogs
                ->sortByDesc(function ($activity) {
                    return strtotime($activity->logged_at) . str_pad($activity->id, 10, '0', STR_PAD_LEFT);
                })
                ->first();

            $activeClaim = $lead->claims->firstWhere('released_at', null);
            $latestClaim = $lead->claims->sortByDesc('claimed_at')->first();
            $claim = $activeClaim ?: $latestClaim;
            $quotation = $lead->quotation;
            $latestProforma = $quotation?->proformas->first();
            $invoice = $latestProforma?->invoice;

            $stageLabel = match ($lead->status_id) {
                LeadStatus::COLD => 'Cold',
                LeadStatus::WARM => 'Warm',
                LeadStatus::HOT  => 'Hot',
                LeadStatus::DEAL => 'Deal',
                default          => 'Published',
            };

            $record = [
                'lead_name' => $lead->name ?? '-',
                'branch_name' => $lead->region?->branch?->name ?? $lead->branch?->name ?? '-',
                'sales_name' => $claim?->sales?->name ?? '-',
                'phone' => $lead->phone ?? '-',
                'source_name' => $lead->source?->name ?? '-',
                'needs' => $lead->needs ?? '-',
                'existing_industries' => $lead->industry?->name ?? '-',
                'city_name' => $lead->region?->name ?? 'All Regions',
                'regional_name' => $lead->region?->regional?->name ?? '-',
                'customer_type' => $lead->customer_type ?? '-',
                'quotation_number' => $quotation?->quotation_no ?? '-',
                'quotation_price' => $quotation ? number_format($quotation->grand_total ?? 0, 2) : '-',
                'invoice_number' => $invoice?->invoice_no ?? '-',
                'invoice_price' => $invoice ? number_format($invoice->amount ?? 0, 2) : '-',
                'quot_created' => $lead->published_at
                    ? \Carbon\Carbon::parse($lead->published_at)->format('d/m/Y')
                    : '-',
                'quot_end_date' => $lead->updated_at
                    ? \Carbon\Carbon::parse($lead->updated_at)->format('d/m/Y')
                    : '-',
                'act_last_time' => $latestActivity?->logged_at
                    ? \Carbon\Carbon::parse($latestActivity->logged_at)->format('d/m/Y')
                    : '-',
                'act_status' => $latestActivity?->activity?->name ?? '-',
                'created_at' => $lead->created_at
                    ? \Carbon\Carbon::parse($lead->created_at)->format('d/m/Y')
                    : '-',
                'claimed_at' => $claim?->claimed_at
                    ? \Carbon\Carbon::parse($claim->claimed_at)->format('d/m/Y')
                    : '-',
                'status_name' => $stageLabel,
            ];

            $rows[] = array_map(fn($column) => $record[$column['key']] ?? '-', $columns);
        }

        $file = $this->createXlsx($rows);
        $downloadFileName = $this->buildManageExportDownloadFileName($request);

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            $content = file_get_contents($file);
            $base64 = base64_encode($content);
            @unlink($file);
            return response()->json([
                'filename' => $downloadFileName,
                'content_base64' => $base64,
            ]);
        }

        return response()->download($file, $downloadFileName)->deleteFileAfterSend(true);
    }

    private function buildManageExportDownloadFileName(Request $request): string
    {
        $baseName = trim((string) $request->input('export_file_name', ''));

        if ($baseName === '') {
            $parts = [];
            $search = trim((string) $request->input('search', ''));
            $branchName = null;
            $salesName = null;
            $startDate = trim((string) $request->input('start_date', ''));
            $endDate = trim((string) $request->input('end_date', ''));
            $stage = strtolower((string) $request->input('stage', 'all'));

            if ($search !== '') {
                $parts[] = "[SEARCHED - {$search}]";
            }

            if ($request->filled('branch_id')) {
                $branchName = Branch::query()
                    ->whereKey($request->input('branch_id'))
                    ->value('name');
            }

            if ($branchName) {
                $parts[] = "[Branch - {$branchName}]";
            }

            if ($request->filled('sales_id')) {
                $salesName = User::query()
                    ->whereKey($request->input('sales_id'))
                    ->value('name');
            }

            if ($salesName) {
                $parts[] = "[Sales - {$salesName}]";
            }

            if ($startDate !== '' && $endDate !== '') {
                $parts[] = "[Date - {$startDate} to {$endDate}]";
            } elseif ($startDate !== '') {
                $parts[] = "[Date - {$startDate}]";
            } elseif ($endDate !== '') {
                $parts[] = "[Date - {$endDate}]";
            }

            $stageLabel = match ($stage) {
                'cold' => 'Cold',
                'warm' => 'Warm',
                'hot' => 'Hot',
                'deal' => 'Deal',
                default => 'All Stage',
            };

            $parts[] = "[Stage - {$stageLabel}]";
            $baseName = implode(' - ', $parts);
        }

        $baseName = trim($baseName);
        $baseName = preg_replace('/\.xlsx$/i', '', $baseName);
        $baseName = strip_tags($baseName);
        $baseName .= ' - [Exported At - ' . now()->format('Ymd_His') . ']';

        return $this->sanitizeExportDownloadFileName($baseName);
    }

    private function applyManageClaimedAtDateFilter($leads, Request $request): void
    {
        if (! $request->filled('start_date') && ! $request->filled('end_date')) {
            return;
        }

        $leads->whereHas('claims', function ($q) use ($request) {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $q->whereDate('claimed_at', '>=', $request->start_date)
                    ->whereDate('claimed_at', '<=', $request->end_date);
            } elseif ($request->filled('start_date')) {
                $q->whereDate('claimed_at', '>=', $request->start_date);
            } else {
                $q->whereDate('claimed_at', '<=', $request->end_date);
            }
        });
    }

    private function sanitizeExportDownloadFileName(string $fileName): string
    {
        $fileName = preg_replace('/[\\\\\\/:*?"<>|]+/', ' - ', $fileName);
        $fileName = preg_replace('/\s+/', ' ', $fileName);
        $fileName = preg_replace('/-+/', '-', $fileName);
        $fileName = trim($fileName, " .-\t\n\r\0\x0B");

        if ($fileName === '') {
            $fileName = 'leads-export-' . now()->format('Ymd_His');
        }

        if (function_exists('mb_substr')) {
            $fileName = mb_substr($fileName, 0, 180);
        } else {
            $fileName = substr($fileName, 0, 180);
        }

        return $fileName . '.xlsx';
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
    public function myAllList(Request $request)
    {
        AutoTrashService::triggerIfNeeded();

        $user    = $request->user();
        $perPage = $request->get('per_page', 10);
        $selfScopedRoles = ['sales', 'branch_manager', 'sales_director'];

        $allowedStatuses = [
            LeadStatus::COLD,
            LeadStatus::WARM,
            LeadStatus::HOT,
            LeadStatus::DEAL,
        ];

        $claims = LeadClaim::with([
            'lead.status',
            'lead.segment',
            'lead.source',
            'lead.region.regional',
            'lead.quotation',
            'lead.industry',
            'sales'
        ])
            ->whereNull('released_at')
            ->whereNull('trash_note')
            ->whereHas('lead', function ($q) use ($request, $allowedStatuses) {
                // Selalu batasi hanya ke status aktif (Cold/Warm/Hot/Deal)
                $q->whereIn('status_id', $allowedStatuses);

                // Optional: filter tambahan jika `status` dikirim dan masih termasuk allowed
                if ($request->filled('status') && in_array((int) $request->status, $allowedStatuses, true)) {
                    $q->where('status_id', (int) $request->status);
                }
            });

        if (in_array($user->role?->code, $selfScopedRoles, true)) {
            $claims->where('sales_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $claims->where(function ($query) use ($search) {
                // Lead basic fields + needs + customer type
                $query->whereHas('lead', function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('needs', 'like', "%{$search}%")
                            ->orWhere('customer_type', 'like', "%{$search}%");
                    });
                })
                    // Sales name
                    ->orWhereHas('sales', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    // Source name
                    ->orWhereHas('lead.source', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    // City name
                    ->orWhereHas('lead.region', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    // Regional name
                    ->orWhereHas('lead.region.regional', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // FILTER DATE
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $claims->whereHas('lead', function ($q) use ($request) {
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $q->whereDate('claimed_at', '>=', $request->start_date)
                        ->whereDate('claimed_at', '<=', $request->end_date);
                } elseif ($request->filled('start_date')) {
                    $q->whereDate('claimed_at', '>=', $request->start_date);
                } else {
                    $q->whereDate('claimed_at', '<=', $request->end_date);
                }
            });
        }

        // Source filter
        if ($request->filled('sources')) {
            $source = $request->input('sources');

            if (is_string($source) && str_contains($source, ',')) {
                $source = array_filter(array_map('trim', explode(',', $source)));
            }

            $claims->whereHas('lead', function ($q) use ($source) {
                if (is_array($source)) {
                    $q->whereIn('source_id', $source);
                } else {
                    $q->where('source_id', $source);
                }
            });
        }

        $paginated = $claims
            ->orderByDesc('claimed_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $cityIds = $paginated->getCollection()
            ->pluck('lead.factory_city_id')
            ->filter()
            ->unique()
            ->values();

        $cities = collect();
        $regionals = collect();
        $provinces = collect();

        if ($cityIds->isNotEmpty()) {
            $cities = DB::table('ref_regions')
                ->whereIn('id', $cityIds)
                ->select('id', 'name', 'regional_id', 'province_id')
                ->get()
                ->keyBy('id');

            $regionalIds = $cities->pluck('regional_id')->filter()->unique()->values();
            $provinceIds = $cities->pluck('province_id')->filter()->unique()->values();

            if ($regionalIds->isNotEmpty()) {
                $regionals = DB::table('ref_regionals')
                    ->whereIn('id', $regionalIds)
                    ->select('id', 'name')
                    ->get()
                    ->keyBy('id');
            }

            if ($provinceIds->isNotEmpty()) {
                $provinces = DB::table('ref_provinces')
                    ->whereIn('id', $provinceIds)
                    ->select('id', 'name')
                    ->get()
                    ->keyBy('id');
            }
        }

        $paginated->getCollection()->transform(function ($row) use ($user, $cities, $regionals, $provinces) {

            $lead = $row->lead;
            $city = $lead ? $cities->get($lead->factory_city_id) : null;

            if ($lead) {
                $lead->alternate_location = $city ? [
                    'region_id' => $city->id,
                    'region_name' => $city->name,
                    'regional_id' => $city->regional_id,
                    'regional_name' => optional($regionals->get($city->regional_id))->name,
                    'province_id' => $city->province_id,
                    'province_name' => optional($provinces->get($city->province_id))->name,
                ] : null;
            }

            $row->name          = $lead->name ?? '-';
            $row->phone         = $lead->phone ?? '-';
            $row->email         = $lead->email ?? '-';
            $row->source        = $lead->source->name ?? '-';
            $row->segment_name  = $lead->segment->name ?? '-';
            $row->regional_name = $lead->region->regional->name ?? '-';
            $row->sales_name    = $row->sales->name ?? '-';
            $row->status_name   = $lead->status->name ?? '-';

            switch ($lead->status?->name) {
                case 'Cold':
                    $row->actions = $this->coldActions($row);
                    break;

                case 'Warm':
                    $row->actions = $this->warmActions($row);
                    break;

                case 'Hot':
                    $row->actions = $this->hotActions($row);
                    break;

                case 'Deal':
                    $row->actions = $this->dealActions($row);
                    break;

                default:
                    $row->actions = '-';
            }

            return $row;
        });

        return response()->json([
            'data'         => $paginated->items(),
            'total'        => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
        ]);
    }

    protected function coldActions($row)
    {
        $meeting     = $row->lead->meetings()->latest()->first();
        $leadUrl     = route('leads.my.cold.manage', $row->lead_id);
        $trashUrl    = route('leads.my.cold.trash', $row->id);
        $setMeetUrl  = route('leads.my.cold.meeting', $row->id);
        $btnId       = 'actionsDropdown' . $row->id;

        $html  = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! text-[#1E1E1E]! hover:text-white! dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right rounded-lg!" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($leadUrl) . '">
            ' . view('components.icon.detail')->render() . '
            View Lead Detail</a>';
        $activityUrl = route('leads.activity.logs', $row->lead_id);
        $html .= '    <button type="button" class="dropdown-item btn-activity-log cursor-pointer flex! items-center! gap-2! text-[#1E1E1E]!" data-url="' . e($activityUrl) . '">
            ' . view('components.icon.log')->render() . '
        View / Add Activity Log</button>';

        if (! $meeting) {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($setMeetUrl) . '">
            ' . view('components.icon.meeting')->render() . '
            Set Meeting</a>';
        } else {
            $viewUrl       = route('leads.my.cold.meeting', $row->id);
            $rescheduleUrl = route('leads.my.cold.meeting.reschedule', $meeting->id);
            $resultUrl     = route('leads.my.cold.meeting.result', $meeting->id);
            $cancelUrl     = route('leads.my.cold.meeting.cancel', $meeting->id);

            $html .= '  <a class="dropdown-item" href="' . e($viewUrl) . '"><i class="bi bi-calendar-event mr-2"></i> View Meeting</a>';

            // Cancel condition
            if (!in_array(optional($meeting->expense)->status, ['submitted', 'canceled']) && is_null($meeting->result)) {
                $html .= '  <button class="dropdown-item text-[#900B09]! cancel-meeting cursor-pointer" data-url="' . e($cancelUrl) . '" data-online="' . ($meeting->is_online ? 1 : 0) . '" data-status="' . (optional($meeting->expense)->status ?? '') . '">'
                    . '    <i class="bi bi-x-circle mr-2"></i> Cancel Meeting</button>';
            }

            // Reschedule condition
            $canSetResult = $meeting->is_online || ($meeting->expense && $meeting->expense->status === 'approved');
            $canReschedule = !$canSetResult
                && optional($meeting->expense)->status !== 'submitted';

            if ($canReschedule) {
                $html .= '  <a class="dropdown-item" href="' . e($rescheduleUrl) . '"><i class="bi bi-arrow-repeat mr-2"></i> Reschedule</a>';
            }

            // Set Result condition
            if (now()->gt($meeting->scheduled_end_at) && ($meeting->result === null || $meeting->result === 'waiting') && $canSetResult) {
                $html .= '  <a class="dropdown-item text-[#02542D]!" href="' . e($resultUrl) . '"><i class="bi bi-check2-square mr-2"></i> Set Result</a>';
            }
        }

        if (! $meeting) {
            $html .= '  <button class="dropdown-item text-danger trash-lead cursor-pointer flex! items-center! gap-2! text-[#900B09]!" data-url="' . e($trashUrl) . '">
            ' . view('components.icon.trash')->render() . '
            Move to Trash Lead</button>';
        }
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    protected function warmActions($row)
    {
        $quotation = $row->lead->quotation;
        $viewUrl   = route('leads.my.warm.manage', $row->lead->id);
        $createUrl = route('leads.my.warm.quotation.create', $row->id);
        $quoteUrl  = $quotation ? route('quotations.show', $quotation->id) : null;
        $downloadUrl = $quotation ? route('quotations.download', $quotation->id) : null;
        $trashUrl   = route('leads.my.warm.trash', $row->id);

        $btnId = 'warmActionsDropdown' . $row->id;

        $html  = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"'
            . ' type="button" id="' . $btnId . '"'
            . ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right text-[#1E1E1E]!" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item flex! items-center! gap-2!" href="' . e($viewUrl) . '">'
            . '
            ' . view('components.icon.detail')->render() . ' 
            View Lead</a>';
        $activityUrl = route('leads.activity.logs', $row->lead->id);
        $html .= '    <button type="button" class="dropdown-item btn-activity-log cursor-pointer flex! items-center! gap-2!" data-url="' . e($activityUrl) . '">
        ' . view('components.icon.log')->render() . ' 
        View / Add Activity</button>';

        if (! $quotation) {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($createUrl) . '">'
                . '    
                ' . view('components.icon.generate-quotation')->render() . '
                Generate Quotation</a>';
        } else {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($quoteUrl) . '">'
                . '    
                ' . view('components.icon.view-quotation')->render() . ' 
                View Quotation</a>';
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($downloadUrl) . '">'
                . '    
                ' . view('components.icon.download')->render() . ' 
                Download</a>';
            $logUrl = route('quotations.logs', $quotation->id);
            $html .= '  <button type="button" class="dropdown-item btn-quotation-log cursor-pointer flex! items-center! gap-2!" data-url="' . e($logUrl) . '">
            ' . view('components.icon.quotation-log')->render() . ' 
            Quotation Log</button>';
        }

        if (! $quotation || $quotation->status !== 'published') {
            $html .= '  <button class="dropdown-item text-[#900B09]! cursor-pointer trash-lead flex! items-center! gap-2!" data-url="' . e($trashUrl) . '">
            ' . view('components.icon.trash')->render() . '
            Trash Lead</button>';
        }
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    protected function hotActions($row)
    {
        $quotation = $row->lead->quotation;
        $viewUrl   = route('leads.manage.form', $row->lead->id);
        $quoteUrl  = $quotation ? route('quotations.show', $quotation->id) : null;
        $downloadUrl = $quotation ? route('quotations.download', $quotation->id) : null;

        $btnId = 'hotActionsDropdown' . $row->id;

        $html  = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"'
            . ' type="button" id="' . $btnId . '"'
            . ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right text-[#1E1E1E]!" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item flex! items-center! gap-2!" href="' . e($viewUrl) . '">'
            . '
            ' . view('components.icon.detail')->render() . '
            View Lead</a>';
        $activityUrl = route('leads.activity.logs', $row->lead->id);
        $html .= '    <button type="button" class="dropdown-item btn-activity-log cursor-pointer flex! items-center! gap-2!" data-url="' . e($activityUrl) . '">
        ' . view('components.icon.log')->render() . ' 
        View / Add Activity Log</button>';

        if (! $quotation) {
            $html .= '  <a class="dropdown-item" href="' . route('leads.my.warm.quotation.create', $row->id) . '">'
                . '
                ' . view('components.icon.generate-quotation')->render() . '
                Generate Quotation</a>';
        } else {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($quoteUrl) . '">'
                . '    
                ' . view('components.icon.view-quotation')->render() . '
                View Quotation</a>';
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($downloadUrl) . '">'
                . '    
                ' . view('components.icon.download') . ' 
                Download</a>';
        }

        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    protected function dealActions($row)
    {
        $quotation = $row->lead->quotation;
        $viewUrl   = route('leads.manage.form', $row->lead->id);
        $quoteUrl  = $quotation ? route('quotations.show', $quotation->id) : null;
        $downloadUrl = $quotation ? route('quotations.download', $quotation->id) : null;

        $btnId = 'dealActionsDropdown' . $row->id;

        $html  = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"'
            . ' type="button" id="' . $btnId . '"'
            . ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item flex! items-center! gap-2!" href="' . e($viewUrl) . '">'
            . '
            ' . view('components.icon.detail')->render() . '
            View Lead</a>';
        $activityUrl = route('leads.activity.logs', $row->lead->id);
        $html .= '    <button type="button" class="dropdown-item btn-activity-log flex! items-center! gap-2!" data-url="' . e($activityUrl) . '">
        ' . view('components.icon.log')->render() . '
        View / Add Activity</button>';

        if (! $quotation) {
            $html .= '  <a class="dropdown-item" href="' . route('leads.my.warm.quotation.create', $row->id) . '">'
                . '    <i class="bi bi-file-earmark-plus mr-2"></i> Generate Quotation</a>';
        } else {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($quoteUrl) . '">'
                . '    
                ' . view('components.icon.view-quotation')->render() . '
                View Quotation</a>';
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($downloadUrl) . '">'
                . '    
                ' . view('components.icon.download') . ' 
                Download</a>';
        }

        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }
}
