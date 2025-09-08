@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message')
    <div>{{ $message ?? __('Forbidden') }}</div>

    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="padding: 10px 20px; background-color: #f44336; color: white; border: none; cursor: pointer; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                Logout
            </button>
        </form>
    </div>
@endsection