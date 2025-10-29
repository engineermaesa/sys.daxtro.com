@extends('layouts.app')

@section('styles')
    <style>
        .collapse-toggle i.fa-angle-down {
            transition: transform .2s;
        }

        .collapse-toggle.collapsed i.fa-angle-down {
            transform: rotate(-90deg);
        }

        .card-header::after {
            display: none !important;
        }


        #process-flow-container {
            margin-left: -8px;
            margin-right: -8px;
        }
        
        #process-flow-container > div {
            padding-left: 8px;
            padding-right: 8px;
        }
        
        .process-flow-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 4px 8px rgba(0, 0, 0, 0.06);
            height: 77px;
            display: flex;
            align-items: center;
            padding: 20px 16px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            width: 100%;
            margin-bottom: 16px;
        }

        .process-flow-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12), 0 8px 16px rgba(0, 0, 0, 0.08);
        }

        .process-flow-icon {
            width: 46px;
            height: 46px;
            background-color: #F8F9FD;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 14px;
            flex-shrink: 0;
        }

        .process-flow-icon i {
            color: #115641;
            font-size: 16px;
        }

        .process-flow-content {
            flex: 1;
            min-width: 0;
        }

        .process-flow-title {
            font-size: 12px;
            font-weight: 500;
            color: #115641;
            margin: 0 0 3px 0;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .process-flow-count {
            font-size: 20px;
            font-weight: bold;
            color: #115641;
            margin: 0 0 3px 0;
            line-height: 1;
        }

        .process-flow-atr {
            font-size: 10px;
            font-weight: 400;
            color: #6B7280;
            margin: 0;
            line-height: 1;
        }

        
        @media (max-width: 1199px) {
            .process-flow-card {
                height: 88px;
                padding: 18px 14px;
            }
            
            .process-flow-icon {
                width: 42px;
                height: 42px;
                margin-right: 12px;
            }
            
            .process-flow-icon i {
                font-size: 15px;
            }
            
            .process-flow-count {
                font-size: 18px;
            }
        }

        @media (max-width: 991px) {
            .process-flow-card {
                height: 85px;
                padding: 16px 12px;
            }
            
            .process-flow-icon {
                width: 40px;
                height: 40px;
                margin-right: 10px;
            }
            
            .process-flow-icon i {
                font-size: 14px;
            }
            
            .process-flow-count {
                font-size: 17px;
            }
            
            .process-flow-title {
                font-size: 11px;
            }
            
            .process-flow-atr {
                font-size: 9px;
            }
        }

        @media (max-width: 575px) {
            .process-flow-card {
                height: auto;
                min-height: 80px;
                padding: 16px;
            }
            
            .process-flow-icon {
                width: 44px;
                height: 44px;
                margin-right: 14px;
            }
            
            .process-flow-icon i {
                font-size: 16px;
            }
            
            .process-flow-count {
                font-size: 20px;
            }
            
            .process-flow-title {
                font-size: 13px;
            }
            
            .process-flow-atr {
                font-size: 11px;
            }
        }
    </style>
@endsection

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>

    <div class="col-md-12 mb-4">
        <h2 class="font-weight-bold mb-4" style="font-size: 30px; color: #115641;">PROCESS FLOW</h2>
        
        <div class="row" id="process-flow-container">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">AWARE</div>
                        <div class="process-flow-count" id="aware-qty">-</div>
                        <div class="process-flow-atr" id="aware-time">Loading...</div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">APPEAL</div>
                        <div class="process-flow-count" id="appeal-qty">-</div>
                        <div class="process-flow-atr" id="appeal-time">Loading...</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">ASK</div>
                        <div class="process-flow-count" id="ask-qty">-</div>
                        <div class="process-flow-atr" id="ask-time">Loading...</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">ACT</div>
                        <div class="process-flow-count" id="act-qty">-</div>
                        <div class="process-flow-atr" id="act-time">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>    
<div class="col-md-12 mb-4">
  <div class="card shadow border-left-info">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">
        Ringkasan Dashboard & Cara Baca
      </h6>
      <button class="btn btn-link collapse-toggle" type="button"
              data-bs-toggle="collapse" data-bs-target="#summaryBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>

    <div id="summaryBody" class="collapse show">
      <div class="card-body">
        <p class="small text-muted mb-3">
          Gambaran singkat seluruh laporan di dashboard ini. Gunakan tautan untuk melompat ke bagiannya.
        </p>

        <ul class="list-group list-group-flush">

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Kartu</span>
            <strong>Status Quotation (Draft/Review/Published/Rejected/Expired)</strong> —
            menampilkan <em>jumlah</em> dokumen dan <em>total nominal</em> per status. Warna border
            mengikuti status untuk memudahkan pemindaian cepat.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-warning mr-2">Line</span>
            <a href="#tvsmBody" class="font-weight-bold">Target vs Sales (Bulanan - 1 Tahun)</a> —
            perbandingan target dan realisasi penjualan per bulan. Filter:
            <em>scope</em> (Global/Jakarta/Makassar/Surabaya) & <em>tahun</em>. Tooltips menampilkan nilai rupiah.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-success mr-2">Donut</span>
            <a href="#donutBody" class="font-weight-bold">Sales Achievement vs Target</a> —
            ringkasan pencapaian terhadap target dalam periode terpilih.
            Terdiri dari: <em>Global Achievement</em>, <em>All Branch Target (Plan)</em>, dan
            <em>Achievement per Branch</em>. Caption menampilkan persentase & nominal
            <code>Achieved/Target</code>.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-primary mr-2">Bar</span>
            <a href="#svtPctBody" class="font-weight-bold">Achievement vs Target per Branch (Monthly %)</a> —
            persentase pencapaian per cabang tiap bulan dalam setahun (filter tahun).
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-info mr-2">Line</span>
            <a href="#ordersMonthlyBody" class="font-weight-bold">Trend Orders Bulanan (YTD)</a> —
            dua seri: <em>Jumlah Order</em> & <em>Nominal Order</em> (sumbu ganda).
            Filter: cabang & rentang tanggal.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-dark mr-2">Bar + Line</span>
            <a href="#salesPerfBody" class="font-weight-bold">Sales Performance</a> —
            (1) Bar: distribusi Cold/Warm/Hot/Deal per sales; (2) Line: tren %
            achievement untuk Top 3/pilihan sales. Filter: cabang & periode.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Bar</span>
            <a href="#group3Body" class="font-weight-bold">Lead Overview</a> —
            ringkasan agregat leads pada periode & cabang terpilih (komposisi/fokus funnel).
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Pie</span>
            <strong>Konversi Leads (Cold→Warm & Warm→Hot)</strong> —
            dua pie chart yang menunjukkan rasio konversi antar level kualitas leads.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Bar</span>
            <a href="#group5Body" class="font-weight-bold">Jumlah Leads Total</a> —
            total leads per status (Cold/Warm/Hot) pada periode & cabang terpilih.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Bar</span>
            <a href="#group6Body" class="font-weight-bold">Jumlah Quotation</a> —
            total quotation per status (Review/Published/Rejected) dengan filter cabang & tanggal.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Bar</span>
            <a href="#group7Body" class="font-weight-bold">Leads Berdasarkan Source</a> —
            tiga bagian (Cold/Warm/Hot) yang menampilkan jumlah leads per sumber masuk.
            Filter cabang & periode; cocok untuk evaluasi efektivitas kanal akuisisi.
          </li>

        </ul>

        <hr class="my-3">

        <div class="small text-muted">
          <strong>Tips cepat:</strong>
          <ul class="mb-0 pl-3">
            <li>Pakai tombol <em>Apply</em> di tiap kartu untuk memuat data sesuai filter.</li>
            <li>Tooltip di chart menampilkan nilai & format (Rp/%). Arahkan kursor ke titik/batang.</li>
            <li>Donut menampilkan <em>Achieved</em> vs <em>Remaining</em> dengan caption persentase.</li>
            <li>Grafik Leads per Branch memiliki garis <em>Target</em> (putus-putus) bila tersedia.</li>
          </ul>
        </div>

      </div>
    </div>
  </div>
</div>


    <div class="col-md-12 mb-2">
        @if ($showOrders)
            @php
                $statusColors = [
                    'draft' => 'secondary',
                    'review' => 'warning',
                    'published' => 'success',
                    'rejected' => 'danger',
                    'expired' => 'dark',
                ];
                $keys = array_keys($quotationStatusStats);
            @endphp

          
            <div class="row justify-content-center">
                @for ($i = 0; $i < 3; $i++)
                    @php
                        $status = $keys[$i];
                        $stats = $quotationStatusStats[$status];
                        $color = $statusColors[$status] ?? 'primary';
                    @endphp
                    <div class="col-md-4 mb-4 d-flex justify-content-center">
                        <div class="card border-left-{{ $color }} shadow h-100 py-2 w-100">
                            <div class="card-body text-center">
                                <div class="text-xs font-weight-bold text-{{ $color }} text-uppercase mb-1">
                                    {{ ucfirst($status) }} Quotations
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                                <div class="text-xs text-gray-700">Rp{{ number_format($stats['amount'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>

        
            <div class="row justify-content-center">
                @for ($i = 3; $i < 5; $i++)
                    @php
                        $status = $keys[$i];
                        $stats = $quotationStatusStats[$status];
                        $color = $statusColors[$status] ?? 'primary';
                    @endphp
                    <div class="col-md-6 mb-4 d-flex justify-content-center">
                        <div class="card border-left-{{ $color }} shadow h-100 py-2 w-100">
                            <div class="card-body text-center">
                                <div class="text-xs font-weight-bold text-{{ $color }} text-uppercase mb-1">
                                    {{ ucfirst($status) }} Quotations
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                                <div class="text-xs text-gray-700">Rp{{ number_format($stats['amount'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        @endif
    </div>

    <div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Sales Achievement vs Target (Donut)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#donutBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="donutBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <input type="date" id="donut_start" class="form-control form-control-sm"
                   value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="donut_end" class="form-control form-control-sm"
                   value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="donut_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div class="row">
          <!-- GLOBAL -->
          <div class="col-lg-3 mb-4">
            <div class="card h-100">
              <div class="card-header py-2">
                <strong>Global Achievement</strong>
              </div>
              <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div style="width: 220px; height: 220px;">
                  <canvas id="donut_global"></canvas>
                </div>
                <div class="mt-2 small text-muted" id="donut_global_caption"></div>
              </div>
            </div>
          </div>

            <div class="col-lg-3 mb-4">
          <div class="card h-100">
  <div class="card-header py-2">
    <strong>All Branch Target (Plan)</strong>
  </div>
  <div class="card-body d-flex flex-column align-items-center justify-content-center">
    <div style="width: 220px; height: 220px;">
      <canvas id="donut_all"></canvas>
    </div>
    <div class="mt-2 small text-muted" id="donut_all_caption"></div>
  </div>
</div>
</div>

          <!-- PER BRANCH -->
          <div class="col-lg-6 mb-4">
            <div class="card h-100">
              <div class="card-header py-2">
                <strong>Achievement per Branch</strong>
              </div>
              <div class="card-body">
                <div id="donut_branch_container" class="row g-3"></div>
              </div>
            </div>
          </div>
        </div> <!-- /row -->
      </div>
    </div>
  </div>
</div>

    <div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Target vs Sales (Bulanan - 1 Tahun)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#tvsmBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="tvsmBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <select id="tvsm_scope" class="form-select form-select-sm">
              <option value="global">Global</option>
              <option value="jakarta">Branch Jakarta</option>
              <option value="makassar">Branch Makassar</option>
              <option value="surabaya">Branch Surabaya</option>
            </select>
          </div>
          <div class="col-md-3">
            <input type="number" id="tvsm_year" class="form-control form-control-sm" value="{{ now()->year }}" min="2000" max="2100">
          </div>
          <div class="col-md-3 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="tvsm_apply">
              <i class="bi bi-graph-up me-1"></i> Apply
            </button>
          </div>
        </div>

        <div style="height: 360px;">
          <canvas id="tvsm_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>




<div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Achievement vs Target per Branch (Monthly %)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#svtPctBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="svtPctBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <input type="number" id="svt_year" class="form-control form-control-sm" value="{{ now()->year }}" min="2000" max="2100">
          </div>
          <div class="col-md-3 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="svt_apply">
              <i class="bi bi-graph-up me-1"></i> Apply
            </button>
          </div>
        </div>
        <div style="height: 360px;">
          <canvas id="svt_percent_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

    {{-- <div class="row"> --}}
        {{-- <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Group 1</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">Line chart with date and branch filters.</p>
                    <ul class="mb-0 ps-3">
                        <li>Target Global &ndash; Achievement Global (All)</li>
                        <li>Target Monthly &ndash; Achievement Monthly</li>
                        <li>Target Branch &ndash; Achievement Branch</li>
                        <li>Target Agent &ndash; Achievement Agent</li>
                        <li>Target Government Project &ndash; Achievement Government Project</li>
                    </ul>
                </div>
            </div>
        </div> --}}

        {{-- <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Group 2</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">Line chart with date and branch filters.</p>
                    <ul class="mb-0 ps-3">
                        <li>Target Branch &ndash; Achievement Branch</li>
                        <li>Target Agent &ndash; Achievement Agent</li>
                        <li>Target Government Project &ndash; Achievement Government Project</li>
                    </ul>
                </div>
            </div>
        </div> --}}

        <div class="col-md-12 mb-4 d-none">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Trend Total Penjualan per Branch</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#branchSalesBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="branchSalesBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            {{-- pilih hingga 3 branch --}}
            <select id="branch_sales_branches" class="form-select form-select-sm select2" multiple>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Pilih maks. 3 branch, kosongkan untuk Top 3 otomatis</small>
          </div>
          <div class="col-md-3">
            <input type="date" id="branch_sales_start" class="form-control form-control-sm"
                   value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="branch_sales_end" class="form-control form-control-sm"
                   value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="branch_sales_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div style="height: 360px;">
          <canvas id="branch_sales_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Cold Leads per Branch (Count & Nominal)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#coldLeadsBranchBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="coldLeadsBranchBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <select id="cl_branch_ids" class="form-select form-select-sm select2" multiple>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Pilih maks. 3 branch, kosongkan untuk Top 3 otomatis</small>
          </div>
          <div class="col-md-3">
            <input type="date" id="cl_start" class="form-control form-control-sm" value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="cl_end" class="form-control form-control-sm" value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="cl_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div class="mb-4" style="height: 320px;">
          <canvas id="cl_count_chart"></canvas>
        </div>
        <div style="height: 320px;">
          <canvas id="cl_amount_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- WARM Leads per Branch -->
<div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Warm Leads per Branch (Count & Nominal)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#warmLeadsBranchBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="warmLeadsBranchBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <select id="wl_branch_ids" class="form-select form-select-sm select2" multiple>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Pilih maks. 3 branch, kosongkan untuk Top 3 otomatis</small>
          </div>
          <div class="col-md-3">
            <input type="date" id="wl_start" class="form-control form-control-sm" value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="wl_end" class="form-control form-control-sm" value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="wl_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div class="mb-4" style="height: 320px;">
          <canvas id="wl_count_chart"></canvas>
        </div>
        <div style="height: 320px;">
          <canvas id="wl_amount_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- HOT Leads per Branch -->
<div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Hot Leads per Branch (Count & Nominal)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#hotLeadsBranchBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="hotLeadsBranchBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <select id="hl_branch_ids" class="form-select form-select-sm select2" multiple>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Pilih maks. 3 branch, kosongkan untuk Top 3 otomatis</small>
          </div>
          <div class="col-md-3">
            <input type="date" id="hl_start" class="form-control form-control-sm" value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="hl_end" class="form-control form-control-sm" value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="hl_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div class="mb-4" style="height: 320px;">
          <canvas id="hl_count_chart"></canvas>
        </div>
        <div style="height: 320px;">
          <canvas id="hl_amount_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Sales Performance</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#salesPerfBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="salesPerfBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <select id="sp_branch" class="form-select form-select-sm select2">
              <option value="">All Branch</option>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <input type="date" id="sp_start" class="form-control form-control-sm" value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="sp_end" class="form-control form-control-sm" value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="sp_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div style="height: 360px;" class="mb-4">
          <canvas id="sp_bar"></canvas>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-md-9">
            <select id="sa_sales_ids" class="form-select form-select-sm select2" multiple>
              @foreach ($salesUsers as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Pilih maks. 3 sales untuk tren Achievement% (kosongkan = Top 3 otomatis)</small>
          </div>
          <div class="col-md-3 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="sa_apply">
              <i class="bi bi-graph-up-arrow me-1"></i> Refresh Trend
            </button>
          </div>
        </div>

        <div style="height: 360px;">
          <canvas id="sa_line"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

        <div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Trend Orders Bulanan (YTD)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ordersMonthlyBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="ordersMonthlyBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <select id="orders_branch" class="form-select form-select-sm select2">
              <option value="">All Branch</option>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                  {{ $branch->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <input type="date" id="orders_start" class="form-control form-control-sm"
                   value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="orders_end" class="form-control form-control-sm"
                   value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="orders_apply">
              <i class="bi bi-search me-1"></i> Apply Filters
            </button>
          </div>
        </div>

        <div style="height: 360px;">
          <canvas id="orders_monthly_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

        <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Lead Overview</h6>
                    <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse"
                        data-bs-target="#group3Body" aria-expanded="true">
                        <i class="fas fa-angle-down"></i>
                    </button>
                </div>
                <div id="group3Body" class="collapse show">
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select id="overview_branch" class="form-select form-select-sm select2">
                                    <option value="">All Branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="overview_start" class="form-control form-control-sm"
                                    value="{{ $defaultStart }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="overview_end" class="form-control form-control-sm"
                                    value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="overview_apply">
                                    <i class="bi bi-search me-1"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                        <div style="height: 300px;">
                            <canvas id="overview_chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Konversi Leads</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Konversi Cold to Warm</h6>
                                </div>
                                <div class="card-body">
                                    <div style="height: 300px;">
                                        <canvas id="cw_chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Konversi Warm to Hot</h6>
                                </div>
                                <div class="card-body">
                                    <div style="height: 300px;">
                                        <canvas id="wh_chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Leads Total</h6>
                    <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse"
                        data-bs-target="#group5Body" aria-expanded="true">
                        <i class="fas fa-angle-down"></i>
                    </button>
                </div>
                <div id="group5Body" class="collapse show">
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select id="lead_total_branch" class="form-select form-select-sm select2">
                                    <option value="">All Branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="lead_total_start" class="form-control form-control-sm"
                                    value="{{ $defaultStart }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="lead_total_end" class="form-control form-control-sm"
                                    value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="lead_total_apply">
                                    <i class="bi bi-search me-1"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                        <div style="height: 300px;">
                            <canvas id="lead_total_chart"></canvas>
                        </div>
                    </div> <!-- end card-body -->
                </div>
            </div>
        </div>

        <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Quotation</h6>
                    <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse"
                        data-bs-target="#group6Body" aria-expanded="true">
                        <i class="fas fa-angle-down"></i>
                    </button>
                </div>
                <div id="group6Body" class="collapse show">
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select id="quotation_branch" class="form-select form-select-sm select2">
                                    <option value="">All Branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ $currentBranchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="quotation_start" class="form-control form-control-sm"
                                    value="{{ $defaultStart }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="quotation_end" class="form-control form-control-sm"
                                    value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="quotation_apply">
                                    <i class="bi bi-search me-1"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                        <div style="height: 300px;">
                            <canvas id="quotation_chart"></canvas>
                        </div>
                    </div> <!-- end card-body -->
                </div>
            </div>
        </div>

        <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Leads Masuk Berdasarkan Source</h6>
                    <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse"
                        data-bs-target="#group7Body" aria-expanded="true">
                        <i class="fas fa-angle-down"></i>
                    </button>
                </div>
                <div id="group7Body" class="collapse show">
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Leads Masuk Berdasarkan Source -
                                        Cold</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-3">
                                            <select id="cold_branch" class="form-select form-select-sm select2">
                                                <option value="">All Branch</option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}"
                                                        {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="cold_start" class="form-control form-control-sm"
                                                value="{{ $defaultStart }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="cold_end" class="form-control form-control-sm"
                                                value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                id="cold_apply">
                                                <i class="bi bi-search me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                    <div style="height: 300px;">
                                        <canvas id="cold_chart"></canvas>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Leads Masuk Berdasarkan Source -
                                        Warm</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-3">
                                            <select id="warm_branch" class="form-select form-select-sm select2">
                                                <option value="">All Branch</option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}"
                                                        {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="warm_start" class="form-control form-control-sm"
                                                value="{{ $defaultStart }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="warm_end" class="form-control form-control-sm"
                                                value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                id="warm_apply">
                                                <i class="bi bi-search me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                    <div style="height: 300px;">
                                        <canvas id="warm_chart"></canvas>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Leads Masuk Berdasarkan Source -
                                        Hot</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-3">
                                            <select id="hot_branch" class="form-select form-select-sm select2">
                                                <option value="">All Branch</option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}"
                                                        {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="hot_start" class="form-control form-control-sm"
                                                value="{{ $defaultStart }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="hot_end" class="form-control form-control-sm"
                                                value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                id="hot_apply">
                                                <i class="bi bi-search me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                    <div style="height: 300px;">
                                        <canvas id="hot_chart"></canvas>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div> <!-- end card-body -->
                </div>
            </div>
        </div>
    @endsection

    @section('scripts')
        @parent
        <script src="{{ asset('sb-admin-2/vendor/chart.js/Chart.min.js') }}"></script>
        <script>
          let svtPercentChart;

function loadAchievementMonthlyPercent() {
  const params = { year: $('#svt_year').val() };
  $.post('{{ route('dashboard.sales-achievement-monthly-percent') }}', params, function(res){
    const labels = res.labels || [];
    const datasets = (res.datasets || []).map((d,i)=>({
      label: d.label,
      data:  d.data,
      backgroundColor: d.color || ['#4e73df','#e74a3b','#1cc88a'][i % 3]
    }));

    const ctx = document.getElementById('svt_percent_chart').getContext('2d');
    if (svtPercentChart) svtPercentChart.destroy();
    svtPercentChart = new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets },
      options: {
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
        tooltips: {
          mode: 'index', intersect: false,
          callbacks: {
            label: (t, data) => {
              const ds = data.datasets[t.datasetIndex];
              const v  = typeof t.yLabel === 'string' ? parseFloat(t.yLabel) : t.yLabel;
              return ds.label + ': ' + (v ? v.toFixed(2) : '0.00') + '%';
            }
          }
        },
        scales: {
          xAxes: [{ gridLines: { display:false } }],
          yAxes: [{
            ticks: {
              beginAtZero:true,
              callback: v => (v ? v.toFixed(0) : 0) + '%'
            }
          }]
        }
      }
    });
  });
}

      let tvsmChart;
function loadTargetVsSalesMonthly() {
  const params = {
    scope: $('#tvsm_scope').val(),
    year:  $('#tvsm_year').val()
  };

  $.post('{{ route('dashboard.target-vs-sales-monthly') }}', params, function(res){
    const labels = res.labels || [];
    const series = res.series || [];
    const ctx = document.getElementById('tvsm_chart').getContext('2d');

    if (tvsmChart) tvsmChart.destroy();

    // siapkan datasets dinamis (2 atau 3 seri)
    const datasets = [];

    if (series[0]) {
      datasets.push({
        label: series[0].label || 'Target',
        data: series[0].data || [],
        borderColor: '#f6c23e',
        backgroundColor: 'rgba(0,0,0,0)',
        fill: false,
        lineTension: 0,
        pointRadius: 3,
        borderWidth: 2
      });
    }

    if (series[1]) {
      datasets.push({
        label: series[1].label || 'Sales',
        data: series[1].data || [],
        borderColor: '#4e73df',
        backgroundColor: 'rgba(0,0,0,0)',
        fill: false,
        lineTension: 0,
        pointRadius: 3,
        borderWidth: 2
      });
    }

    // ⬇️ seri baru: All Branch Target (jika ada)
    if (series[2]) {
      datasets.push({
        label: series[2].label || 'All Branch Target',
        data: series[2].data || [],
        borderColor: '#6c757d',
        backgroundColor: 'rgba(0,0,0,0)',
        fill: false,
        lineTension: 0,
        pointRadius: 0,
        borderWidth: 2,
        borderDash: [6,4]
      });
    }

    tvsmChart = new Chart(ctx, {
      type: 'line',
      data: { labels, datasets },
      options: {
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
        tooltips: {
          mode: 'index', intersect: false,
          callbacks: {
            label: function(tooltipItem, data) {
              const ds = data.datasets[tooltipItem.datasetIndex];
              const val = tooltipItem.yLabel || 0;
              return ds.label + ': Rp' + number_format(val, 0, ',', '.');
            }
          }
        },
        scales: {
          xAxes: [{ gridLines: { display: false } }],
          yAxes: [{
            ticks: {
              beginAtZero: true,
              callback: function(value){ return 'Rp' + number_format(value, 0, ',', '.'); }
            }
          }]
        }
      }
    });
  });
}

            let donutGlobalChart;
            let donutAllChart;
const donutBranchCharts = {};

function renderDonut(ctx, achieved, target) {
  // clamp agar tidak negatif/lebih dari target pada visual
  const inTarget = Math.min(achieved, target);
  const remaining = Math.max(target - inTarget, 0);

  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Achieved', 'Remaining'],
      datasets: [{
        data: [inTarget, remaining],
        backgroundColor: ['#1cc88a', '#e0e0e0'],
        borderWidth: 0
      }]
    },
    options: {
      maintainAspectRatio: false,
      cutoutPercentage: 65,
      legend: { display: false },
      tooltips: {
        callbacks: {
          label: function(t, d) {
            const label = d.labels[t.index] || '';
            const val = d.datasets[0].data[t.index] || 0;
            return label + ': Rp' + number_format(val, 0, ',', '.');
          }
        }
      }
    }
  });
}

function loadSalesAchievementDonuts() {
  const params = {
    start_date: $('#donut_start').val(),
    end_date:   $('#donut_end').val()
  };

  $.post('{{ route('dashboard.sales-achievement-donut') }}', params, function(res){
    // ALL BRANCH (PLAN)
const a = res.all_branch || { achieved:0, target:10000000, percent:0 };
const actx = document.getElementById('donut_all').getContext('2d');
if (donutAllChart) donutAllChart.destroy();
donutAllChart = renderDonut(actx, a.achieved, a.target);
$('#donut_all_caption').text(
  'Achievement: ' + (a.percent ? a.percent.toFixed(2) : '0.00') + '% — ' +
  'Rp' + number_format(a.achieved,0,',','.') + ' / Rp' + number_format(a.target,0,',','.')
);

    // GLOBAL
    const g = res.global || { achieved:0, target:10000000, percent:0 };
    const gctx = document.getElementById('donut_global').getContext('2d');
    if (donutGlobalChart) donutGlobalChart.destroy();
    donutGlobalChart = renderDonut(gctx, g.achieved, g.target);
    $('#donut_global_caption').text(
      'Achievement: ' + (g.percent ? g.percent.toFixed(2) : '0.00') + '% — ' +
      'Rp' + number_format(g.achieved,0,',','.') + ' / Rp' + number_format(g.target,0,',','.')
    );

    // PER BRANCH
    const container = $('#donut_branch_container');
    container.empty();
    // Hapus chart lama
    Object.values(donutBranchCharts).forEach(ch => { try { ch.destroy(); } catch(e){} });
    for (const key in donutBranchCharts) delete donutBranchCharts[key];

    const branches = res.branches || [];
    if (!branches.length) {
      container.append('<div class="col-12 text-muted">Tidak ada data</div>');
      return;
    }

    branches.forEach((b, idx) => {
      const cid = 'donut_branch_' + b.id;
      const col = $(`
        <div class="col-md-4 col-sm-6">
          <div class="d-flex flex-column align-items-center">
            <div style="width:180px;height:180px"><canvas id="${cid}"></canvas></div>
            <div class="mt-2 text-center small">
              <strong>${b.label}</strong><br>
              <span>${(b.percent ? b.percent.toFixed(2) : '0.00')}%</span><br>
              <span>Rp${number_format(b.achieved,0,',','.')} / Rp${number_format(b.target,0,',','.')}</span>
            </div>
          </div>
        </div>
      `);
      container.append(col);

      const ctx = document.getElementById(cid).getContext('2d');
      donutBranchCharts[cid] = renderDonut(ctx, b.achieved, b.target);
    });
  });
}


let spBarChart, saLineChart;

// Bar: Cold/Warm/Hot/Deal per Sales
function loadSalesPerformanceBar() {
  const params = {
    branch_id:  $('#sp_branch').val(),
    start_date: $('#sp_start').val(),
    end_date:   $('#sp_end').val()
  };
  $.post('{{ route('dashboard.sales-performance-bar') }}', params, function(res){
    const labels = res.labels || [];
    const ds = res.datasets || [];
    const ctx = document.getElementById('sp_bar').getContext('2d');
    if (spBarChart) spBarChart.destroy();
    spBarChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: ds.map(d => ({
          label: d.label,
          data: d.data,
          backgroundColor: d.color
        }))
      },
      options: {
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
        tooltips: {
          mode: 'index', intersect: false,
          callbacks: { label: (t, data) => {
            const ds = data.datasets[t.datasetIndex];
            return ds.label + ': ' + number_format(t.yLabel,0,',','.');
          }}
        },
        scales: {
          xAxes: [{ stacked: false, gridLines: { display:false } }],
          yAxes: [{ stacked: false, ticks: { beginAtZero:true, callback: v => number_format(v,0,',','.') } }]
        }
      }
    });
  });
}

// Line: Achievement % per Sales (Top 3 atau pilihan)
function loadSalesAchievementTrend() {
  const params = {
    sales_ids: ($('#sa_sales_ids').val() || []).slice(0,3),
    branch_id: $('#sp_branch').val(), // sinkron dengan filter branch di atas
    start_date: $('#sp_start').val(),
    end_date:   $('#sp_end').val()
  };
  $.post('{{ route('dashboard.sales-achievement-trend') }}', params, function(res){
    const labels = res.labels || [];
    const series = res.series || [];
    const colors = ['#4e73df', '#e74a3b', '#1cc88a', '#f6c23e']; // sampai 4 warna

    const ctx = document.getElementById('sa_line').getContext('2d');
    if (saLineChart) saLineChart.destroy();
    saLineChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: series.map((s,i)=>({
          label: s.label,
          data: s.data,
          borderColor: colors[i % colors.length],
          backgroundColor: 'rgba(0,0,0,0)',
          fill: false,
          lineTension: 0,
          pointRadius: 3
        }))
      },
      options: {
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
        tooltips: {
          mode: 'index', intersect: false,
          callbacks: {
            label: function(t, data) {
              const ds = data.datasets[t.datasetIndex];
              const val = typeof t.yLabel === 'string' ? parseFloat(t.yLabel) : t.yLabel;
              return ds.label + ': ' + (val ? val.toFixed(2) : '0.00') + '%';
            }
          }
        },
        scales: {
          xAxes: [{ gridLines: { display:false } }],
          yAxes: [{
            ticks: {
              beginAtZero:true,
              callback: v => (v ? v.toFixed(0) : 0) + '%'
            }
          }]
        }
      }
    });
  });
}


        const lbCharts = {}; // simpan instance chart per prefix

