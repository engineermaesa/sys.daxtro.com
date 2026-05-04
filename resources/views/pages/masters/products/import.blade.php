@extends('layouts.app')

@section('content')
@php
$stepGuides = [
1 => [
	'category' => 'Download template',
	'description' => 'Download template import products terlebih dahulu dari tombol "Download Template".',
],
2 => [
	'category' => 'Isi kolom wajib',
	'description' => 'Isi kolom wajib: `Product Type` (pilih dari dropdown) dan `Name`. Pastikan kolom lainnya sesuai kebutuhan.',
],
3 => [
	'category' => 'Upload file',
	'description' => 'Unggah file yang sudah diisi lewat form "Upload Products File" lalu klik "Submit Preview".',
],
4 => [
	'category' => 'Preview hasil',
	'description' => 'Periksa hasil di langkah Preview: periksa baris, koreksi bila perlu (gunakan tombol delete untuk baris bermasalah).',
],
5 => [
	'category' => 'Submit hasil',
	'description' => 'Jika semua sudah benar, klik "Submit Results" untuk menyimpan produk ke sistem.',
],
];

$referenceGuides = [
	'product_type' => [
		'sheet' => 'Product Types',
		'note' => 'Gunakan sheet Product Types sebagai referensi untuk kolom Product Type (dropdown).',
	],
];
@endphp
<section class="min-h-screen sm:text-xs! lg:text-sm!">
	<div class="pt-4">
		<div class="flex items-center gap-2">
			<svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path
					d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
					fill="#115641" />
			</svg>
			<h1 class="text-[#115641] font-semibold lg:text-2xl text-lg">Products</h1>
		</div>
		<p class="mt-1 text-[#115641] lg:text-lg text-sm">Import Products</p>
	</div>

	<div class="mt-4 w-full bg-white border border-[#D9D9D9] rounded-lg">
		<div class="flex items-center justify-between p-3 border-b border-b-[#D9D9D9]">
			<h1 class="font-bold text-[#1E1E1E] text-base">Download Template</h1>
			@if(Route::has('masters.products.import.template'))
			<a href="{{ route('masters.products.import.template') }}"
				class="bg-[#115641] rounded-lg flex justify-center items-center p-2 gap-1 text-white">
				<x-icon.download />
				Download Template
			</a>
			@else
			<div class="bg-gray-200 rounded-lg flex justify-center items-center p-2 gap-1 text-gray-600">
				<x-icon.download />
				Template not available
			</div>
			@endif
		</div>
		<div class="p-3">
			<div class="w-full border border-[#D9D9D9] rounded-lg overflow-hidden text-[#1E1E1E]">
				<div id="triggerOpenGuide"
					class="guide-toggle flex justify-between items-center p-3 cursor-pointer font-bold bg-[#115641] text-white"
					data-target="#mainGuide">
					<span>
						Petunjuk untuk mengisi template import products
					</span>
					<i class="fas fa-chevron-right transform transition-transform duration-300"></i>
				</div>
				<div id="mainGuide" class="guide-panel border-t border-[#D9D9D9]" style="display: none;">
					<div class="guide-toggle cursor-pointer bg-white text-[#1E1E1E] flex items-center justify-between p-3"
						data-target="#stepGuide">
						<h1 class="font-bold">Langkah Import</h1>
						<i class="fas fa-chevron-right transform transition-transform duration-300"></i>
					</div>
					<div id="stepGuide" class="guide-panel" style="display: none;">
						<table class="w-full border-collapse">
							<thead class="text-[#1E1E1E] bg-[#F5F5F5]">
								<tr class="border-t border-t-[#D9D9D9]">
									<th class="p-1 lg:p-3 border-r border-r-[#D9D9D9]">Steps</th>
									<th class="p-1 lg:p-3">Kategori</th>
									<th class="p-1 lg:p-3">Penjelasan</th>
								</tr>
							</thead>
							<tbody class="text-[#1E1E1E]">
								@foreach($stepGuides as $step => $guide)
								<tr class="border-t border-t-[#D9D9D9]">
									<td class="p-1 lg:p-3 border-r border-r-[#D9D9D9] text-center">{{ $step }}</td>
									<td class="p-1 lg:p-3 border-r border-r-[#D9D9D9]">{{ $guide['category'] }}</td>
									<td class="p-1 lg:p-3">{{ $guide['description'] }}</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>

					<div class="guide-toggle border-t border-t-[#D9D9D9] cursor-pointer bg-white text-[#1E1E1E] flex items-center justify-between p-3"
						data-target="#referencesGuide">
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
									<td class="p-1 lg:p-3 border-r border-r-[#D9D9D9] text-center">{{ $column }}</td>
									<td class="p-1 lg:p-3 border-r border-r-[#D9D9D9]">{{ $guide['sheet'] }}</td>
									<td class="p-1 lg:p-3">{{ $guide['note'] }}</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="mt-4 w-full bg-white border border-[#D9D9D9] rounded-lg">
		<h1 class="font-bold text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9] text-base">Upload Products File</h1>
		<div class="p-3">
			<div class="custom-file">
				@php
				$uploadAction = Route::has('masters.products.import.preview') ? route('masters.products.import.preview') : '#';
				@endphp
				<form id="uploadForm" action="{{ $uploadAction }}" method="POST" enctype="multipart/form-data">
					@csrf
					<input type="file"
						class="custom-file-input cursor-pointer w-full border border-[#D9D9D9] focus:outline-none!"
						id="importFile" name="import_file" accept=".xlsx,.csv" required>
					<label class="custom-file-label" for="importFile">Choose Excel...</label>
					<div class="flex justify-end my-5">
						<button type="submit"
							class="cursor-pointer p-2 lg:px-3 lg:py-2 bg-[#115641] border border-[#115641] rounded-lg text-white font-semibold">
							Submit Preview
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	@isset($rows)
	<div class="mt-4 w-full bg-white border border-[#D9D9D9] rounded-lg">
		@php
		$storeAction = Route::has('masters.products.import.store') ? route('masters.products.import.store') : '#';
		@endphp
		<form id="submitForm" method="POST" action="{{ $storeAction }}">
			@csrf
			@php
			$previewStageTabs = $previewTableConfig['tabs'] ?? [];
			$defaultPreviewTab = $previewTableConfig['default_tab'] ?? 'all';
			@endphp

			<div class="flex items-center justify-between p-3 border-b border-b-[#D9D9D9]">
				<h1 class="font-bold text-[#1E1E1E] text-base">Preview Results</h1>
				<button type="submit"
					class="cursor-pointer bg-[#115641] text-white rounded-lg px-3 py-2 flex items-center gap-2">
					<x-icon.small-circle-check />
					<span>Submit Results</span>
				</button>
			</div>

			@if(session('success'))
			<div class="p-3">
				<div class="bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
			</div>
			@endif
			@if(session('error'))
			<div class="p-3">
				<div class="bg-red-100 text-red-800 p-2 rounded">{{ session('error') }}</div>
			</div>
			@endif

			@foreach($previewStageTabs as $tab => $tabConfig)
			@php $isActiveTab = $tab === $defaultPreviewTab; @endphp
			<div data-tab-container="{{ $tab }}" class="{{ $isActiveTab ? 'block' : 'hidden' }}">
				<div class="overflow-x-auto">
					<table id="{{ $tab }}ImportProducts" class="bg-white rounded-br-lg rounded-bl-lg preview-table">
						<thead id="{{ $tab }}Head" class="text-[#1E1E1E]">
							<tr class="border-b border-b-[#D9D9D9]">
								<th class="text-sm p-2 lg:p-3 text-center col-no">No</th>
								@foreach(($tabConfig['headers'] ?? []) as $hi => $header)
								@php
									$headerUp = strtoupper($header);
									if (str_contains($headerUp, 'PRODUCT_TYPE')) {
										$colClass = 'col-type';
									} elseif (str_contains($headerUp, 'SKU')) {
										$colClass = 'col-sku';
									} elseif ($headerUp === 'NAME') {
										$colClass = 'col-name';
									} elseif (str_contains($headerUp, 'PRICE')) {
										$colClass = 'col-price';
									} else {
										$colClass = 'col-default';
									}

									// Shortened display labels for price columns
									if (str_contains($headerUp, 'FOB_PRICE')) {
										$displayHeader = 'FOB.PRICE';
									} elseif (str_contains($headerUp, 'BDI_PRICE')) {
										$displayHeader = 'BDI.PRICE';
									} elseif (str_contains($headerUp, 'CORPORATE_PRICE') || str_contains($headerUp, 'CORP_PRICE')) {
										$displayHeader = 'CORP.PRICE';
									} elseif (str_contains($headerUp, 'GOVERNMENT_PRICE') || str_contains($headerUp, 'GOV_PRICE')) {
										$displayHeader = 'GOV.PRICE';
									} elseif (str_contains($headerUp, 'PERSONAL_PRICE')) {
										$displayHeader = 'PERSONAL.PRICE';
									} else {
										$displayHeader = $header;
									}
								@endphp
								<th class="text-sm p-2 lg:p-3 text-nowrap {{ $colClass }}">{{ $displayHeader }}</th>
								<input type="hidden" name="headers[]" value="{{ $header }}" />
								@endforeach
								<th class="text-sm p-2 lg:p-3 text-center col-action">Action</th>
							</tr>
						</thead>
						<tbody id="{{ $tab }}Body" class="text-[#1E1E1E]">
							@foreach(($tabConfig['rows'] ?? []) as $row)
							<tr class="{{ trim(($row['row_class'] ?? '') . ' border-b border-b-[#D9D9D9]') }}"
								data-preview-index="{{ $loop->index + 1 }}">
								<td class="p-2 lg:p-3 text-center preview-no col-no">{{ $loop->index + 1 }}</td>
								@foreach(($row['cells'] ?? []) as $ci => $cell)
								@php
									$headerLabel = $tabConfig['headers'][$ci] ?? '';
									$isProductType = (stripos($headerLabel, 'PRODUCT_TYPE') !== false);
									$allTypes = $types ?? [];
									if (is_array($cell)) {
										$cellValue = isset($cell['value']) ? $cell['value'] : implode(', ', $cell);
										$cellStr = isset($cell['value']) ? (string)$cell['value'] : implode(', ', $cell);
									} else {
										$cellValue = $cell;
										$cellStr = (string)($cell ?? '');
									}
								@endphp
								<td class="p-2 lg:p-3">
									@if($isProductType)
									<select
										name="rows[{{ $row['preview_index'] ?? $loop->parent->index }}][cells][{{ $ci }}]"
										class="w-full border border-[#D9D9D9] rounded px-1 py-1 bg-white text-sm">
										<option value="">-- Select type --</option>
										@foreach($allTypes as $t)
										@php $selected = ($cellStr === (string)$t->name) || ($cellStr === (string)$t->id); @endphp
										<option value="{{ $t->id }}" {{ $selected ? 'selected' : '' }}>{{ $t->name }}</option>
										@endforeach
									</select>
									@else
									<input type="text"
										name="rows[{{ $row['preview_index'] ?? $loop->parent->index }}][cells][{{ $ci }}]"
										value="{{ $cellValue }}"
										class="w-full bg-transparent border border-transparent focus:border-[#D9D9D9] rounded px-1 py-1 text-sm" />
									@endif
								</td>
								@endforeach
								<td class="p-2 lg:p-3 text-center col-action">
									<button type="button" class="delete-preview-row" title="Delete row">
										<i class="fas fa-trash text-red-600"></i>
									</button>
								</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
			@endforeach
		</form>
	</div>
	@endisset
