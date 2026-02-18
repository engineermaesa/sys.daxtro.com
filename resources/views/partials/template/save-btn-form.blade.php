<div class="flex items-center justify-end gap-3">
    @if ($backUrl == 'back')
        <a href="javascript:history.back()" class="inline-block text-center w-[125px] px-3 py-2 border border-[#083224] text-[#083224] font-semibold rounded-lg cursor-pointer transition-all duration-300 hover:bg-white">Cancel</a>
    @else
        <a href="{{ $backUrl }}" class="inline-block text-center w-[125px] px-3 py-2 border border-[#083224] text-[#083224] font-semibold rounded-lg cursor-pointer transition-all duration-300 hover:bg-white">Cancel</a>
    @endif
    
    @if ( ! isset($no_save_btn) ||  ! $no_save_btn)
        <button type="submit" class="inline-block text-center w-[125px] px-3 py-2 bg-[#115640] transition-all duration-300 hover:bg-[#083224] text-white font-semibold rounded-lg cursor-pointer">Save</button>
    @endif
</div>