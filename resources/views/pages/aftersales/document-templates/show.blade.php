@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('assets/css/document-templates.css') }}?v={{ filemtime(public_path('assets/css/document-templates.css')) }}">
@endsection

@section('content')
<section id="document-template-app" class="dt-page" data-template="{{ $template }}">
    <nav class="dt-breadcrumb" aria-label="Breadcrumb">Document Templates <span>›</span> <span id="dt-document-name"></span></nav>
    <header class="dt-page-header">
        <div><div class="dt-title-row"><h1 id="dt-title"></h1><span id="dt-version" class="dt-badge"></span><span id="dt-status" class="dt-badge dt-draft">Draft</span></div><p>Configure the content used by the Aftersales team.</p></div>
        <div id="dt-actions" class="dt-actions"></div>
    </header>
    <div id="dt-content"></div>
    <p id="dt-feedback" class="dt-feedback" role="status" aria-live="polite"></p>
    <dialog id="dt-preview" class="dt-dialog"><div class="dt-card-head"><h2>Template preview</h2><button type="button" class="dt-button" id="dt-close-preview">Close</button></div><div id="dt-preview-content" class="dt-card-body"></div></dialog>
</section>
@endsection

@section('scripts')
@if($template === 'service-agreement')
<script src="{{ asset('assets/js/agreement-templates.js') }}?v={{ filemtime(public_path('assets/js/agreement-templates.js')) }}"></script>
@elseif($template === 'service-inspection-report')
<script src="{{ asset('assets/js/inspection-templates.js') }}?v={{ filemtime(public_path('assets/js/inspection-templates.js')) }}"></script>
@else
<script src="{{ asset('assets/js/survey-templates.js') }}?v={{ filemtime(public_path('assets/js/survey-templates.js')) }}"></script>
@endif
@endsection