</section>
@endsection

@section('styles')
<style>
	.nav-products {
		transition: background-color 0.2s ease, color 0.2s ease;
	}

	.nav-products.active-nav {
		border-bottom: 4px solid #115641;
		color: #1E1E1E;
	}

	.invalid-cell {
		outline: 2px solid rgba(220, 53, 69, 0.6);
		background-color: rgba(255, 230, 230, 0.6);
	}

	.custom-toast {
		position: fixed;
		top: 1rem;
		right: 1rem;
		background: #dc3545;
		color: white;
		padding: 0.75rem 1rem;
		border-radius: 6px;
		box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
		z-index: 1200;
		white-space: pre-line;
	}

	/* ── Preview table layout ── */
	.preview-table {
		table-layout: fixed;
		width: 100%;
		min-width: 860px; /* pastikan tidak collapse di layar kecil */
		border-collapse: collapse;
	}

	.preview-table th,
	.preview-table td {
		vertical-align: middle;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	/* Allow table headers to wrap/show full text (avoid 'Act...' truncation) */
	.preview-table thead th {
		white-space: normal;
		overflow: visible;
		text-overflow: unset;
	}

	/* Lebar tiap kolom */
	.preview-table .col-no     { width: 48px;  min-width: 48px;  max-width: 48px;  text-align: center; }
	.preview-table .col-type   { width: 150px; min-width: 150px; }
	.preview-table .col-sku    { width: 90px;  min-width: 90px;  }
	.preview-table .col-name   { width: 150px; min-width: 150px; }
	.preview-table .col-price  { width: 120px; min-width: 100px; }
	.preview-table .col-default{ width: 110px; min-width: 110px; }
	/* Action column: give slightly larger max so header has room, but keep it compact */
	.preview-table .col-action { width: 64px;  min-width: 48px;  max-width: 80px;  text-align: center; }

	/* Input & select mengikuti lebar sel */
	.preview-table td input,
	.preview-table td select {
		width: 100%;
		min-width: 0;
		box-sizing: border-box;
	}

	/* Tombol hapus */
	.delete-preview-row {
		background: transparent;
		border: none;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 30px;
		height: 30px;
		border-radius: 6px;
		padding: 0;
		margin: 0;
	}

	.delete-preview-row i {
		color: #dc3545;
		font-size: 15px;
	}
</style>
@endsection

@section('scripts')
<script>
	$(function(){
		if (typeof bsCustomFileInput !== 'undefined') { bsCustomFileInput.init(); }

		$('#uploadForm').on('submit', function(){ loading(); });

		$('#importFile').on('change', function(){
			const file = this.files && this.files[0];
			const $label = $(this).closest('.custom-file').find('.custom-file-label');
			$label.text(file && file.name ? file.name : 'Choose Excel...');
		});

		$('.guide-toggle').on('click', function(){
			const target = $(this).data('target');
			const $panel = $(target);
			if (!$panel.length) return;
			$panel.stop(true, true).slideToggle(200);
			$(this).find('.fa-chevron-right').first().toggleClass('rotate-90');
		});

		$('#submitForm').on('submit', function(e){
			$(this).find('.invalid-cell').removeClass('invalid-cell');

			const $form = $(this);
			const errors = [];

			$form.find('table[id$="ImportProducts"]').each(function(){
				const $table = $(this);
				const headers = $table.find('thead th').map(function(){ return $(this).text().trim().toUpperCase(); }).get();

				const nameIdx = headers.findIndex(h => h.indexOf('NAME') !== -1);
				const typeIdx = headers.findIndex(h => h.indexOf('PRODUCT_TYPE') !== -1);

				$table.find('tbody tr').each(function(rowIndex){
					const displayRow = $(this).data('preview-index') || (rowIndex + 1);

					if (nameIdx >= 0) {
						const $input = $(this).find('td').eq(nameIdx).find('input, textarea, select').first();
						if (!($input.val() || '').toString().trim()) {
							errors.push(displayRow + ': NAME is required');
							$input.addClass('invalid-cell');
						}
					}

					if (typeIdx >= 0) {
						const $input = $(this).find('td').eq(typeIdx).find('input, textarea, select').first();
						if (!($input.val() || '').toString().trim()) {
							errors.push(displayRow + ': PRODUCT_TYPE is required');
							$input.addClass('invalid-cell');
						}
					}
				});
			});

			if (errors.length) {
				e.preventDefault();
				const lines = errors.map(function(it){
					const parts = String(it).split(':');
					const row = parts[0] ? parts[0].trim() : '';
					const rest = parts[1] ? parts[1].trim() : '';
					const field = rest.split(' ')[0] || '';
					let fieldFormatted = field;
					if (!(field === field.toUpperCase() || field.indexOf('_') !== -1)) {
						fieldFormatted = field.charAt(0).toUpperCase() + field.slice(1).toLowerCase();
					}
					return (row ? row + '. ' : '') + fieldFormatted + ' is required';
				});
				const messageHtml = lines.join('<br>');
				try {
					if (typeof Notyf !== 'undefined') {
						const n = new Notyf({position: {x: 'right', y: 'top'}, duration: 6000});
						n.error({message: messageHtml, ripple: false});
					} else {
						const $old = document.querySelector('.custom-toast');
						if ($old) $old.remove();
						const div = document.createElement('div');
						div.className = 'custom-toast';
						div.innerHTML = messageHtml;
						document.body.appendChild(div);
						setTimeout(() => div.remove(), 6000);
					}
				} catch (err) {
					alert(lines.join('\n'));
				}
				const $first = $form.find('.invalid-cell').first();
				if ($first.length) $first[0].scrollIntoView({behavior: 'smooth', block: 'center'});
			}
		});

		$(document).on('click', '.delete-preview-row', function(){
			const $tr = $(this).closest('tr');
			const $table = $tr.closest('table');
			$tr.remove();
			$table.find('tbody tr').each(function(i){
				const idx = i + 1;
				$(this).attr('data-preview-index', idx);
				$(this).find('.preview-no').first().text(idx);
			});
		});
	});
</script>
@endsection