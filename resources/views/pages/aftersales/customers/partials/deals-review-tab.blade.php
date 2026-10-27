{{-- Deals Review tab (PRD v2.2 IM-86, IM-87): pending Deals (Possible match) -> compare -> Link to existing / Create new. No Skip. --}}
<div id="tab-deals" class="hidden">
    <p class="text-[#757575] mt-4 mb-3" id="dr-tab-summary">Loading...</p>
    <div id="dr-tab-error" class="hidden" style="margin-bottom:10px;padding:10px 14px;border-radius:8px;background:#FBE0DF;color:#C0281F;font-size:11px"></div>

    <div class="bg-white rounded-lg border border-[#D9D9D9] overflow-x-auto">
        <table class="w-full" data-no-cards>
            <thead class="text-[#1E1E1E]">
                <tr class="border-b border-b-[#D9D9D9]">
                    <th class="p-3 text-left uppercase text-xs font-bold">Lead Name</th>
                    <th class="p-3 text-left uppercase text-xs font-bold">Quotation</th>
                    <th class="p-3 text-left uppercase text-xs font-bold">Phone</th>
                    <th class="p-3 text-left uppercase text-xs font-bold">City</th>
                    <th class="p-3 text-left uppercase text-xs font-bold">Match</th>
                    <th class="p-3 text-center uppercase text-xs font-bold">Action</th>
                </tr>
            </thead>
            <tbody id="dr-tab-body"></tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var API = '/api/aftersales/customers';
    var items = [];
    var openId = null;

    function esc(v) {
        return String(v === null || v === undefined ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function errorText(xhr, fallback) {
        var body = xhr && xhr.responseJSON ? xhr.responseJSON : {};
        var fields = [];
        Object.keys(body.errors || {}).forEach(function (k) { fields = fields.concat(body.errors[k]); });
        return fields.length ? fields.join(' ') : (body.message || fallback);
    }

    function showError(msg) {
        $('#dr-tab-error').text(msg).toggleClass('hidden', !msg);
    }

    // D01 is paginated with no filters: collect all pages, keep needs_review (Lead updated items stay in the modal).
    function fetchAll(page, acc) {
        return $.getJSON(API + '/deal-reviews', { page: page }).then(function (res) {
            acc = acc.concat(res.data || []);
            return page < (res.last_page || 1) ? fetchAll(page + 1, acc) : acc;
        });
    }

    // TODO(API): GET /api/aftersales/customers/deal-reviews should also return per item:
    //   quotation_no (or lead_snapshot.quotation_no), lead_snapshot.city, machines_count (from the quotation),
    //   and per candidate: city, machines_count, source (manual | import | deal_conversion).
    function leadName(item) {
        var snap = item.lead_snapshot || {};
        return (item.lead && item.lead.name) || snap.name || ('Lead #' + item.lead_id);
    }
    function quotationNo(item) { return item.quotation_no || (item.lead_snapshot || {}).quotation_no || '-'; }
    function city(obj) { return (obj && (obj.city || obj.region_name || (obj.region && obj.region.name))) || '-'; }
    function candidates(item) { return (item.candidates || []).filter(function (c) { return c.type === 'customer'; }); }

    function load() {
        showError('');
        $('#dr-tab-body').html('<tr><td colspan="6" class="text-center p-4 text-[#757575]">Loading...</td></tr>');
        return fetchAll(1, []).done(function (all) {
            items = all.filter(function (i) { return i.status === 'needs_review'; });
            if (openId && !items.some(function (i) { return i.id === openId; })) openId = null;
            render();
            if (window.setDealBadge) window.setDealBadge(items.length);
        }).fail(function (xhr) {
            $('#dr-tab-body').empty();
            showError(errorText(xhr, 'Failed to load deal reviews.'));
        });
    }

    function render() {
        var n = items.length;
        $('#dr-tab-summary').text(n
            ? n + (n === 1 ? ' Deal needs' : ' Deals need') + ' review. The number on the red badge goes down after each decision.'
            : 'No Deals need review.');

        if (!n) {
            $('#dr-tab-body').html('<tr><td colspan="6" class="text-center p-4 text-[#757575]">Nothing to review.</td></tr>');
            return;
        }

        $('#dr-tab-body').html(items.map(function (item) {
            var snap = item.lead_snapshot || {};
            var open = item.id === openId;
            var row = '<tr class="border-t border-t-[#D9D9D9]">' +
                '<td class="p-3 text-left text-black font-semibold">' + esc(leadName(item)) + '</td>' +
                '<td class="p-3 text-left text-black">' + esc(quotationNo(item)) + '</td>' +
                '<td class="p-3 text-left text-black">' + esc(snap.phone || '-') + '</td>' +
                '<td class="p-3 text-left text-black">' + esc(city(snap)) + '</td>' +
                '<td class="p-3 text-left"><span style="background:#FBEFC8;color:#7A5F00;font-size:11px;font-weight:600;padding:3px 10px;border-radius:6px;white-space:nowrap">Possible match</span></td>' +
                '<td class="p-3 text-center"><button type="button" class="dr-tab-toggle bg-white border border-[#D9D9D9] rounded-lg hover:bg-[#E8E8E8] cursor-pointer" data-id="' + item.id + '" style="font-size:11px;padding:5px 14px;font-weight:600">' + (open ? 'Close' : 'Review') + '</button></td></tr>';
            return row + (open ? compareRow(item) : '');
        }).join(''));
    }

    function personBox(title, name, phone, cityText, extra) {
        return '<div class="bg-white" style="flex:1;min-width:220px;border:1px solid #E3E3E3;border-radius:8px;padding:10px 12px">' +
            '<p class="uppercase mb-1" style="font-size:9px;font-weight:700;color:#9A9A9A">' + title + '</p>' +
            '<p class="font-bold mb-0" style="font-size:12px">' + esc(name) + '</p>' +
            '<p class="mb-0" style="font-size:11px;color:#757575">Phone ' + esc(phone || '-') + ' &middot; ' + esc(cityText) + '</p>' +
            (extra ? '<p class="mb-0" style="font-size:11px;color:#757575">' + extra + '</p>' : '') + '</div>';
    }

    var SOURCE_LABEL = { manual: 'Manual', import: 'Import', deal_conversion: 'Deal' };

    function existingBox(item) {
        var cands = candidates(item);
        if (!cands.length) {
            return '<div class="bg-white" style="flex:1;min-width:220px;border:1px solid #E3E3E3;border-radius:8px;padding:10px 12px"><p class="uppercase mb-1" style="font-size:9px;font-weight:700;color:#9A9A9A">Existing customer</p><p class="mb-0" style="font-size:11px;color:#757575">No existing customer found.</p></div>';
        }
        var c = cands[0];
        var select = cands.length > 1
            ? '<select class="dr-tab-candidate" data-id="' + item.id + '" style="font-size:11px;border:1px solid #D9D9D9;border-radius:6px;padding:3px 6px;margin-bottom:6px;max-width:100%">' +
                cands.map(function (x) { return '<option value="' + x.id + '">' + esc(x.name + ' (' + (x.phone || '-') + ')') + '</option>'; }).join('') + '</select>'
            : '';
        return '<div class="dr-tab-existing" data-id="' + item.id + '" style="flex:1;min-width:220px">' + select + '<div class="dr-tab-existing-box">' + existingDetail(c) + '</div></div>';
    }

    function existingDetail(c) {
        var extra = [];
        if (c.machines_count !== undefined && c.machines_count !== null) extra.push(esc(c.machines_count) + ' machines');
        if (c.source) extra.push('Source: ' + esc(SOURCE_LABEL[c.source] || c.source));
        return personBox('Existing customer', c.name, c.phone, city(c), extra.join(' &middot; '));
    }

    function compareRow(item) {
        var snap = item.lead_snapshot || {};
        var cands = candidates(item);
        var dealExtra = item.machines_count !== undefined && item.machines_count !== null ? esc(item.machines_count) + ' machines from quotation' : '';
        var btn = function (action, label, primary) {
            return '<button type="button" class="dr-tab-action ' + (primary ? 'bg-[#115640] text-white' : 'bg-white border border-[#D9D9D9]') + ' rounded-lg cursor-pointer" ' +
                'data-id="' + item.id + '" data-action="' + action + '" data-updated="' + esc(item.updated_at) + '" style="font-size:11px;padding:6px 14px;font-weight:600">' + label + '</button>';
        };
        return '<tr style="background:#FFFCF3;border-top:1px solid #EDEDED"><td colspan="6" style="padding:14px 16px">' +
            '<div class="flex" style="gap:12px;flex-wrap:wrap">' +
            personBox('From Deal', leadName(item), snap.phone, city(snap), dealExtra) + existingBox(item) + '</div>' +
            '<div class="flex" style="gap:8px;margin-top:12px">' + (cands.length ? btn('link', 'Link to existing', true) : '') + btn('create_new', 'Create new', !cands.length) + '</div>' +
            '</td></tr>';
    }

    $(document).on('click', '.dr-tab-toggle', function () {
        var id = parseInt($(this).data('id'), 10);
        openId = openId === id ? null : id;
        render();
    });

    $(document).on('change', '.dr-tab-candidate', function () {
        var item = items.filter(function (i) { return i.id === parseInt($(this).data('id'), 10); }.bind(this))[0];
        var c = item && candidates(item).filter(function (x) { return String(x.id) === $(this).val(); }.bind(this))[0];
        if (c) $(this).closest('.dr-tab-existing').find('.dr-tab-existing-box').html(existingDetail(c));
    });

    // D02: updated_at is sent exactly as D01 returned it.
    $(document).on('click', '.dr-tab-action', function () {
        var id = $(this).data('id');
        var payload = { action: $(this).data('action'), updated_at: $(this).attr('data-updated') };
        if (payload.action === 'link') {
            payload.customer_id = parseInt($('.dr-tab-candidate[data-id="' + id + '"]').val() || (candidates(items.filter(function (i) { return i.id === id; })[0])[0] || {}).id, 10);
        }

        $('.dr-tab-action').prop('disabled', true);
        showError('');
        $.ajax({
            url: API + '/deal-reviews/' + id + '/resolve',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json'
        }).done(function () {
            openId = null;
            load();                                                     // badge goes down with the list
            if (typeof loadCustomers === 'function') loadCustomers();
        }).fail(function (xhr) {
            showError(errorText(xhr, 'Failed to resolve review.'));
            if (xhr.status === 409) load();                             // stale / already resolved
            $('.dr-tab-action').prop('disabled', false);
        });
    });

    window.loadDealsReviewTab = load;
})();
</script>
@endpush
