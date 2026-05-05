@extends('layouts.app')

@section('styles')
<style>
	.product-multi-select + .select2-container .select2-selection--multiple {
		min-height: 40px !important;
		border: 1px solid #D9D9D9 !important;
		border-radius: 0.5rem !important;
		padding: 4px 8px !important;
		display: block !important;
	}

	.product-multi-select + .select2-container .select2-selection__rendered {
		display: flex !important;
		flex-wrap: wrap !important;
		align-items: center !important;
		gap: 4px !important;
		margin: 0 !important;
		padding: 0 !important;
		list-style: none !important;
	}

	.product-multi-select + .select2-container .select2-selection__choice {
		display: inline-flex !important;
		align-items: center !important;
		position: relative !important;
		margin: 0 !important;
		padding: 4px 8px 4px 30px !important;
		background-color: #E8EFEC !important;
		border: 1px solid #115640 !important;
		border-radius: 0.375rem !important;
		color: #115640 !important;
		font-weight: 500 !important;
		line-height: 1.25 !important;
	}

	.product-multi-select + .select2-container .select2-selection__choice__remove {
		width: 18px !important;
		height: 18px !important;
		display: inline-flex !important;
		align-items: center !important;
		justify-content: center !important;
		position: absolute !important;
		left: 6px !important;
		top: 50% !important;
		transform: translateY(-50%) !important;
		color: #115640 !important;
		margin: 0 !important;
		padding: 0 !important;
		border-right: 0 !important;
		font-size: 18px !important;
		line-height: 1 !important;
		transition: color 0.15s ease !important;
	}

	.product-multi-select + .select2-container .select2-selection__choice__remove:hover {
		color: #900B09 !important;
	}

	.product-multi-select + .select2-container .select2-search--inline,
	.product-multi-select + .select2-container .select2-search__field {
		display: inline-flex !important;
		margin: 0 !important;
		min-width: 120px !important;
	}
</style>
@endsection

