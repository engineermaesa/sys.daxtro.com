
<div class="modal fade" id="dealReviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 760px;">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="flex items-start justify-between" style="padding:22px 28px 12px">
                <div>
                    <h5 class="text-[#115640] font-bold mb-1" style="font-size:20px">Deal Reviews</h5>
                    <p class="text-[#9A9A9A] mb-0" style="font-size:11px" id="dr-subtitle">Review Lead updates.</p>
                </div>
                <button type="button" data-dismiss="modal" aria-label="Close"
                    style="width:32px;height:32px;font-size:12px" class="border border-[#D9D9D9] rounded-lg bg-white flex items-center justify-center cursor-pointer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body bg-[#F9FAFB] text-xs" style="padding:18px 28px;max-height:65vh;overflow-y:auto">
                <div id="dr-error" class="hidden" style="margin-bottom:10px;padding:10px 14px;border-radius:8px;background:#FBE0DF;color:#C0281F;font-size:11px"></div>
                <div id="dr-list"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var API = '/api/aftersales/customers';
    var $modal = $('#dealReviewModal');
    var customerFilter = null; // set when opened from a customer's "Lead updated" badge

    var FIELDS = [
        ['name', 'Name'], ['phone', 'Phone'], ['email', 'Email'], ['address', 'Address'],
        ['ref_province_id', 'Province ID'], ['ref_region_id', 'City ID']
    ];

    function esc(v) {
        return String(v === null || v === undefined ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function showError(msg) {
        $('#dr-error').text(msg).toggleClass('hidden', !msg);
    }

    function errorText(xhr, fallback) {
        var body = xhr && xhr.responseJSON ? xhr.responseJSON : {};
        var fields = [];
        Object.keys(body.errors || {}).forEach(function (k) { fields = fields.concat(body.errors[k]); });
        return fields.length ? fields.join(' ') : (body.message || fallback);
    }

    function fetchAll(page, acc) {
        return $.getJSON(API + '/deal-reviews', { queue: 'lead_updates', page: page, per_page: 100 }).then(function (res) {
            acc = acc.concat(res.data || []);
            return page < (res.last_page || 1) ? fetchAll(page + 1, acc) : acc;
        });
    }

    function load() {
        showError('');
        $('#dr-list').html('<p class="text-center text-[#757575]" style="padding:16px">Loading...</p>');

        fetchAll(1, []).done(function (items) {
            if (customerFilter) {
                items = items.filter(function (i) { return String(i.service_customer_id) === String(customerFilter); });
            }
            render(items);
        }).fail(function (xhr) {
            $('#dr-list').empty();
            showError(errorText(xhr, 'Failed to load deal reviews.'));
        });
    }

    function render(items) {
        if (!items.length) {
            $('#dr-list').html('<p class="text-center text-[#757575]" style="padding:16px">Nothing to review.</p>');
            return;
        }
        $('#dr-list').html(items.map(card).join(''));
    }

    function btn(action, item, label, primary, extra) {
        return '<button type="button" class="dr-action ' + (primary ? 'bg-[#115640] text-white' : 'bg-white border border-[#D9D9D9]') + ' rounded-lg" ' +
            'data-id="' + item.id + '" data-action="' + action + '" data-updated="' + esc(item.updated_at) + '" ' + (extra || '') +
            ' style="font-size:11px;padding:6px 14px;font-weight:600">' + label + '</button>';
    }

    function card(item) {
        var leadName = (item.lead && item.lead.name) || (item.lead_snapshot || {}).name || ('Lead #' + item.lead_id);
        
        var current = item.customer || {};
        var pending = item.pending_changes || {};
        var rows = FIELDS.map(function (f) {
            var oldV = current[f[0]], newV = pending[f[0]];
            var changed = String(oldV === null || oldV === undefined ? '' : oldV) !== String(newV === null || newV === undefined ? '' : newV);
            return '<tr style="border-top:1px solid #EDEDED' + (changed ? ';background:#FFFCF3' : '') + '">' +
                '<td style="padding:5px 8px;color:#757575">' + f[1] + '</td>' +
                '<td style="padding:5px 8px">' + esc(oldV === null || oldV === undefined ? '-' : oldV) + '</td>' +
                '<td style="padding:5px 8px;' + (changed ? 'font-weight:700;color:#7A5F00' : '') + '">' + esc(newV === null || newV === undefined ? '-' : newV) + '</td></tr>';
        }).join('');
        var body = '<p class="mb-1" style="font-size:10px;color:#7A5F00;font-weight:700;text-transform:uppercase">Lead updated</p>' +
            '<p class="mb-2" style="font-size:11px"><b>' + esc(current.name || leadName) + '</b></p>' +
            '<table class="w-full mb-2" style="font-size:10.5px"><thead style="color:#5F6B66;font-size:9.5px;text-transform:uppercase"><tr><th style="padding:5px 8px;text-align:left">Field</th><th style="padding:5px 8px;text-align:left">Customer now</th><th style="padding:5px 8px;text-align:left">From Lead</th></tr></thead><tbody>' + rows + '</tbody></table>' +
            '<div class="flex" style="gap:8px">' + btn('apply', item, 'Apply changes', true) + btn('ignore', item, 'Ignore', false) + '</div>';

        return '<div class="bg-white" style="border:1px solid #E3E3E3;border-radius:10px;padding:14px 16px;margin-bottom:10px">' + body + '</div>';
    }

    // D02: updated_at must be sent exactly as D01 returned it (no reformatting).
    $modal.on('click', '.dr-action', function () {
        var $b = $(this);
        var id = $b.data('id');
        var action = $b.data('action');
        var payload = { action: action, updated_at: $b.attr('data-updated') };

        $modal.find('.dr-action').prop('disabled', true);
        showError('');

        $.ajax({
            url: API + '/deal-reviews/' + id + '/resolve',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json'
        }).done(function () {
            if (typeof loadCustomers === 'function') loadCustomers();
            load(); // reload the queue; resolved items disappear
        }).fail(function (xhr) {
            // 409: stale timestamp / already resolved / no longer eligible -> reload and show the message
            showError(errorText(xhr, 'Failed to resolve review.'));
            if (xhr.status === 409) load();
            $modal.find('.dr-action').prop('disabled', false);
        });
    });

    // opened from a "Lead updated" badge (one customer) or from the header button (all items)
    $(document).on('click', '.btn-open-deal-review', function () {
        customerFilter = $(this).data('customer-id') || null;
        $('#dr-subtitle').text(customerFilter ? 'Review the Lead changes for this customer.' : 'Review Lead updates.');
        $modal.modal('show');
        load();
    });
})();
</script>
@endpush