function loadLeadsBranchTrend(prefix, status) {
  const params = {
    status: status, // 'cold' | 'warm' | 'hot'
    branch_ids: $('#' + prefix + '_branch_ids').val() || [],
    start_date: $('#' + prefix + '_start').val(),
    end_date:   $('#' + prefix + '_end').val()
  };

  $.post('{{ route('dashboard.leads-branch-trend') }}', params, function(res) {
    const labels       = res.labels || [];
    const seriesCount  = res.series_count  || [];
    const seriesAmount = res.series_amount || [];
    const targetCount  = res.target_count  || [];
    const targetAmount = res.target_amount || [];

    const colors = ['#4e73df', '#e74a3b', '#1cc88a']; // branch lines

    // COUNT chart
    const ctx1 = document.getElementById(prefix + '_count_chart').getContext('2d');
    if (lbCharts[prefix + '_count']) lbCharts[prefix + '_count'].destroy();
    const dsCount = seriesCount.map((s, i) => ({
      label: s.label + ' - Leads',
      data: s.data,
      borderColor: colors[i % colors.length],
      backgroundColor: 'rgba(0,0,0,0)',
      fill: false,
      lineTension: 0,
      pointRadius: 3
    }));
    // tambahan 1 line: TARGET
    if (targetCount.length === labels.length) {
      dsCount.push({
        label: 'Target',
        data: targetCount,
        borderColor: '#f6c23e',
        backgroundColor: 'rgba(0,0,0,0)',
        fill: false,
        lineTension: 0,
        pointRadius: 0,
        borderWidth: 2,
        borderDash: [6,4]
      });
    }
    lbCharts[prefix + '_count'] = new Chart(ctx1, {
      type: 'line',
      data: { labels, datasets: dsCount },
      options: {
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
        tooltips: {
          mode: 'index', intersect: false,
          callbacks: {
            label: function(t, d) {
              const ds  = d.datasets[t.datasetIndex];
              const val = t.yLabel;
              return ds.label + ': ' + number_format(val, 0, ',', '.');
            }
          }
        },
        scales: {
          xAxes: [{ gridLines: { display: false } }],
          yAxes: [{ ticks: { beginAtZero: true, callback: v => number_format(v,0,',','.') } }]
        }
      }
    });

    // AMOUNT chart (Rupiah)
    const ctx2 = document.getElementById(prefix + '_amount_chart').getContext('2d');
    if (lbCharts[prefix + '_amount']) lbCharts[prefix + '_amount'].destroy();
    const dsAmt = seriesAmount.map((s, i) => ({
      label: s.label + ' - Nominal',
      data: s.data,
      borderColor: colors[i % colors.length],
      backgroundColor: 'rgba(0,0,0,0)',
      fill: false,
      lineTension: 0,
      pointRadius: 3
    }));
    // tambahan 1 line: TARGET (nominal)
    if (targetAmount.length === labels.length) {
      dsAmt.push({
        label: 'Target',
        data: targetAmount,
        borderColor: '#6c757d',
        backgroundColor: 'rgba(0,0,0,0)',
        fill: false,
        lineTension: 0,
        pointRadius: 0,
        borderWidth: 2,
        borderDash: [6,4]
      });
    }
    lbCharts[prefix + '_amount'] = new Chart(ctx2, {
      type: 'line',
      data: { labels, datasets: dsAmt },
      options: {
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
        tooltips: {
          mode: 'index', intersect: false,
          callbacks: {
            label: function(t, d) {
              const ds  = d.datasets[t.datasetIndex];
              const val = t.yLabel;
              return ds.label + ': Rp' + number_format(val, 0, ',', '.');
            }
          }
        },
        scales: {
          xAxes: [{ gridLines: { display: false } }],
          yAxes: [{ ticks: { beginAtZero: true, callback: v => 'Rp' + number_format(v,0,',','.') } }]
        }
      }
    });
  });
}



            // === Trend Total Penjualan per Branch ===
