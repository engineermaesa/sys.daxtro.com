{{-- Document language switcher (PRD v2.2 IM-99..101). Labels are always in Bahasa Indonesia. Behaviour: documents-script (applyLang / .doc-lang-btn). --}}
<div class="doc-lang inline-flex rounded-lg border border-[#D9D9D9] bg-white overflow-hidden shrink-0" data-doc="{{ $doc }}" data-no-i18n role="group" aria-label="Bahasa dokumen">
    @foreach (['id' => 'Indonesia', 'en' => 'Inggris', 'zh' => 'Mandarin'] as $code => $label)
        <button type="button" data-lang="{{ $code }}" title="{{ strtoupper($code === 'zh' ? 'chn' : $code) }}"
            class="doc-lang-btn cursor-pointer font-semibold text-xs {{ $code === 'en' ? 'bg-[#115640] text-white' : 'bg-white text-[#4B5563]' }}"
            style="padding: 6px 12px;">{{ $label }}</button>
    @endforeach
</div>
