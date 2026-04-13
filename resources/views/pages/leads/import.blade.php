@extends('layouts.app')

@section('content')
@php
  $stepGuides = [
    1 => [
      'category' => 'Tentukan jenis lead',
      'description' => 'Jika lead masih awal, gunakan bagian biru dan isi status_stage = cold. Jika lead sudah warm, isi bagian biru lalu lengkapi bagian kuning dan isi status_stage = warm.',
    ],
    2 => [
      'category' => 'Isi kolom wajib',
      'description' => 'Kolom bertanda * wajib diisi: source_id* dan segment_id*.',
    ],
    3 => [
      'category' => 'Gunakan dropdown / referensi',
      'description' => 'Pilih nilai dari dropdown jika tersedia. Untuk kolom berbentuk ID, ambil angka / kode dari sheet referensi.',
    ],
    4 => [
      'category' => 'Jangan ubah header',
      'description' => 'Isi data mulai baris 2. Jangan mengubah nama kolom pada baris 1.',
    ],
    5 => [
      'category' => 'Cek format data',
      'description' => 'created_at isi dengan format tanggal seperti contoh: 2026-04-10. Untuk Amount isi angka tanpa simbol.',
    ],
    6 => [
      'category' => 'Simpan lalu import',
      'description' => 'Setelah semua data benar, simpan file Excel lalu lanjutkan proses import ke sistem.',
    ],
  ];

  $referenceGuides = [
    'source_id' => [
      'sheet' => 'Lead Sources',
      'note' => 'Pilih ID source lead',
    ],
    'segment_id' => [
      'sheet' => 'Lead Segments',
      'note' => 'Pilih ID segment',
    ],
    'industry_id' => [
      'sheet' => 'Industries',
      'note' => 'Pilih ID industri',
    ],
    'region_id' => [
      'sheet' => 'Regions',
      'note' => 'Pilih ID region',
    ],
    'lead_position' => [
      'sheet' => 'Jabatans',
      'note' => 'Pilih ID jabatan',
    ],
    'nip_sales' => [
      'sheet' => 'Sales NIP',
      'note' => 'Gunakan NIP sales',
    ],
    'Meeting Type' => [
      'sheet' => '_Lookups / dropdown',
      'note' => 'Pilih dari dropdown',
    ],
    'Expense Type' => [
      'sheet' => '_Lookups / dropdown',
      'note' => 'Pilih dari dropdown',
    ],
  ];

  $importantNotes = [
    1 => 'Kolom dengan dropdown sebaiknya dipilih dari list, jangan diketik manual jika opsinya sudah tersedia.',
    2 => 'city_id tidak memiliki referensi di workbook ini, jadi isi sesuai ID kota dari sistem / master data internal.',
    3 => 'Sheet referensi seperti Lead Sources, Lead Segments, Industries, Regions, Sales NIP, dan Jabatans dipakai untuk mencari ID yang benar.',
  ];
