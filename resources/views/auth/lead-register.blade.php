@extends('layouts.auth')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">Register Lead</h1>
                                </div>
                                @include('partials.flash')
                                <form class="user" method="POST" action="{{ route('lead.register.store') }}">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label class="form-label">Source <i class="required">*</i></label>
                                        <br>
                                        <select name="source_id" class="form-select w-100" required>
                                            <option value="">Select Source</option>
                                            @foreach($sources as $source)
                                                <option value="{{ $source->id }}" {{ old('source_id') == $source->id ? 'selected' : '' }}>{{ $source->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Segment <i class="required">*</i></label>
                                        <br>
                                        <select name="segment_id" class="form-select w-100" required>
                                            <option value="">Select Segment</option>
                                            @foreach($segments as $segment)
                                                <option value="{{ $segment->id }}" {{ old('segment_id') == $segment->id ? 'selected' : '' }}>{{ $segment->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Region <i class="required">*</i></label>
                                        <br>
                                        <select name="region_id" class="form-select w-100" required>
                                            <option value="">Select Region</option>
                                            @foreach($regions as $region)
                                                <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Name <i class="required">*</i></label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Phone <i class="required">*</i></label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Email <i class="required">*</i></label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Needs <i class="required">*</i></label>
                                        <input type="text" name="needs" class="form-control" value="{{ old('needs') }}" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-user btn-block">Submit</button>
                                </form>
                                <hr>
                                <div class="text-center">
                                    <a class="small" href="{{ route('login') }}">Back to Login</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