let branchSalesChart;

function loadBranchSalesTrend() {
    const params = {
        branch_ids: $('#branch_sales_branches').val() || [],   // array of ids
        start_date: $('#branch_sales_start').val(),
        end_date:   $('#branch_sales_end').val()
    };

    $.post('{{ route('dashboard.branch-sales-trend') }}', params, function(res) {
        const labels = res.labels || [];
        const series = res.series || [];

        const ctx = document.getElementById('branch_sales_chart').getContext('2d');
        if (branchSalesChart) branchSalesChart.destroy();

        // palet warna untuk 3 garis
        const colors = ['#4e73df', '#e74a3b', '#1cc88a'];

        branchSalesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: series.map(function(s, idx){
                    return {
                        label: s.label,
                        data: s.data,
                        borderColor: colors[idx % colors.length],
                        backgroundColor: 'rgba(0,0,0,0)',
                        fill: false,
                        lineTension: 0,
                        pointRadius: 3
                    };
                })
            },
            options: {
                maintainAspectRatio: false,
                legend: { position: 'bottom' },
                tooltips: {
                    mode: 'index', intersect: false,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            const ds = data.datasets[tooltipItem.datasetIndex];
                            const val = tooltipItem.yLabel;
                            return ds.label + ': Rp' + number_format(val, 0, ',', '.');
                        }
                    }
                },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value){ return 'Rp' + number_format(value, 0, ',', '.'); }
                        }
                    }]
                }
            }
        });
    });
}



            let ordersMonthlyChart;

