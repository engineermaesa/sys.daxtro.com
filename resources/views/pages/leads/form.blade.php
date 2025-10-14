@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-xl-12">
                <div class="card mb-4">
                    <div class="card-body pt-3">
                        <div class="text-right">
                            @if (!empty($form_data->id) && $form_data->status_id == \App\Models\Leads\LeadStatus::PUBLISHED)
                                <button type="button" class="btn btn-success me-2" id="btnClaim"
                                    data-url="{{ route('leads.claim', $form_data->id) }}">
                                    Claim Lead
                                </button>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('leads.save', $form_data->id) }}" id="form"
                            back-url="{{ route('leads.available') }}" require-confirmation="true">
                            @csrf
                            @php $isCreate = empty($form_data->id); @endphp

                            <div id="lead-entries">
                                <div class="lead-entry card mb-3">
                                    @if (empty($form_data->id)):
                                    <div class="card-header lead-label">Lead 1</div>
                                    @endif

                                    <div class="card-body">
                                        <div class="row">
                                        @php
                                            $defaultName = old('name', $form_data->name);
                                            $defaultTitle = old('title');
                                            if (! $isCreate && empty($defaultTitle)) {
                                                if (str_starts_with($defaultName, 'Mr ')) {
                                                    $defaultTitle = 'Mr';
                                                    $defaultName = substr($defaultName, 3);
                                                } elseif (str_starts_with($defaultName, 'Mrs ')) {
                                                    $defaultTitle = 'Mrs';
                                                    $defaultName = substr($defaultName, 4);
                                                }
                                            }
                                        @endphp
                                        <div class="col-md-1 mb-3">
                                            <label class="form-label">Title <i class="required">*</i></label>
                                            <br>
                                            <select name="{{ $isCreate ? 'title[]' : 'title' }}" class="form-select" required>
                                                <option value="Mr" {{ $defaultTitle === 'Mr' ? 'selected' : '' }}>Mr</option>
                                                <option value="Mrs" {{ $defaultTitle === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Name <i class="required">*</i></label>
                                            <input type="text" name="{{ $isCreate ? 'name[]' : 'name' }}" placeholder="Nama Lengkap" class="form-control"
                                                value="{{ $defaultName }}" required>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Jabatan <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'jabatan_id[]' : 'jabatan_id' }}" class="form-select select2" required>
                                                <option value="" disabled selected>Pilih</option>
                                                @foreach($jabatans as $jabatan)
                                                    <option value="{{ $jabatan->id }}" {{ old('jabatan_id', $form_data->jabatan_id) == $jabatan->id ? 'selected' : '' }}>{{ $jabatan->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Phone <i class="required">*</i></label>
                                            <input type="text" name="{{ $isCreate ? 'phone[]' : 'phone' }}" placeholder="0812xxxxxxx" class="form-control"
                                                value="{{ old('phone', $form_data->phone) }}" required>
                                        </div>                                                                                

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="{{ $isCreate ? 'email[]' : 'email' }}" placeholder="email@domain.com" class="form-control"
                                                value="{{ old('email', $form_data->email) }}">
                                        </div>

                                        <div class="pic-extensions col-12">
                                            @foreach ($form_data->picExtensions ?? [] as $pic)
                                                <div class="row pic-entry">
                                                    <div class="col-md-1 mb-3">
                                                        <select class="form-select" data-field="title" required>
                                                            <option value="Mr" {{ $pic->title === 'Mr' ? 'selected' : '' }}>Mr</option>
                                                            <option value="Mrs" {{ $pic->title === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <input type="text" class="form-control" data-field="nama" value="{{ $pic->nama }}" placeholder="Nama Lengkap" required>
                                                    </div>
                                                    <div class="col-md-2 mb-3">
                                                        <select class="form-select select2" data-field="jabatan_id" required>
                                                            <option value="" disabled {{ empty($pic->jabatan_id) ? 'selected' : '' }}>Pilih</option>
                                                            @foreach($jabatans as $jabatan)
                                                                <option value="{{ $jabatan->id }}" {{ $pic->jabatan_id == $jabatan->id ? 'selected' : '' }}>{{ $jabatan->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 mb-3">
                                                        <input type="text" class="form-control" data-field="phone" value="{{ $pic->phone }}" placeholder="0812xxxxxxx" required>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <input type="email" class="form-control" data-field="email" value="{{ $pic->email }}" placeholder="email@domain.com" required>
                                                    </div>
                                                    <div class="col-md-1 mb-3 d-flex align-items-end">
                                                        <button type="button" class="btn btn-outline-danger remove-pic">&times;</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="col-12 mb-3">
                                            <button type="button" class="btn btn-sm btn-outline-primary add-pic">
                                                <i class="bi bi-person-plus me-1"></i> Add PIC
                                            </button>
                                        </div>

                                        <div class="col-12 mb-4"> 
                                            <hr class="text-muted" />
                                        </div>



                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Source <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'source_id[]' : 'source_id' }}" class="form-select select2 source-select" required>
                                            <option value="" disabled selected>Pilih</option>
                                            @php
                                                $filter = [
                                                    'Ads Google',
                                                    'Website',
                                                    'Instagram',
                                                    'Facebook',
                                                    'Linked In',
                                                    'Tik Tok',
                                                    'Friends Recommendation',
                                                    'Canvas', 
                                                    'Visit', 
                                                    'Expo RHVAC Jakarta 2025',
                                                    'Association',
                                                    'Business Association',
                                                    'Repeat Order',
                                                    'Sales Independen',
                                                    'Aftersales',
                                                    'Office Walk In',
                                                    'Media with QR/Referral',
                                                    'Agent / Reseller',
                                                    'Youtube',
                                                    'Google Search',
                                                    'Telemarketing',
                                                ];
                                                $isNew = empty($form_data->source_id);
                                            @endphp

                                            @foreach ($sources as $source)
                                                @if ($isNew ? in_array($source->name, $filter) : true)
                                                    <option value="{{ $source->id }}"
                                                        {{ old('source_id', $form_data->source_id) == $source->id ? 'selected' : '' }}>
                                                        {{ $source->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                            </select>
                                        </div>

                                    <div class="col-md-2 mb-3 agent-fields d-none">
                                        <label class="form-label">Agent Title</label>
                                        <select name="{{ $isCreate ? 'agent_title[]' : 'agent_title' }}" class="form-select">
                                            <option value="">Select Title</option>
                                            <option value="Mr" {{ old('agent_title', $form_data->agent_title) === 'Mr' ? 'selected' : '' }}>Mr</option>
                                            <option value="Mrs" {{ old('agent_title', $form_data->agent_title) === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                                            <option value="Ms" {{ old('agent_title', $form_data->agent_title) === 'Ms' ? 'selected' : '' }}>Ms</option>
                                            <option value="Dr" {{ old('agent_title', $form_data->agent_title) === 'Dr' ? 'selected' : '' }}>Dr</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3 agent-fields d-none">
                                        <label class="form-label">Agent Name</label>
                                        <input type="text" name="{{ $isCreate ? 'agent_name[]' : 'agent_name' }}" 
                                            class="form-control" 
                                            placeholder="Enter agent name" 
                                            value="{{ old('agent_name', $form_data->agent_name) }}">
                                    </div>

                                    <div class="col-md-8 mb-3 canvas-fields d-none">
                                        <label class="form-label">SPK Canvassing</label>
                                        <input type="text" name="{{ $isCreate ? 'spk_canvassing[]' : 'spk_canvassing' }}" 
                                            class="form-control" 
                                            placeholder="Enter SPK Canvassing details" 
                                            value="{{ old('spk_canvassing', $form_data->spk_canvassing) }}">
                                    </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Company <i class="required">*</i></label>
                                            <input type="text" name="{{ $isCreate ? 'company[]' : 'company' }}" placeholder="Nama Perusahaan" class="form-control"
                                                value="{{ old('company', $form_data->company) }}" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Customer Type <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'customer_type[]' : 'customer_type' }}" class="form-select select2" required>
                                                <option value="" disabled selected>Pilih</option>
                                                @foreach($customerTypes as $type)
                                                    <option value="{{ $type->name }}" {{ old('customer_type', $form_data->customer_type) == $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>      
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Reason of Contacting Us</label>
                                            <textarea name="{{ $isCreate ? 'contact_reason[]' : 'contact_reason' }}" class="form-control" rows="2">{{ old('contact_reason', $form_data->contact_reason) }}</textarea>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Reason to Open Business</label>
                                            <textarea name="{{ $isCreate ? 'business_reason[]' : 'business_reason' }}" class="form-control" rows="2">{{ old('business_reason', $form_data->business_reason) }}</textarea>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Competitor Offer</label>
                                            <textarea name="{{ $isCreate ? 'competitor_offer[]' : 'competitor_offer' }}" class="form-control" rows="2">{{ old('competitor_offer', $form_data->competitor_offer) }}</textarea>
                                        </div>

                                        {{-- <div class="col-md-4 mb-3">
                                            <label class="form-label">Transaction Type <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'segment_id[]' : 'segment_id' }}" class="form-select select2" required>
                                            <option value="" disabled selected>Pilih</option>
                                            @foreach ($segments as $segment)
                                                <option value="{{ $segment->id }}"
                                                    {{ old('segment_id', $form_data->segment_id) == $segment->id ? 'selected' : '' }}>
                                                    {{ $segment->name }}</option>
                                            @endforeach
                                            </select>
                                        </div> --}}
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Customer City <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'region_id[]' : 'region_id' }}" class="form-select select2 region-select">
                                                <option value="" disabled {{ old('region_id', $form_data->region_id)===null ? 'selected' : '' }}>Pilih</option>
                                                <option value="ALL" {{ old('region_id', $form_data->region_id)==='ALL' ? 'selected' : '' }}>
                                                    All Regions (will show in all regions)
                                                </option>
                                                @foreach($regions as $region)
                                                    <option 
                                                    value="{{ $region->id }}" 
                                                    data-branch="{{ $region->branch_id }}"
                                                    {{ old('region_id', $form_data->region_id)==$region->id ? 'selected' : '' }}
                                                    >
                                                    {{ $region->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" 
                                                name="{{ $isCreate ? 'branch_id[]' : 'branch_id' }}" 
                                                class="branch-id-field" 
                                                value="{{ old('branch_id', $form_data->branch_id) }}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Customer Province <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'province[]' : 'province' }}" class="form-select select2 province-select">
                                            <option value="" selected>Pilih</option>
                                            @foreach ($provinces as $prov)
                                                <option value="{{ $prov }}"
                                                    {{ old('province', $form_data->province) == $prov ? 'selected' : '' }}>
                                                    {{ $prov }}</option>
                                            @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Existing Customer Industry<i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'industry_id[]' : 'industry_id' }}" class="form-select select2 industry-select" required>
                                                <option value="" disabled selected>Pilih</option>
                                                @foreach($industries as $industry)
                                                    <option value="{{ $industry->id }}" {{ old('industry_id', $form_data->industry_id ?? ($form_data->other_industry ? 'other' : null)) == $industry->id ? 'selected' : '' }}>{{ $industry->name }}</option>
                                                @endforeach
                                                <option value="other" {{ old('industry_id', $form_data->industry_id ?? ($form_data->other_industry ? 'other' : null)) === 'other' ? 'selected' : '' }}>Lainnya</option>
                                            </select>
                                            <input type="text" name="{{ $isCreate ? 'other_industry[]' : 'other_industry' }}" class="form-control mt-2 industry-other d-none" placeholder="Isi industri" value="{{ old('other_industry', $form_data->other_industry) }}" />
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Industry Remark</label>
                                            <textarea name="{{ $isCreate ? 'industry_remark[]' : 'industry_remark' }}" class="form-control" placeholder="Additional comments about the industry" rows="2">{{ old('industry_remark', $form_data->industry_remark) }}</textarea>
                                        </div>

                                        <div class="col-12 mb-4"> 
                                            <hr class="text-muted" />
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">City Factory To Be</label>
                                            <select name="{{ $isCreate ? 'factory_city_id[]' : 'factory_city_id' }}" class="form-select select2 factory-region-select">
                                                <option value="" disabled selected>Pilih</option>
                                                <option value="ALL" {{ old('factory_city_id', $form_data->factory_city_id) === 'ALL' ? 'selected' : '' }}>
                                                    All Cities
                                                </option>
                                                @foreach($regions as $region)
                                                    <option value="{{ $region->id }}" 
                                                        data-branch="{{ $region->branch_id }}"
                                                        data-province="{{ $region->province->name ?? '' }}"
                                                        {{ old('factory_city_id', $form_data->factory_city_id) == $region->id ? 'selected' : '' }}>
                                                        {{ $region->name }}
                                                    </option>
                                                @endforeach

                                                @php
                                                    dump('Region object:', $region ?? null);
                                                    dump('Factory city data:', $form_data->factory_city_id ?? null);
                                                    dump('Factory province:', $form_data->factory_province ?? null);
                                                @endphp
                                                
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Province Factory To Be</label>
                                            <select name="{{ $isCreate ? 'factory_province[]' : 'factory_province' }}" class="form-select select2 factory-province-select">
                                                <option value="" selected>Pilih</option>
                                                @foreach ($provinces as $prov)
                                                    <option value="{{ $prov }}" {{ old('factory_province', $form_data->factory_province) == $prov ? 'selected' : '' }}>
                                                        {{ $prov }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Industry To Be</label>
                                            <select name="{{ $isCreate ? 'factory_industry_id[]' : 'factory_industry_id' }}" class="form-select select2 factory-industry-select">
                                                <option value="" disabled selected>Pilih</option>
                                                @foreach($industries as $industry)
                                                    <option value="{{ $industry->id }}" {{ old('factory_industry_id', $form_data->factory_industry_id) == $industry->id ? 'selected' : '' }}>
                                                        {{ $industry->name }}
                                                    </option>
                                                @endforeach
                                                <option value="other" {{ old('factory_industry_id', $form_data->factory_industry_id ?? ($form_data->factory_other_industry ? 'other' : null)) === 'other' ? 'selected' : '' }}>Lainnya</option>
                                            </select>
                                            <input type="text" name="{{ $isCreate ? 'factory_other_industry[]' : 'factory_other_industry' }}" 
                                                class="form-control mt-2 factory-industry-other d-none" 
                                                placeholder="Isi industri" 
                                                value="{{ old('factory_other_industry', $form_data->factory_other_industry) }}" />
                                        </div>
                                
                                        {{-- <div class="col-md-4 mb-3">
                                            <label class="form-label">Product @if($isCreate)<i class="required">*</i>@endif</label>
                                            <select name="{{ $isCreate ? 'product_id[]' : 'product_id' }}" class="form-select select2" @if($isCreate) required @endif>
                                                <option value="" disabled selected>Pilih</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->id }}" {{ old('product_id', $form_data->product_id) == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                                                @endforeach
                                            </select>
                                        </div> --}}

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Needs <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'needs[]' : 'needs' }}" class="form-select select2" required>
                                                <option value="" disabled selected>Pilih</option>
                                                @php
                                                    $needsOptions = [
                                                        'Tube Ice ( Mesin Es Kristal Tabung )',
                                                        'Cube Ice ( Mesin Es Kristal Kubus )',
                                                        'Block Ice ( Mesin Es Balok )',
                                                        'Flake ice ( Mesin Es Pecah )',
                                                        'Slurry Ice ( Es Bubur halus )',
                                                        'Flake Ice ( Es Serpih )',
                                                        'Cold Room ( Ruang Pendingin )',
                                                        'Other ( Keperluan Kustom )',
                                                    ];
                                                    $selectedNeed = old('needs', $form_data->needs);
                                                @endphp
                                                @foreach ($needsOptions as $need)
                                                    <option value="{{ $need }}" {{ $selectedNeed == $need ? 'selected' : '' }}>{{ $need }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tonase</label>
                                            <input type="number" step="0.01" name="{{ $isCreate ? 'tonase[]' : 'tonase' }}" class="form-control" value="{{ old('tonase', $form_data->tonase) }}" placeholder="0.00">
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Tonage Remark</label>
                                            <textarea name="{{ $isCreate ? 'tonage_remark[]' : 'tonage_remark' }}" class="form-control" rows="2">{{ old('tonage_remark', $form_data->tonage_remark) }}</textarea>
                                        </div>

                                        @if ($isCreate)
                                            <div class="col-12 text-end mt-0">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-lead d-none">
                                                    <i class="bi bi-trash me-1"></i> Remove Lead
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            </div>

                            @if ($isCreate)
                                <div class="text-end mb-3">
                                    <button type="button" id="add-lead" class="btn btn-outline-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Add Lead
                                    </button>
                                </div>
                            @endif

                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                @include('partials.common.save-btn-form', ['backUrl' => 'back'])
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if (!empty($form_data->id))
            {{-- Meetings --}}
            @foreach ($meetings as $meeting)
                <div class="card mb-4">
                    <div class="card-header"><strong>Meeting</strong></div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Schedule</th>
                                <td>
                                    {{ $meeting->scheduled_start_at ? date('d M Y H:i', strtotime($meeting->scheduled_start_at)) : '' }}
                                    -
                                    {{ $meeting->scheduled_end_at ? date('d M Y H:i', strtotime($meeting->scheduled_end_at)) : '' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td>{{ $meeting->is_online ? 'Online' : 'Offline' }}</td>
                            </tr>
                            @if ($meeting->is_online)
                                <tr>
                                    <th>URL</th>
                                    <td>{{ $meeting->online_url }}</td>
                                </tr>
                            @else
                                <tr>
                                    <th>Location</th>
                                    <td>{{ trim(($meeting->city ?? '') . ' ' . ($meeting->address ?? '')) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th>Result</th>
                                <td>{{ $meeting->result ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Summary</th>
                                <td>{{ $meeting->summary ?? '-' }}</td>
                            </tr>
                            @if ($meeting->attachment)
                                <tr>
                                    <th>Attachment</th>
                                    <td>
                                        <a href="{{ route('attachments.download', $meeting->attachment_id) }}"
                                            class="btn btn-sm btn-outline-secondary">Download</a>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            @endforeach

            {{-- Quotation --}}
            @if ($quotation)
                <div class="card mb-4">
                    <div class="card-header"><strong>Quotation</strong></div>
                    <div class="card-body">
                        @php
                            $latestReview = $quotation->reviews->sortByDesc('decided_at')->first();
                        @endphp
                        @if($latestReview)
                            <div class="alert alert-{{ $latestReview->decision === 'reject' ? 'danger' : 'success' }}">
                                Quotation {{ $latestReview->decision === 'reject' ? 'rejected' : 'approved' }} by
                                <b>{{ $latestReview->reviewer->name ?? $latestReview->role }}</b>
                                on {{ $latestReview->decided_at ? \Carbon\Carbon::parse($latestReview->decided_at)->format('d M Y') : '' }}<br>
                                <strong>Notes:</strong> {{ $latestReview->notes }}
                            </div>
                        @endif
                        <table class="table table-sm">
                            <tr>
                                <th>No</th>
                                <td>{{ $quotation->quotation_no }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @php
                                        $statusClass =
                                            [
                                                'draft' => 'secondary',
                                                'review' => 'warning',
                                                'published' => 'success',
                                                'rejected' => 'danger',
                                            ][$quotation->status] ?? 'light';
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst($quotation->status) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Grand Total</th>
                                <td>Rp{{ number_format($quotation->grand_total, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Expiry Date</th>
                                <td>{{ $quotation->expiry_date ? date('d M Y', strtotime($quotation->expiry_date)) : '-' }}
                                </td>
                            </tr>
                        </table>
                        <a href="{{ route('quotations.download', $quotation->id) }}"
                            class="btn btn-outline-secondary mb-3">
                            <i class="bi bi-download"></i> Download Quotation
                        </a>

                        @if ($quotation->items->count())
                            <h6 class="mt-4">Items</h6>
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Disc %</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($quotation->items as $item)
                                        <tr>
                                            <td>{{ $item->description }}</td>
                                            <td>{{ $item->qty }}</td>
                                            <td>Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                            <td>{{ $item->discount_pct }}</td>
                                            <td>Rp{{ number_format($item->line_total, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-end">Sub Total</th>
                                        <th class="text-end">Rp{{ number_format($quotation->subtotal, 0, ',', '.') }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Tax ({{ $quotation->tax_pct }}%)</th>
                                        <th class="text-end">Rp{{ number_format($quotation->tax_total, 0, ',', '.') }}</th>
                                    </tr>
                                    @if (!empty($quotation->discount))
                                        <tr>
                                            <th colspan="4" class="text-end">Discount</th>
                                            <th class="text-end text-danger">-
                                                Rp{{ number_format($quotation->discount, 0, ',', '.') }}</th>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th colspan="4" class="text-end">Grand Total</th>
                                        <th class="text-end fw-bold">
                                            Rp{{ number_format($quotation->grand_total, 0, ',', '.') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif

                        @if ($quotation->proformas->count())
                            <h6 class="mt-4">Proformas</h6>
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Term</th>
                                        <th>No</th>
                                        <th>Status</th>
                                        <th>Issued</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($quotation->proformas as $pf)
                                        <tr>
                                            <td>{{ $pf->term_no ?? 'Booking Fee' }}</td>
                                            <td>{{ $pf->proforma_no ?? '-' }}</td>
                                            <td>{{ ucfirst($pf->status) }}</td>
                                            <td>{{ $pf->issued_at ? date('d M Y', strtotime($pf->issued_at)) : '-' }}</td>
                                            <td>
                                                Rp{{ number_format($pf->amount, 0, ',', '.') }}
                                                @if ($pf->paymentConfirmation)
                                                    @php $fr = $pf->paymentConfirmation->financeRequest; @endphp
                                                    @if ($fr && in_array($fr->status, ['approved','rejected']))
                                                        <div class="small text-{{ $fr->status === 'approved' ? 'success' : 'danger' }} mt-1">
                                                            {{ ucfirst($fr->status) }}: {{ $fr->notes }}
                                                        </div>
                                                    @elseif($fr)
                                                        <div class="small text-warning mt-1">Awaiting Finance</div>
                                                    @endif
                                                    @if ($fr && $fr->status !== 'rejected')
                                                        <div class="small text-success mt-1">
                                                            Paid at:
                                                            {{ $pf->paymentConfirmation->paid_at->format('d M Y') }}<br>
                                                            Confirmed by:
                                                            {{ $pf->paymentConfirmation->confirmedBy?->name ?? '-' }}
                                                        </div>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if ($pf->attachment_id)
                                                    <a href="{{ route('attachments.download', $pf->attachment_id) }}"
                                                        class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-download"></i> Download Proforma
                                                    </a>
                                                @endif

                                                @if ($pf->invoice && $pf->invoice->attachment_id)
                                                    <a href="{{ route('attachments.download', $pf->invoice->attachment_id) }}"
                                                        class="btn btn-sm btn-outline-secondary ms-1">
                                                        <i class="bi bi-download"></i> Invoice
                                                    </a>
                                                @endif

                                                @if ($pf->status === 'confirmed')
                                                    @php $fr = $pf->paymentConfirmation?->financeRequest; @endphp
                                                    @if (!$pf->paymentConfirmation)
                                                        <a href="{{ route('payment-confirmation.terms.payment.confirm.form', [$quotation->lead_id, $pf->term_no ?? 'bf']) }}"
                                                            class="btn btn-sm btn-outline-primary ml-1">
                                                            <i class="bi bi-cash-coin"></i> Confirm Payment
                                                        </a>
                                                    @else
                                                        <a href="{{ route('payment-confirmation.terms.payment.confirm.form', [$quotation->lead_id, $pf->term_no ?? 'bf']) }}"
                                                            class="btn btn-sm {{ $fr && $fr->status === 'rejected' ? 'btn-outline-danger' : 'btn-outline-success' }} ml-1">
                                                            <i
                                                                class="bi {{ $fr && $fr->status === 'rejected' ? 'bi-pencil-square' : 'bi-eye' }}"></i>
                                                            {{ $fr && $fr->status === 'rejected' ? 'Edit Payment' : 'View Payment' }}
                                                        </a>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Order --}}
            @if ($order)
                <div class="card mb-4">
                    <div class="card-header"><strong>Order</strong></div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Order No</th>
                                <td>{{ $order->order_no }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>{{ $order->order_status }}</td>
                            </tr>
                            <tr>
                                <th>Total Billing</th>
                                <td>Rp{{ number_format($order->total_billing, 0, ',', '.') }}</td>
                            </tr>
                        </table>

                        @if ($order->orderItems->count())
                            <h6 class="mt-4">Items</h6>
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Disc %</th>
                                        <th>Tax %</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->orderItems as $item)
                                        <tr>
                                            <td>{{ $item->description }}</td>
                                            <td>{{ $item->qty }}</td>
                                            <td>Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                            <td>{{ $item->discount_pct }}</td>
                                            <td>{{ $item->tax_pct }}</td>
                                            <td>Rp{{ number_format($item->line_total, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                    </div>
                </div>
            @endif
        @endif
    </section>
@endsection

@section('scripts')
    <script>
    $(function () {
    /* -----------------------------------------------------------
    * helper: initialise select2 on the given jQuery collection
    * --------------------------------------------------------- */
    function initSelect2($elements) {
        $elements.each(function () {
        const $sel = $(this);

        // if this <select> already has Select2, leave it alone
        if ($sel.data('select2')) return;

        $sel.select2({ width: '100%' });
        });
    }

    function styleDisabledProvince($target) {
        $target.each(function () {
            const $select = $(this);
            const $select2Container = $select.next('.select2-container');

            $select2Container.find('.select2-selection').css({
                'background-color': '#e9ecef',
                'color': '#6c757d',
                'pointer-events': 'none',
                'border-color': '#ced4da',
                'cursor': 'not-allowed'
            });
        });
    }

function toggleAgentFields($select) {
    const $entry = $select.closest('.lead-entry');
    const $agentFields = $entry.find('.agent-fields');
    const $agentTitle = $entry.find('select[name*="agent_title"]');
    const $agentName = $entry.find('input[name*="agent_name"]');
    const $canvasFields = $entry.find('.canvas-fields');
    const $spkCanvassing = $entry.find('input[name*="spk_canvassing"]');
    
    // Get the selected source name
    const selectedText = $select.find('option:selected').text().trim();
    
    // Handle Agent / Reseller
    if (selectedText === 'Agent / Reseller') {
        $agentFields.removeClass('d-none');
        $agentTitle.prop('required', true);
        $agentName.prop('required', true);
    } else {
        $agentFields.addClass('d-none');
        $agentTitle.prop('required', false).val('');
        $agentName.prop('required', false).val('');
    }
    
    // Handle Canvas
    if (selectedText === 'Canvas') {
        $canvasFields.removeClass('d-none');
        $spkCanvassing.prop('required', true);
    } else {
        $canvasFields.addClass('d-none');
        $spkCanvassing.prop('required', false).val('');
    }
}

    $('#lead-entries .source-select').each(function(){
        toggleAgentFields($(this));
    });

    // Add event handler for source changes
    $(document).on('change', '.source-select', function() {
        toggleAgentFields($(this));
    });

    /* -----------------------------------------------------------
    * helper: renumber the “Lead n” labels
    * --------------------------------------------------------- */
    function updateLeadLabels() {
        $('#lead-entries .lead-entry').each(function (i) {
        $(this).find('.lead-label').text('Lead ' + (i + 1));
        });
        updateLeadPicNames();
    }

    function updateLeadPicNames() {
        $('#lead-entries .lead-entry').each(function(i){
            $(this).attr('data-index', i);
            $(this).find('.pic-entry').each(function(){
                $(this).find('[data-field]').each(function(){
                    const field = $(this).data('field');
                    $(this).attr('name', `pic_extensions[${i}][${field}][]`);
                });
            });
        });
    }

    /* -----------------------------------------------------------
    * PAGE-LOAD: turn the first row into Select2 widgets
    * --------------------------------------------------------- */
    initSelect2($('#lead-entries').find('select.select2'));
    styleDisabledProvince($('#lead-entries').find('.province-select'));
    updateLeadLabels();
    updateLeadPicNames();

    const regionProvinces = @json($regions->pluck('province.name','id'));
    $('#lead-entries .province-select').on('select2:opening', e => e.preventDefault());
    $('#lead-entries .region-select').each(function(){
        setProvince($(this));
    });

    function setFactoryProvince($regionSelect) {
        const regionId = $regionSelect.val();
        const $entry = $regionSelect.closest('.lead-entry');
        const $provinceSelect = $entry.find('.factory-province-select');

        if (regionId === 'ALL') {
            $provinceSelect.val('').trigger('change.select2');
        } else {
            const province = $regionSelect.find('option:selected').data('province') || '';
            $provinceSelect.val(province).trigger('change.select2');
        }
    }

    $('#lead-entries .factory-region-select').each(function(){
        setFactoryProvince($(this));
    });

    $(document).on('change', '.factory-region-select', function() {
        setFactoryProvince($(this));
    });

    $(document).on('select2:opening', '.factory-province-select', function(e) {
        // Optional: prevent manual selection if you want it auto-filled only
        // e.preventDefault();
    });

    function toggleIndustryOther($select) {
        const $entry = $select.closest('.lead-entry');
        const $other = $entry.find('.industry-other');
        if ($select.val() === 'other') {
            $other.removeClass('d-none').prop('required', true);
        } else {
            $other.addClass('d-none').prop('required', false).val('');
        }
    }

    $('#lead-entries .industry-select').each(function(){
        toggleIndustryOther($(this));
    });

    /* -----------------------------------------------------------
    * PIC extension add/remove
    * --------------------------------------------------------- */
    const jabatanOptions = @json($jabatans->pluck('name', 'id'));

    function jabatanSelectHtml() {
        let opts = '<option value="" disabled selected>Pilih</option>';
        Object.entries(jabatanOptions).forEach(([id,name]) => {
            opts += `<option value="${id}">${name}</option>`;
        });
        return `<select class="form-select select2" data-field="jabatan_id" required>${opts}</select>`;
    }

    function picEntryHtml() {
        return `
        <div class="row pic-entry">
            <div class="col-md-1 mb-3">
                <select class="form-select" data-field="title" required>
                    <option value="Mr">Mr</option>
                    <option value="Mrs">Mrs</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <input type="text" class="form-control" data-field="nama" placeholder="Nama Lengkap" required>
            </div>
            <div class="col-md-2 mb-3 jabatan-field">
                ${jabatanSelectHtml()}
            </div>
            <div class="col-md-2 mb-3">
                <input type="text" class="form-control" data-field="phone" placeholder="0812xxxxxxx" required>
            </div>
            <div class="col-md-3 mb-3">
                <input type="email" class="form-control" data-field="email" placeholder="email@domain.com" required>
            </div>
            <div class="col-md-1 mb-3 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger remove-pic">&times;</button>
            </div>
        </div>`;
    }

    $(document).on('click', '.add-pic', function(){
        const $entry = $(this).closest('.lead-entry');
        const $html = $(picEntryHtml());
        $entry.find('.pic-extensions').append($html);
        initSelect2($html.find('select.select2'));
        updateLeadPicNames();
    });

    $(document).on('click', '.remove-pic', function(){
        $(this).closest('.pic-entry').remove();
        updateLeadPicNames();
    });

    /* -----------------------------------------------------------
    * ADD lead
    * --------------------------------------------------------- */
    $('#add-lead').on('click', function () {
        const $template = $('#lead-entries .lead-entry:first');
        const $clone    = $template.clone(false, false);   // Shallow-clone, no data/events

        /* ---- strip Select2 from the clone (important!) ---- */
        $clone.find('select.select2').each(function () {
        const $sel = $(this);

        // remove any duplicated Select2 container that came across in the clone
        $sel.siblings('.select2-container').remove();

        // remove Select2‐related markup / classes / data so it’s a brand-new <select>
        $sel.removeAttr('data-select2-id')
            .removeClass('select2-hidden-accessible')
            .removeData('select2')
            .show();

        // cloned <option> tags keep their Select2 IDs and selections
        $sel.find('option')
            .removeAttr('data-select2-id')
            .prop('selected', false)
            .removeAttr('selected');
        });

        toggleIndustryOther($clone.find('.industry-select'));
        toggleFactoryIndustryOther($clone.find('.factory-industry-select'));
        toggleAgentFields($clone.find('.source-select'));

        /* ---- clear field values ---- */
        $clone.find('input').val('');
        // ensure select boxes start on their placeholder
        $clone.find('select').each(function () {
            const $select = $(this);
            $select.val(''); // reset to empty

            // Jika ada option pertama yang kosong (""), pastikan tidak disabled
            const $firstOption = $select.find('option:first-child');
            if ($firstOption.val() === '' && $firstOption.prop('disabled')) {
                $firstOption.prop('disabled', false);
            }
        });
        /* ---- show remove button ---- */
        $clone.find('.remove-lead').removeClass('d-none');

        $clone.find('.pic-extensions').empty();

        /* ---- append clone & init its Select2s only ---- */
        $('#lead-entries').append($clone);
        initSelect2($clone.find('select.select2'));
        styleDisabledProvince($clone.find('.province-select'));

        $clone.find('.province-select').on('select2:opening', e => e.preventDefault());
        setProvince($clone.find('.region-select'));
        toggleIndustryOther($clone.find('.industry-select'));
        updateLeadLabels();
    });

    /* -----------------------------------------------------------
    * REMOVE lead
    * --------------------------------------------------------- */
    $(document).on('click', '.remove-lead', function () {
        $(this).closest('.lead-entry').remove();
        updateLeadLabels();
    });

    /* -----------------------------------------------------------
    * keep hidden branch_id in sync
    * --------------------------------------------------------- */
    function setProvince($regionSelect) {
        const regionId = $regionSelect.val();
        const $entry = $regionSelect.closest('.lead-entry');
        const $provinceSelect = $entry.find('.province-select');

        if (regionId === 'ALL') {
            $provinceSelect.val('').trigger('change.select2');
            $provinceSelect.prop('required', false);
        } else {
            const province = regionProvinces[regionId] || '';
            $provinceSelect.val(province).trigger('change.select2');
            $provinceSelect.prop('required', true);
        }
    }

    function toggleFactoryIndustryOther($select) {
        const $entry = $select.closest('.lead-entry');
        const $other = $entry.find('.factory-industry-other');
        if ($select.val() === 'other') {
            $other.removeClass('d-none').prop('required', true);
        } else {
            $other.addClass('d-none').prop('required', false).val('');
        }
    }

        // Initialize both industry selects
    $('#lead-entries .industry-select').each(function(){
        toggleIndustryOther($(this));
    });

    $('#lead-entries .factory-industry-select').each(function(){
        toggleFactoryIndustryOther($(this));
    });

    // Add event handlers for both industry selects
    $(document).on('change', '.industry-select', function() {
        toggleIndustryOther($(this));
    });

    $(document).on('change', '.factory-industry-select', function() {
        toggleFactoryIndustryOther($(this));
    });


    $(document).on('select2:opening', '.province-select', function (e) {
        e.preventDefault();
    });

    $(document).on('change', '.region-select', function () {
        const branch = $(this).find('option:selected').data('branch');
        $(this).closest('.lead-entry').find('.branch-id-field').val(branch);
        setProvince($(this));
    });

    $(document).on('change', '.industry-select', function () {
        toggleIndustryOther($(this));
    });

    /* -----------------------------------------------------------
    * Claim-lead button
    * --------------------------------------------------------- */
    $('#btnClaim').on('click', function () {
        const url = $(this).data('url');

        Swal.fire({
        title: 'Are you sure?',
        text: 'You are about to claim this lead.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, claim it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#aaa'
        }).then(res => {
        if (!res.isConfirmed) return;

        $.post(url, { _token: '{{ csrf_token() }}' })
            .done(() => {
            notif('Lead claimed successfully');
            location.href = '{{ route('leads.my') }}';
            })
            .fail(xhr => {
            notif(xhr.responseJSON?.message || 'Failed to claim lead', 'error');
            });
        });
    });
    });
    </script>

@endsection