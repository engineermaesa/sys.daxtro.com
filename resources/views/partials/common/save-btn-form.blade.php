<div class="text-end">
    @if ($backUrl == 'back')
        <a href="javascript:history.back()" class="btn btn-secondary me-2">Cancel</a>
    @else
        <a href="{{ $backUrl }}" class="btn btn-secondary me-2">Cancel</a>
    @endif
    
    @if ( ! isset($no_save_btn) ||  ! $no_save_btn)
        <button type="submit" class="btn btn-primary">Save</button>
    @endif
</div>