<style>
    @page { margin: 14mm 12mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.35; color: #1E1E1E; }
    h1 { text-align: center; font-size: 15pt; color: #115640; margin: 0 0 2px; }
    h2 { font-size: 10pt; background: #E7F3EE; color: #115640; padding: 4px 6px; margin: 12px 0 4px; }
    .sub { text-align: center; color: #757575; margin: 0 0 8px; }
    .copy { position: absolute; top: 0; right: 0; border: 1px solid #115640; color: #115640; padding: 2px 8px; font-weight: bold; font-size: 8pt; }
    table { width: 100%; border-collapse: collapse; }
    td, th { border: 1px solid #D9D9D9; padding: 3px 5px; vertical-align: top; }
    th { background: #F5F5F5; text-align: left; }
    .nob td { border: none; padding: 1px 4px; }
    .muted { color: #757575; }
    .badge { font-weight: bold; }
    .sig td { text-align: center; width: 33%; height: 70px; vertical-align: middle; }
    .sig img { max-height: 55px; max-width: 150px; }
    .photo { width: 33%; text-align: center; font-size: 7pt; border: 1px solid #D9D9D9; padding: 3px; }
    .photo img { width: 100%; }
</style>
@unless ($watermarkCopy ?? false)
<div class="copy">{{ $copyLabel }}</div>
@endunless