function loadOrdersMonthly() {
    const params = {
        branch_id:  $('#orders_branch').val(),
        start_date: $('#orders_start').val(),
        end_date:   $('#orders_end').val()
    };

    $.post('{{ route('dashboard.orders-monthly') }}', params, function(rows) {
        const labels  = rows.map(r => r.label);
        const counts  = rows.map(r => r.count);
        const amounts = rows.map(r => r.amount);

        const ctx = document.getElementById('orders_monthly_chart').getContext('2d');
        if (ordersMonthlyChart) ordersMonthlyChart.destroy();

        ordersMonthlyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Jumlah Order',
                        data: counts,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78,115,223,0.05)',
                        yAxisID: 'yCount',
                        fill: false,
                        lineTension: 0
                    },
                    {
                        label: 'Nominal Order (Rp)',
                        data: amounts,
                        borderColor: '#e74a3b',
                        backgroundColor: 'rgba(231,74,59,0.05)',
                        yAxisID: 'yAmount',
                        fill: false,
                        lineTension: 0
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                legend: { position: 'bottom' },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            const ds = data.datasets[tooltipItem.datasetIndex];
                            const val = tooltipItem.yLabel;
                            if (ds.yAxisID === 'yAmount') {
                                return ds.label + ': Rp' + number_format(val, 0, ',', '.');
                            }
                            return ds.label + ': ' + number_format(val, 0, ',', '.');
                        }
                    }
                },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [
                        {
                            id: 'yCount',
                            position: 'left',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value){ return number_format(value, 0, ',', '.'); }
                            }
                        },
                        {
                            id: 'yAmount',
                            position: 'right',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value){ return 'Rp' + number_format(value, 0, ',', '.'); }
                            },
                            gridLines: { drawOnChartArea: false }
                        }
                    ]
                },
                elements: { point: { radius: 3 } }
            }
        });
    });
}

            Chart.defaults.global.defaultFontFamily = 'Nunito',
                '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
            Chart.defaults.global.defaultFontColor = '#858796';

            function number_format(number, decimals, dec_point, thousands_sep) {
                number = (number + '').replace(',', '').replace(' ', '');
                var n = !isFinite(+number) ? 0 : +number,
                    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                    s = '',
                    toFixedFix = function(n, prec) {
                        var k = Math.pow(10, prec);
                        return '' + Math.round(n * k) / k;
                    };
                s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                if (s[0].length > 3) {
                    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                }
                if ((s[1] || '').length < prec) {
                    s[1] = s[1] || '';
                    s[1] += new Array(prec - s[1].length + 1).join('0');
                }
                return s.join(dec);
            }

          
            function loadProcessFlowMkt5a() {
                const params = {
                    @if(auth()->user()->role?->code === 'branch_manager' || auth()->user()->role?->code === 'sales')
                        branch_id: {{ auth()->user()->branch_id ?? 'null' }},
                    @endif
                };
                
                $.get('/api/dashboard/mkt5a', params)
                    .done(function(response) {
                       
                        $('#aware-qty').text(number_format(response.aware.all_leads_qty, 0, ',', '.'));
                        $('#aware-time').text('ATR ' + formatTime(response.aware.acquisition_time_avg_hours));
                         
                        $('#appeal-qty').text(number_format(response.appeal.meeting_in_qty, 0, ',', '.'));
                        $('#appeal-time').text('ATR ' + formatTime(response.appeal.meeting_time_avg_hours));
                        
                        $('#ask-qty').text(number_format(response.quotation.quotation_in_qty, 0, ',', '.'));
                        $('#ask-time').text('ATR ' + formatTime(response.quotation.quotation_time_avg_hours));

                        $('#act-qty').text(number_format(response.act.invoice_in_qty, 0, ',', '.'));
                        $('#act-time').text('ATR ' + formatTime(response.act.invoice_time_avg_hours));
                    })
                    .fail(function() {
                        $('#aware-qty, #appeal-qty, #ask-qty, #act-qty').text('0');
                        $('#aware-time, #appeal-time, #ask-time, #act-time').text('ATR 00:00:00');
                    });
            }

            function formatTime(hours) {
                if (!hours || hours === 0) return '00:00:00';
                
                const totalSeconds = Math.round(hours * 3600);
                const h = Math.floor(totalSeconds / 3600);
                const m = Math.floor((totalSeconds % 3600) / 60);
                const s = totalSeconds % 60;
                
                return String(h).padStart(2, '0') + ':' + 
                       String(m).padStart(2, '0') + ':' + 
                       String(s).padStart(2, '0');
            }

            $(function() {
                const statusMap = {
                    cold: {{ \App\Models\Leads\LeadStatus::COLD }},
                    warm: {{ \App\Models\Leads\LeadStatus::WARM }},
                    hot: {{ \App\Models\Leads\LeadStatus::HOT }}
                };

                const charts = {};

                loadProcessFlowMkt5a();
                
                $('#svt_apply').on('click', loadAchievementMonthlyPercent);
loadAchievementMonthlyPercent();

// binding & initial load
$('#tvsm_apply').on('click', loadTargetVsSalesMonthly);
loadTargetVsSalesMonthly();


                // binding & initial
$('#donut_apply').on('click', loadSalesAchievementDonuts);
loadSalesAchievementDonuts();
                // bindings
$('#sp_apply').on('click', function(){
  loadSalesPerformanceBar();
  loadSalesAchievementTrend();
});
$('#sa_apply').on('click', loadSalesAchievementTrend);

// initial load
loadSalesPerformanceBar();
loadSalesAchievementTrend();

// === Binding tombol Apply & initial load ===
$('#cl_apply').on('click', function(){ loadLeadsBranchTrend('cl','cold'); });
$('#wl_apply').on('click', function(){ loadLeadsBranchTrend('wl','warm'); });
$('#hl_apply').on('click', function(){ loadLeadsBranchTrend('hl','hot'); });

// initial load
loadLeadsBranchTrend('cl','cold');
loadLeadsBranchTrend('wl','warm');
loadLeadsBranchTrend('hl','hot');

                $('#branch_sales_apply').on('click', loadBranchSalesTrend);
loadBranchSalesTrend(); // initial (YTD / Top 3)

                $('#orders_apply').on('click', loadOrdersMonthly);
                loadOrdersMonthly();

                function renderChart(ctx, data, label = 'Jumlah Leads', type = 'bar') {
                    let labels = data.map(d => d.source);
                    let values = data.map(d => d.total);
                    if (labels.length === 0) {
                        labels = ['No Data'];
                        values = [0];
                    }
                    const backgroundColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
                    const config = {
                        type: type,
                        data: {
                            labels: labels,
                            datasets: [{
                                label: label,
                                backgroundColor: type === 'pie' ? backgroundColors.slice(0, values.length) : '#4e73df',
                                data: values
                            }]
                        },
                        options: {
                            maintainAspectRatio: false
                        }
                    };

                    if (type === 'bar') {
                        config.options.scales = {
                            yAxes: [{
                                ticks: { beginAtZero: true }
                            }]
                        };
                        config.options.legend = { display: false };
                    } else if (type === 'pie') {
                        config.options.legend = { position: 'bottom' };
                        config.options.tooltips = {
                            callbacks: {
                                label: function(tooltipItem, data) {
                                    const dataset = data.datasets[tooltipItem.datasetIndex];
                                    const total = dataset.data.reduce(function(prev, next) { return prev + next; }, 0);
                                    const value = dataset.data[tooltipItem.index];
                                    const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                                    return data.labels[tooltipItem.index] + ': ' + value + ' (' + percent + '%)';
                                }
                            }
                        };
                    }

                    return new Chart(ctx, config);
                }

                function loadChart(prefix) {
                    const params = {
                        status_id: statusMap[prefix],
                        branch_id: $('#' + prefix + '_branch').val(),
                        start_date: $('#' + prefix + '_start').val(),
                        end_date: $('#' + prefix + '_end').val()
                    };
                    $.post('{{ route('dashboard.group7.leads-source') }}', params, function(data) {
                        const ctx = document.getElementById(prefix + '_chart').getContext('2d');
                        if (charts[prefix]) {
                            charts[prefix].destroy();
                        }
                        charts[prefix] = renderChart(ctx, data);
                    });
                }

                function loadOverviewChart() {
                    const params = {
                        branch_id: $('#overview_branch').val(),
                        start_date: $('#overview_start').val(),
                        end_date: $('#overview_end').val()
                    };
                    $.post('{{ route('dashboard.group3.lead-overview') }}', params, function(data) {
                        const ctx = document.getElementById('overview_chart').getContext('2d');
                        if (charts.overview) {
                            charts.overview.destroy();
                        }
                        charts.overview = renderChart(ctx, data);
                    });
                }

                function loadLeadTotals() {
                    const baseParams = {
                        branch_id: $('#lead_total_branch').val(),
                        start_date: $('#lead_total_start').val(),
                        end_date: $('#lead_total_end').val()
                    };

                    const statuses = ['cold', 'warm', 'hot'];
                    const requests = statuses.map(function(status) {
                        return $.post('{{ route('dashboard.group5.lead-total') }}',
                            Object.assign({
                                status_id: statusMap[status]
                            }, baseParams));
                    });

                    $.when.apply($, requests).done(function() {
                        const responses = arguments.length === 1 ? [arguments] : arguments;
                        const data = statuses.map(function(status, idx) {
                            return {
                                source: status.charAt(0).toUpperCase() + status.slice(1),
                                total: responses[idx][0].total
                            };
                        });
                        const ctx = document.getElementById('lead_total_chart').getContext('2d');
                        if (charts.lead_total) {
                            charts.lead_total.destroy();
                        }
                        charts.lead_total = renderChart(ctx, data);
                    });
                }

                ['cold', 'warm', 'hot'].forEach(function(prefix) {
                    $('#' + prefix + '_apply').on('click', function() {
                        loadChart(prefix);
                    });
                    loadChart(prefix);
                });

                function loadColdWarmChart() {
                    $.post('{{ route('dashboard.group4.cold-warm') }}', function(data) {
                        const ctx = document.getElementById('cw_chart').getContext('2d');
                        if (charts.cw) {
                            charts.cw.destroy();
                        }
                        charts.cw = renderChart(ctx, data, 'Cold to Warm', 'pie');
                    });
                }

                function loadWarmHotChart() {
                    $.post('{{ route('dashboard.group4.warm-hot') }}', function(data) {
                        const ctx = document.getElementById('wh_chart').getContext('2d');
                        if (charts.wh) {
                            charts.wh.destroy();
                        }
                        charts.wh = renderChart(ctx, data, 'Warm to Hot', 'pie');
                    });
                }

                loadColdWarmChart();
                loadWarmHotChart();

                $('#overview_apply').on('click', loadOverviewChart);
                loadOverviewChart();

                $('#lead_total_apply').on('click', loadLeadTotals);
                loadLeadTotals();
                const quotationStatuses = ['review', 'published', 'rejected'];
                let quotationChart;

                function loadQuotationStatusChart() {
                    const baseParams = {
                        branch_id: $('#quotation_branch').val(),
                        start_date: $('#quotation_start').val(),
                        end_date: $('#quotation_end').val()
                    };

                    const requests = quotationStatuses.map(function(status) {
                        return $.post('{{ route('dashboard.group6.quotation-status') }}',
                            Object.assign({
                                status: status
                            }, baseParams));
                    });

                    $.when.apply($, requests).done(function() {
                        const responses = arguments.length === 1 ? [arguments] : arguments;
                        const data = quotationStatuses.map(function(status, idx) {
                            return {
                                source: status.charAt(0).toUpperCase() + status.slice(1),
                                total: responses[idx][0].total
                            };
                        });

                        const ctx = document.getElementById('quotation_chart').getContext('2d');
                        if (quotationChart) {
                            quotationChart.destroy();
                        }
                        quotationChart = renderChart(ctx, data, 'Jumlah Quotation');
                    });
                }

                $('#quotation_apply').on('click', loadQuotationStatusChart);
                loadQuotationStatusChart();

                function loadProcessFlow() {
                    const branchId = {{ Auth::user()->branch_id ?? 'null' }};
                    const apiUrl = '/api/dashboard/mkt5a' + (branchId ? '?branch_id=' + branchId : '');
                    
                    $.get(apiUrl, function(response) {
                        if (response.success) {
                            renderProcessFlow(response.data);
                        }
                    }).fail(function() {
                        console.error('Failed to load process flow data');
                        renderProcessFlowError();
                    });
                }

                function renderProcessFlow(data) {
                    const container = $('#processFlowContainer');
                    container.empty();

                    // First row - Count values
                    const firstRow = $('<div class="row mb-3"></div>');
                    data.forEach(function(item, index) {
                        const cardCol = $(`
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                                <div class="process-flow-card">
                                    <div class="process-flow-icon">
                                        <i class="${item.icon}"></i>
                                    </div>
                                    <div class="process-flow-content">
                                        <div class="process-flow-title">${item.title}</div>
                                        <div class="process-flow-count">${number_format(item.count, 0, ',', '.')}</div>
                                        <div class="process-flow-atr">ATR ${item.atr}</div>
                                    </div>
                                </div>
                            </div>
                        `);
                        firstRow.append(cardCol);
                    });

                    const secondRow = $('<div class="row"></div>');
                    data.forEach(function(item, index) {
                        const cardCol = $(`
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                                <div class="process-flow-card">
                                    <div class="process-flow-icon">
                                        <i class="${item.icon}"></i>
                                    </div>
                                    <div class="process-flow-content">
                                        <div class="process-flow-title">${item.title}</div>
                                        <div class="process-flow-count">${item.percentage}%</div>
                                        <div class="process-flow-atr">ATR ${item.atr}</div>
                                    </div>
                                </div>
                            </div>
                        `);
                        secondRow.append(cardCol);
                    });

                    container.append(firstRow);
                    container.append(secondRow);
                }

                function renderProcessFlowError() {
                    const container = $('#processFlowContainer');
                    container.html(`
                        <div class="col-12">
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                Unable to load process flow data. Please try again later.
                            </div>
                        </div>
                    `);
                }

                loadProcessFlow();
                setInterval(loadProcessFlow, 300000);
            });
        </script>
    @endsection
