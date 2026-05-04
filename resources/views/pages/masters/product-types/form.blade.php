@extends('layouts.app')

@section('content')
<section class="section">
	<div class="row">
		<div class="col-xl-12">
			<div class="card">
				<div class="card-body pt-3">
							<form method="POST" action="{{ $saveUrl ?? route('masters.product-types.save', $form_data->id) }}"
								id="form"
								back-url="{{ $backUrl ?? route('masters.product-types.index') }}"
								require-confirmation="true">
						@csrf

						<div class="mb-3">
							<label class="form-label">Type Name <i class="required">*</i></label>
							<input type="text" name="name" class="form-control" value="{{ old('name', $form_data->name) }}" required>
						</div>

						<!-- No code field for product types (DB has only `name`) -->

						@include('partials.common.save-btn-form', ['backUrl' => route('masters.product-types.index')])
					</form>
				</div>
			</div>
		</div>
	</div>
</section>
@endsection