@endphp
<section class="min-h-screen sm:text-xs! lg:text-sm!">
  {{-- HEADER PAGES --}}
  <div class="pt-4">
      <div class="flex items-center gap-2">
          <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path
                  d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
                  fill="#115640" />
          </svg>
          <h1 class="text-[#115640] font-semibold lg:text-2xl text-lg">Leads</h1>
      </div>
      <p class="mt-1 text-[#115640] lg:text-lg text-sm">Import Leads</p>
  </div>

  {{-- GUIDES --}}
  <div class="mt-4 w-full bg-white border border-[#D9D9D9] rounded-lg">
    <div class="flex items-center justify-between p-3 border-b border-b-[#D9D9D9]">
      <h1 class="font-bold text-[#1E1E1E] text-base">Download Template</h1>
      <a href="{{ route('leads.import.template') }}" class="bg-[#115640] rounded-lg flex justify-center items-center p-2 gap-1 text-white">
        <x-icon.download/>
        Download Template
      </a>
    </div>
    <div class="p-3">
      <div class="w-full border border-[#D9D9D9] rounded-lg overflow-hidden text-[#1E1E1E]">
        <div id="triggerOpenGuide" class="guide-toggle flex justify-between items-center p-3 cursor-pointer font-bold bg-[#115640] text-white" data-target="#mainGuide">
          <span>
            Petunjuk untuk mengisi template import leads
          </span>
          <i class="fas fa-chevron-right transform transition-transform duration-300"></i>
        </div>
        <div id="mainGuide" class="guide-panel border-t border-[#D9D9D9]" style="display: none;">
            {{-- COLOR GUIDE --}}
            <div class="guide-toggle cursor-pointer bg-white text-[#1E1E1E] flex items-center justify-between p-3" data-target="#colorGuide">
              <h1 class="font-bold">Keterangan Warna</h1>
              <i class="fas fa-chevron-right transform transition-transform duration-300"></i>
            </div>
            <div id="colorGuide" class="guide-panel" style="display: none;">
              <table class="w-full border-collapse">
                  <thead class="text-[#1E1E1E] bg-[#F5F5F5]">
                      <tr class="border-t border-t-[#D9D9D9]">
                          <th class="p-1 lg:p-3 border-r border-r-[#D9D9D9]">Warna</th>
                          <th class="p-1 lg:p-3">Penjelasan</th>
                      </tr>
                  </thead>

                  <tbody>
                      <tr class="border-t border-t-[#D9D9D9]">
                          <td class="p-1 lg:p-3 bg-[#538DD5] text-white border-r border-r-[#D9D9D9]">
                              Biru
                          </td>
                          <td class="p-1 lg:p-3">
                              Data lead utama / cold lead
                          </td>
                      </tr>
                      <tr class="border-t border-t-[#D9D9D9]">
                          <td class="p-1 lg:p-3 bg-[#FFF1C2] border-r border-r-[#D9D9D9]">
                              Kuning
                          </td>
                          <td class="p-1 lg:p-3">
                              Tambahan data untuk warm lead
                          </td>
                      </tr>
                  </tbody>
              </table>
            </div>

            {{-- STEPS GUIDE --}}
            <div class="guide-toggle border-t border-t-[#D9D9D9] cursor-pointer bg-white text-[#1E1E1E] flex items-center justify-between p-3" data-target="#stepGuide">
              <h1 class="font-bold">Langkah Import</h1>
              <i class="fas fa-chevron-right transform transition-transform duration-300"></i>
            </div>
            <div id="stepGuide" class="guide-panel" style="display: none;">
              <table class="w-full border-collapse">
                  <thead class="text-[#1E1E1E] bg-[#F5F5F5]">
                      <tr class="border-t border-t-[#D9D9D9]">
                          <th class="p-1 lg:p-3 border-r border-r-[#D9D9D9] text-center">Steps</th>
                          <th class="p-1 lg:p-3 border-r border-r-[#D9D9D9]">Kategori</th>
                          <th class="p-1 lg:p-3">Penjelasan</th>
                      </tr>
                  </thead>

                  <tbody class="text-[#1E1E1E]">
                      @foreach($stepGuides as $step => $guide)
                        <tr class="border-t border-t-[#D9D9D9]">
                            <td class="p-1 lg:p-3 border-r border-r-[#D9D9D9] text-center">
                                {{ $step }}
                            </td>
                            <td class="p-1 lg:p-3 border-r border-r-[#D9D9D9]">
                                {{ $guide['category'] }}
                            </td>
                            <td class="p-1 lg:p-3">
                                {{ $guide['description'] }}
                            </td>
                        </tr>
                      @endforeach
                  </tbody>
              </table>
            </div>

            {{-- REFERENCES GUIDE --}}
            <div class="guide-toggle border-t border-t-[#D9D9D9] cursor-pointer bg-white text-[#1E1E1E] flex items-center justify-between p-3" data-target="#referencesGuide">
              <h1 class="font-bold">Referensi Sheet</h1>
              <i class="fas fa-chevron-right transform transition-transform duration-300"></i>
            </div>
            <div id="referencesGuide" class="guide-panel" style="display: none;">
              <table class="w-full border-collapse">
                  <thead class="text-[#1E1E1E] bg-[#F5F5F5]">
                      <tr class="border-t border-t-[#D9D9D9]">
                          <th class="p-1 lg:p-3 border-r border-r-[#D9D9D9] text-center">Kolom di Import</th>
                          <th class="p-1 lg:p-3 border-r border-r-[#D9D9D9]">Ambil dari sheet</th>
                          <th class="p-1 lg:p-3">Catatan</th>
                      </tr>
                  </thead>

                  <tbody class="text-[#1E1E1E]">
                      @foreach($referenceGuides as $column => $guide)
                        <tr class="border-t border-t-[#D9D9D9]">
                            <td class="p-1 lg:p-3 border-r border-r-[#D9D9D9] text-center">
                                {{ $column }}
                            </td>
                            <td class="p-1 lg:p-3 border-r border-r-[#D9D9D9]">
                                {{ $guide['sheet'] }}
                            </td>
                            <td class="p-1 lg:p-3">
                                {{ $guide['note'] }}
                            </td>
                        </tr>
                      @endforeach
                  </tbody>
              </table>
            </div>

            {{-- NOTES --}}
            <div class="guide-toggle border-t border-t-[#D9D9D9] cursor-pointer bg-white text-[#1E1E1E] flex items-center justify-between p-3" data-target="#notesGuide">
              <h1 class="font-bold">Catatan Penting</h1>
              <i class="fas fa-chevron-right transform transition-transform duration-300"></i>
            </div>
            <div id="notesGuide" class="guide-panel" style="display: none;">
              <table class="w-full border-collapse">
                  <tbody class="text-[#1E1E1E]">
                      @foreach($importantNotes as $note)
                        <tr class="border-t border-t-[#D9D9D9]">
                            <td class="p-1 lg:p-3">
                              {{ $note }}
                            </td>
                        </tr>
                      @endforeach
                  </tbody>
              </table>
            </div>
        </div>
      </div>
    </div>
  </div>

  {{-- UPLOAD TEMPLATE --}}
  <div class="mt-4 w-full bg-white border border-[#D9D9D9] rounded-lg">
    <h1 class="font-bold text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9] text-base">Upload Leads File</h1>
    <div class="p-3">
      <div class="custom-file">
        <form id="uploadForm" action="{{ route('leads.import.preview') }}" method="POST"
          enctype="multipart/form-data">
          @csrf
          
          <input type="file"
            class="custom-file-input cursor-pointer w-full border border-[#D9D9D9] focus:outline-none!"
            id="importFile" name="import_file" accept=".xlsx,.csv" required>
          <label class="custom-file-label" for="importFile">Choose Excel or CSV File(s)...</label>
          <div class="flex justify-end my-5">
            <button type="submit" class="cursor-pointer p-2 lg:px-3 lg:py-2 bg-[#115640] border border-[#115640] rounded-lg text-white font-semibold">Submit Preview</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @isset($rows)
  {{-- RESULT PREVIEW TEMPLATE --}}
  <div class="mt-4 w-full bg-white border border-[#D9D9D9] rounded-lg">
    <h1 class="font-bold text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9] text-base">Preview Results</h1>
      <div>
        <form id="submitForm" method="POST" action="{{ route('leads.import.store') }}">
          @csrf
          {{-- NEWER TABLE IMPORTS --}}
          @php
            $previewStageTabs = $previewTableConfig['tabs'] ?? [];
            $defaultPreviewTab = $previewTableConfig['default_tab'] ?? 'cold';
          @endphp
          <div class="bg-white lg:flex justify-between items-center border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">
            {{-- SEARCH TABLES --}}
            <div class="xl:w-[10%]! border border-gray-300 rounded-lg lg:flex! items-center hidden">
                <div class="px-2">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z"
                            fill="#6B7786" />
                    </svg>
                </div>
                <input id="searchInput" type="text" placeholder="Search"
                    class="w-full px-3 py-2 border-none focus:outline-[#115640] " />
            </div>
            {{-- NAVIGATION STATUS TABLES --}}
            <div class="xl:w-[80%]! gap-3 flex items-center">
                <div class="w-full border border-[#D5D5D5] rounded-lg grid grid-cols-2">
                    @foreach ($previewStageTabs as $tab => $tabConfig)
                    @php
                      $isActiveTab = $tab === $defaultPreviewTab;
                    @endphp
                    {{-- NAVIGATION STATUS --}}
                    <div data-tab="{{ $tab }}"
                        class="text-center cursor-pointer py-2 h-full nav-leads {{ $loop->last ? '' : 'border-r border-r-[#D5D5D5]' }} {{ $isActiveTab ? 'active-nav' : '' }}">
                        <p class="{{ $isActiveTab ? 'text-[#1E1E1E]' : 'text-[#1E1E1E]' }}">
                          {{ $tabConfig['label'] ?? ucfirst($tab) }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="xl:w-[10%]! cursor-pointer bg-[#115640] text-white rounded-lg hidden lg:inline!">
              <div class="w-full flex items-center justify-center text-center gap-3 px-3 py-2 text-white">
                <x-icon.small-circle-check/>
                <span>Submit Results</span>
              </div>
            </button>
          </div>

          @foreach($previewStageTabs as $tab => $tabConfig)
            @php
              $isActiveTab = $tab === $defaultPreviewTab;
            @endphp
            <div data-tab-container="{{ $tab }}" class="{{ $isActiveTab ? 'block' : 'hidden' }}">
                <div class="overflow-x-scroll">
                    <table id="{{ $tab }}ImportLeads" class="w-full bg-white rounded-br-lg rounded-bl-lg">
                        {{-- HEADER TABLE --}}
                        <thead id="{{ $tab }}Head" class="text-[#1E1E1E]">
                          <tr class="border-b border-b-[#D9D9D9]">
                            @foreach(($tabConfig['headers'] ?? []) as $header)
                              <th class="text-sm p-2 lg:p-3 text-nowrap">
                                {{ $header }}
                              </th>
                            @endforeach
                          </tr>
                        </thead>

                        {{-- BODY TABLE --}}
                        <tbody id="{{ $tab }}Body" class="text-[#1E1E1E]">
                          @if($tab === 'cold')
                            @foreach(($tabConfig['rows'] ?? []) as $row)
                              <tr
                                class="{{ $row['row_class'] . 'border-b border-b-[#D9D9D9]' ?? '' }}"
                                data-group-key="{{ $row['group_key'] ?? '' }}"
                                data-preview-index="{{ $row['preview_index'] ?? '' }}">
                                <input type="hidden" name="rows[{{ $row['preview_index'] }}][group_key]" value="{{ $row['group_key'] ?? '' }}">
                                <td class="p-2 lg:p-3 align-middle">{{ $row['group_index'] ?? '' }}</td>
                                <td class="p-2 lg:p-3">
                                  <select name="rows[{{ $row['preview_index'] }}][source_id]" class="form-control form-control-sm">
                                    <option value="">--</option>
                                    @foreach($sources as $s)
                                      <option value="{{ $s->id }}" {{ (string) $s->id === (string) ($row['source_id'] ?? '') ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                  </select>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <select name="rows[{{ $row['preview_index'] }}][segment_id]" class="form-control form-control-sm">
                                    <option value="">--</option>
                                    @foreach($segments as $seg)
                                      <option value="{{ $seg->id }}" {{ (string) $seg->id === (string) ($row['segment_id'] ?? '') ? 'selected' : '' }}>{{ $seg->name }}</option>
                                    @endforeach
                                  </select>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <select name="rows[{{ $row['preview_index'] }}][region_id]" class="form-control form-control-sm">
                                    <option value="">All Region</option>
                                    @foreach($regions as $r)
                                      <option value="{{ $r->id }}" {{ (string) $r->id === (string) ($row['region_id'] ?? '') ? 'selected' : '' }}>{{ $r->name }}</option>
                                    @endforeach
                                  </select>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" name="rows[{{ $row['preview_index'] }}][lead_name]" value="{{ $row['lead_name'] ?? '' }}" class="form-control form-control-sm">
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" name="rows[{{ $row['preview_index'] }}][lead_email]" value="{{ $row['lead_email'] ?? '' }}" class="form-control form-control-sm">
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" name="rows[{{ $row['preview_index'] }}][lead_phone]" value="{{ $row['lead_phone'] ?? '' }}" class="form-control form-control-sm">
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" name="rows[{{ $row['preview_index'] }}][lead_needs]" value="{{ $row['lead_needs'] ?? '' }}" class="form-control form-control-sm">
                                </td>
                                <td class="p-2 lg:p-3">
                                  <select name="rows[{{ $row['preview_index'] }}][nip_sales]" class="form-control form-control-sm">
                                    <option value="">--</option>
                                    @foreach($users as $u)
                                      <option value="{{ $u->nip }}" {{ (string) $u->nip === (string) ($row['nip_sales'] ?? '') ? 'selected' : '' }}>{{ $u->nip }} - {{ $u->name }}</option>
                                    @endforeach
                                  </select>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" name="rows[{{ $row['preview_index'] }}][published_at]" value="{{ $row['published_at'] ?? '' }}" class="form-control form-control-sm">
                                </td>
                                <td class="p-2 lg:p-3">
                                  @php
                                    $stage = $row['status_stage'] ?? '';
                                  @endphp
                                  <select name="rows[{{ $row['preview_index'] }}][status_stage]" class="form-control form-control-sm">
                                    <option value="" {{ $stage === '' ? 'selected' : '' }}>--</option>
                                    <option value="cold" {{ $stage === 'cold' ? 'selected' : '' }}>cold</option>
                                    <option value="warm" {{ $stage === 'warm' ? 'selected' : '' }}>warm</option>
                                    <option value="hot" {{ $stage === 'hot' ? 'selected' : '' }}>hot</option>
                                    <option value="deal" {{ $stage === 'deal' ? 'selected' : '' }}>deal</option>
                                  </select>
                                </td>
                                <td class="p-2 lg:p-3 align-middle">
                                  @if(!empty($row['error']))
                                    <span class="badge badge-danger">{{ $row['error'] }}</span>
                                  @else
                                    <span class="badge badge-success">OK</span>
                                  @endif
                                </td>
                                <td class="p-2 lg:p-3 text-center align-middle">
                                  <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger remove-preview-row"
                                    data-remove-scope="group"
                                    data-group-key="{{ $row['group_key'] ?? '' }}">
                                    <i class="bi bi-x"></i>
                                  </button>
                                </td>
                              </tr>
                            @endforeach
                          @elseif($tab === 'warm')
                            @foreach(($tabConfig['rows'] ?? []) as $row)
                              <tr
                                class="{{ $row['row_class'] . 'border-b border-b-[#D9D9D9]' ?? '' }}"
                                data-group-key="{{ $row['group_key'] ?? '' }}"
                                data-preview-index="{{ $row['preview_index'] ?? '' }}">
                                @if(empty($row['is_first_in_group']))
                                  <input type="hidden" name="rows[{{ $row['preview_index'] }}][group_key]" value="{{ $row['group_key'] ?? '' }}">
                                @endif
                                <td class="p-2 lg:p-3 align-middle">{{ !empty($row['is_first_in_group']) ? ($row['group_index'] ?? '') : '' }}</td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" class="form-control form-control-sm" value="{{ !empty($row['is_first_in_group']) ? ($row['meeting_type_label'] ?? '') : '' }}" readonly>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" class="form-control form-control-sm" value="{{ !empty($row['is_first_in_group']) ? ($row['meeting_url'] ?? '') : '' }}" readonly>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" class="form-control form-control-sm" value="{{ !empty($row['is_first_in_group']) ? ($row['meeting_start_at'] ?? '') : '' }}" readonly>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" class="form-control form-control-sm" value="{{ !empty($row['is_first_in_group']) ? ($row['meeting_end_at'] ?? '') : '' }}" readonly>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" class="form-control form-control-sm" value="{{ !empty($row['is_first_in_group']) ? ($row['meeting_city_label'] ?? '') : '' }}" readonly>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" class="form-control form-control-sm" value="{{ !empty($row['is_first_in_group']) ? ($row['meeting_address'] ?? '') : '' }}" readonly>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" class="form-control form-control-sm" value="{{ $row['expense_type_label'] ?? '' }}" readonly>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" class="form-control form-control-sm" value="{{ $row['expense_notes'] ?? '' }}" readonly>
                                </td>
                                <td class="p-2 lg:p-3">
                                  <input type="text" class="form-control form-control-sm" value="{{ $row['expense_amount'] ?? '' }}" readonly>
                                </td>
                                <td class="p-2 lg:p-3 align-middle">
                                  @if(!empty($row['is_first_in_group']))
                                    @if(!empty($row['error']))
                                      <span class="badge badge-danger">{{ $row['error'] }}</span>
                                    @else
                                      <span class="badge badge-success">OK</span>
                                    @endif
                                  @endif
                                </td>
                                <td class="p-2 lg:p-3 text-center align-middle">
                                  <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger remove-preview-row"
                                    data-remove-scope="{{ !empty($row['is_first_in_group']) ? 'group' : 'row' }}"
                                    data-group-key="{{ $row['group_key'] ?? '' }}"
                                    data-preview-index="{{ $row['preview_index'] ?? '' }}">
                                    <i class="bi bi-x"></i>
                                  </button>
                                </td>
                              </tr>
                            @endforeach
                          @endif
                        </tbody>
                    </table>
                </div>

                {{-- NAVIGATION ROWS --}}
                <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent">
                    <div class="flex items-center gap-3">
                        <p class="font-semibold">Show Rows</p>
                        <select id="{{ $tab }}PageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md"
                            onchange="changePageSize('{{ $tab }}', this.value)">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <div id="{{ $tab }}Showing" class="font-semibold">Showing 0-0 of 0</div>
                        <div>
                            <button id="{{ $tab }}PrevBtn"
                                type="button"
                                class="btn btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                                onclick="goPrev('{{ $tab }}')">
                                <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                            </button>
                            <button id="{{ $tab }}NextBtn" type="button" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                                onclick="goNext('{{ $tab }}')">
                                <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
          @endforeach
        </form>
      </div>
    @endisset
  </div>
</section>
@endsection

@section('styles')
<style>
  .nav-leads {
    transition: background-color 0.2s ease, color 0.2s ease;
  }

  .nav-leads.active-nav {
    border-bottom: 4px solid #115640;
    color: #1E1E1E;
  }

  .nav-leads.active-nav p {
    color: #1E1E1E;
  }
</style>
@endsection

@section('scripts')
<script>
  const importPreviewDefaultTab = @json($previewTableConfig['default_tab'] ?? 'cold');
  const hasImportPreviewRows = @json(isset($rows));
  const importSuccessMessage = @json(session('success'));

  $(function(){
    if (typeof bsCustomFileInput !== 'undefined') {
      bsCustomFileInput.init();
    }

    $('#uploadForm').on('submit', function(){
      loading();
    });

    $('.guide-toggle').on('click', function(){
      const $trigger = $(this);
      const target = $trigger.data('target');
      const $panel = $(target);

      if (!$panel.length) {
        return;
      }

      $panel.stop(true, true).slideToggle(200);
      $trigger.find('.fa-chevron-right').first().toggleClass('rotate-90');
    });

    if (importSuccessMessage) {
      notif(importSuccessMessage);
    }

    function updateTabSummary(tab) {
      const $rows = $('[data-tab-container="' + tab + '"] tbody tr');
      const totalRows = $rows.length;
      const visibleRows = $rows.filter(function() {
        return $(this).css('display') !== 'none';
      }).length;

      if (visibleRows === 0) {
        $('#' + tab + 'Showing').text('Showing 0-0 of ' + totalRows);
        return;
      }

      $('#' + tab + 'Showing').text('Showing 1-' + visibleRows + ' of ' + totalRows);
    }

    function filterActiveTabRows() {
      const keyword = ($('#searchInput').val() || '').toLowerCase().trim();
      const activeTab = $('.nav-leads.active-nav').data('tab') || importPreviewDefaultTab;
      const $rows = $('[data-tab-container="' + activeTab + '"] tbody tr');

      $rows.each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(keyword) !== -1);
      });

      updateTabSummary(activeTab);
    }

    if (hasImportPreviewRows) {
      $('.legacy-preview-table').find(':input').prop('disabled', true);

      function activatePreviewTab(tab) {
        $('[data-tab-container]').addClass('hidden').removeClass('block');
        $('[data-tab-container="' + tab + '"]').removeClass('hidden').addClass('block');

        $('.nav-leads').removeClass('active-nav');
        $('.nav-leads').find('p').removeClass('text-[#1E1E1E]').addClass('text-[#1E1E1E]');

        const $activeTab = $('.nav-leads[data-tab="' + tab + '"]');
        $activeTab.addClass('active-nav');
        $activeTab.find('p').removeClass('text-[#1E1E1E]').addClass('text-[#1E1E1E]');

        filterActiveTabRows();
      }

      $('.nav-leads').on('click', function(){
        activatePreviewTab($(this).data('tab'));
      });

      $('#searchInput').on('input', function() {
        filterActiveTabRows();
      });

      activatePreviewTab(importPreviewDefaultTab);

      $(document).on('click', '.remove-preview-row', function(){
        const removeScope = $(this).data('remove-scope');
        const groupKey = $(this).data('group-key');
        const previewIndex = $(this).data('preview-index');

        if (removeScope === 'group' && groupKey) {
          $('tr[data-group-key="' + groupKey + '"]').remove();
          $('input[name$="[group_key]"]').filter(function() {
            return $(this).val() === groupKey;
          }).remove();
        } else if (previewIndex !== undefined && previewIndex !== '') {
          $('tr[data-preview-index="' + previewIndex + '"]').remove();
          $('input[name="rows[' + previewIndex + '][group_key]"]').remove();
        }

        filterActiveTabRows();
      });

      $('#submitForm').on('submit', function(e){
        e.preventDefault();
        Swal.fire({
          title: 'Submit leads?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes',
          cancelButtonText: 'Cancel'
        }).then(function(res){
          if (res.isConfirmed) {
            loading();
            this.submit();
          }
        }.bind(this));
      });
    }

    window.changePageSize = function(tab) {
      updateTabSummary(tab);
    };

    window.goPrev = function(tab) {
      updateTabSummary(tab);
    };

    window.goNext = function(tab) {
      updateTabSummary(tab);
    };
  });
</script>
@endsection