@section('content')
<section class="min-h-screen sm:text-xs! lg:text-sm!">
	<div class="pt-4">
		<div class="flex items-center gap-3 text-[#115640]">
			<x-icon.package/>
			<h1 class="font-semibold text-2xl">Products</h1>
		</div>
		<div class="flex items-center mt-2 gap-3">
			<a href="javascript:history.back()" class="text-[#757575] hover:no-underline">All Products</a>
			<i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
			<a href="{{ route('masters.products.form', $form_data->id) }}" class="text-[#083224] underline">
				{{ $form_data->id ? 'Edit Product' : 'Create Product' }}
			</a>
		</div>

		@php
			$formatPrice = fn($value) => ($value !== null && $value !== '')
				? number_format((int) preg_replace('/[^0-9]/', '', (string) $value), 0, '.', ',')
				: '';
			$labelClass = 'text-[#1E1E1E]! mb-1! block!';
			$fieldClass = 'w-full px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!';
			$selectClass = 'select2 w-full rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!';
			$multiSelectClass = $selectClass . ' product-multi-select';
			$optionClass = 'text-[#1E1E1E] checked:bg-[#115640] checked:text-white';
			$priceInputClass = 'p-2 border border-[#D9D9D9] w-full rounded-tr-lg rounded-br-lg text-[#1E1E1E]! focus:outline-none! item-price product-price-input';
		@endphp

		<form method="POST" action="{{ route('masters.products.save', $form_data->id) }}"
			id="form"
			back-url="{{ route('masters.products.index') }}"
			require-confirmation="true">
			@csrf

			{{-- MAIN GRID --}}
			<div class="grid grid-cols-[3fr_1fr] gap-3 mt-4">
				{{-- LEFT GRID --}}
				<div class="grid grid-cols-1 gap-3">
					{{-- PRODUCTS INFORMATION --}}
					<div class="bg-white rounded-lg border border-[#D9D9D9]">
						<h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">Product Information</h1>

						{{-- PRODUCT TYPE & SKU --}}
						<div class="w-full grid grid-cols-2 gap-2 mt-3">

							{{-- PRODUCT TYPE --}}
							<div class="px-3">
								<label class="{{ $labelClass }}">Product Type <i class="required">*</i></label>
								<select name="product_type_id" class="{{ $selectClass }}" required>
									<option value="" class="{{ $optionClass }}">-- Select Type --</option>
									@foreach($types as $type)
										<option value="{{ $type->id }}" class="{{ $optionClass }}" {{ old('product_type_id', $selectedType) == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
									@endforeach
								</select>
							</div>

							{{-- SKU --}}
							<div class="px-3">
								<label class="{{ $labelClass }}">SKU <i class="required">*</i></label>
								<input type="text" name="sku" class="{{ $fieldClass }}" value="{{ old('sku', $form_data->sku) }}" required>
							</div>
						</div>

						{{-- PRODUCT NAME --}}
						<div class="w-full grid grid-cols-1 gap-2 mt-3 px-3">
							<label class="{{ $labelClass }}">Product Name <i class="required">*</i></label>
							<input type="text" name="name" class="{{ $fieldClass }}" value="{{ old('name', $form_data->name) }}" required>
						</div>

						{{-- CATEGORIES --}}
						<div class="w-full grid grid-cols-1 gap-2 mt-3 px-3">
							<label class="{{ $labelClass }}">Categories</label>
							<select name="category_ids[]" class="{{ $multiSelectClass }}" multiple>
								@foreach($categories as $cat)
									<option value="{{ $cat->id }}" class="{{ $optionClass }}" {{ in_array($cat->id, old('category_ids', $selectedCategories ?? [])) ? 'selected' : '' }}>{{ $cat->name }}</option>
								@endforeach
							</select>
						</div>

						{{-- PARTS --}}
						<div class="w-full grid grid-cols-1 gap-2 mt-3 mb-4 px-3">
							<label class="{{ $labelClass }}">Parts</label>
							<select name="part_ids[]" class="{{ $multiSelectClass }}" multiple>
								@foreach($parts as $part)
									<option value="{{ $part->id }}" class="{{ $optionClass }}" {{ in_array($part->id, old('part_ids', $selectedParts ?? [])) ? 'selected' : '' }}>{{ $part->name }}</option>
								@endforeach
							</select>
						</div>
					</div>

					<div class="bg-white rounded-lg border border-[#D9D9D9] mt-4">
						<h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">Product Description</h1>

						<div class="w-full grid grid-cols-1 gap-2 mt-3 mb-4 px-3">
							<label class="{{ $labelClass }}">Description</label>
							<textarea name="description"
								placeholder="Type product description here..."
								class="{{ $fieldClass }}"
								rows="10">{{ old('description', $form_data->description) }}</textarea>
						</div>
					</div>
				</div>

				{{-- RIGHT GRID --}}
				<div class="grid grid-cols-1 gap-3">
					{{-- PRICING AND WARRANTY --}}
					<div class="bg-white rounded-lg border border-[#D9D9D9]">
						<h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">Pricing & Warranty</h1>

						<h1 class="text-[#1E1E1E] px-3 py-2 font-semibold border-b border-b-[#D9D9D9]">Price (Rp)</h1>

						{{-- FOB BDI CORPORATE GOVERNMENT PRICE --}}
						<div class="w-full grid grid-cols-2 gap-2 mt-3">

							{{-- FOB Price --}}
							<div class="px-3">
								<label class="{{ $labelClass }}">FOB Price</label>
								<div class="w-full flex items-center">
									<span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg rounded-bl-lg">Rp</span>
									<input type="text" inputmode="numeric" pattern="[0-9,]*" name="fob_price" class="{{ $priceInputClass }}" value="{{ $formatPrice(old('fob_price', $form_data->fob_price)) }}">
								</div>
							</div>

							{{-- BDI PRICE --}}
							<div class="px-3">
								<label class="{{ $labelClass }}">BDI Price</label>
								<div class="w-full flex items-center">
									<span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg rounded-bl-lg">Rp</span>
									<input type="text" inputmode="numeric" pattern="[0-9,]*" name="bdi_price" class="{{ $priceInputClass }}" value="{{ $formatPrice(old('bdi_price', $form_data->bdi_price)) }}">
								</div>
							</div>

							{{-- CORPORATE PRICE --}}
							<div class="px-3">
								<label class="{{ $labelClass }}">Corporate Price</label>
								<div class="w-full flex items-center">
									<span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg rounded-bl-lg">Rp</span>
									<input type="text" inputmode="numeric" pattern="[0-9,]*" name="corporate_price" class="{{ $priceInputClass }}" value="{{ $formatPrice(old('corporate_price', $form_data->corporate_price)) }}">
								</div>
							</div>

							{{-- Government Price --}}
							<div class="px-3">
								<label class="{{ $labelClass }}">Government Price</label>
								<div class="w-full flex items-center">
									<span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg rounded-bl-lg">Rp</span>
									<input type="text" inputmode="numeric" pattern="[0-9,]*" name="government_price" class="{{ $priceInputClass }}" value="{{ $formatPrice(old('government_price', $form_data->government_price)) }}">
								</div>
							</div>

						</div>

						{{-- PERSONAL PRICE --}}
						<div class="w-full grid grid-cols-1 mt-3 mb-4 px-3">
							<label class="{{ $labelClass }}">Personal Price</label>
							<div class="w-full flex items-center">
								<span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg rounded-bl-lg">Rp</span>
								<input type="text" inputmode="numeric" pattern="[0-9,]*" name="personal_price" class="{{ $priceInputClass }}" value="{{ $formatPrice(old('personal_price', $form_data->personal_price)) }}">
							</div>
						</div>

						<div class="w-full bg-[#D9D9D9] h-px block"></div>

						{{-- WARRANTY --}}
						<h1 class="text-[#1E1E1E] px-3 py-2 font-semibold border-b border-b-[#D9D9D9]">Warranty</h1>

						<div class="grid grid-cols-1">

							{{-- CHECKBOX IS WARRANTY --}}
							<div class="px-3 py-3 flex items-center gap-2">
								<input class="h-4 w-4 cursor-pointer accent-[#115640]" type="checkbox" role="switch" id="warranty_available" name="warranty_available" value="1" {{ old('warranty_available', $form_data->warranty_available) ? 'checked' : '' }}>
								<label class="text-[#1E1E1E]! cursor-pointer" for="warranty_available">Warranty Available</label>
							</div>

							{{-- WARRANTY IS --}}
							<div class="px-3 warranty-time-field {{ old('warranty_available', $form_data->warranty_available) ? '' : 'd-none' }}">
								<label class="{{ $labelClass }}">Warranty Time (Month)</label>
								<input type="number" name="warranty_time_month" class="{{ $fieldClass }}" value="{{ old('warranty_time_month', $form_data->warranty_time_month) }}">
							</div>

							{{-- VAT OR TAX? --}}
							<div class="px-3 mt-3 mb-4">
								<label class="{{ $labelClass }}">VAT (%)</label>
								<input type="number" name="vat" class="{{ $fieldClass }}" value="{{ old('vat', $form_data->vat) }}">
							</div>
						</div>
					</div>
				</div>
			</div>

			{{-- TEMPLATE BUTTON --}}
			<div class="mt-4">
				@include('partials.template.save-btn-form', ['backUrl' => route('masters.products.index')])
			</div>
		</form>
	</div>
</section>
@endsection

@section('scripts')
<script>
$(function () {
	$('#warranty_available').on('change', function () {
		$('.warranty-time-field').toggleClass('d-none', !$(this).is(':checked'));
	});

	function styleProductMultiSelects() {
		$('.product-multi-select').each(function () {
			const $container = $(this).next('.select2-container');
			$container.addClass('w-full!');
		});
	}

	function formatCommaNumber(value) {
		const digits = value.toString().replace(/[^0-9]/g, '');
		return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

	$('.product-price-input').each(function () {
		this.value = formatCommaNumber(this.value);
	});

	$(document).on('input', '.product-price-input', function () {
		this.value = formatCommaNumber(this.value);
	});

	styleProductMultiSelects();
	$(document).on('select2:select select2:unselect', '.product-multi-select', function () {
		setTimeout(styleProductMultiSelects, 0);
	});
});
</script>
@endsection
