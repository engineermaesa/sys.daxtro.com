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
    <div id="dr-tab-pager" class="hidden">
        <div class="flex items-center justify-end gap-2 mt-3 text-xs">
            <button type="button" class="dr-tab-page bg-white border border-[#D9D9D9] rounded-lg px-3 py-1 cursor-pointer" data-dir="-1">Prev</button>
            <span id="dr-tab-page-info" class="text-[#757575]"></span>
            <button type="button" class="dr-tab-page bg-white border border-[#D9D9D9] rounded-lg px-3 py-1 cursor-pointer" data-dir="1">Next</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var API = '/api/aftersales/customers';
    var items = [];
    var page = 1;
    var lastPage = 1;
    var total = 0;
    var openId = null;
    var detail = null; // GET /deal-reviews/{openId} data

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

    function setBadge(res) {
        if (window.setDealBadge) window.setDealBadge(res);
    }

    function leadName(item) {
        return (item.lead && item.lead.name) || (item.lead_snapshot || {}).name || ('Lead #' + item.lead_id);
    }

    function load(toPage) {
        page = toPage || page;
        showError('');
        $('#dr-tab-body').html('<tr><td colspan="6" class="text-center p-4 text-[#757575]">Loading...</td></tr>');
        return $.getJSON(API + '/deal-reviews', { queue: 'pending', page: page }).done(function (res) {
            setBadge(res);
            items = res.data || [];
            lastPage = res.last_page || 1;
            total = res.total || 0;
            if (page > lastPage) return load(lastPage);
            if (openId && !items.some(function (i) { return i.id === openId; })) { openId = null; detail = null; }
            render();
        }).fail(function (xhr) {
            $('#dr-tab-body').empty();
            showError(errorText(xhr, 'Failed to load deal reviews.'));
        });
    }

    function render() {
        $('#dr-tab-summary').text(total
            ? total + (total === 1 ? ' Deal needs' : ' Deals need') + ' review. The number on the red badge goes down after each decision.'
            : 'No Deals need review.');
        $('#dr-tab-pager').toggleClass('hidden', lastPage <= 1);
        $('#dr-tab-page-info').text('Page ' + page + ' of ' + lastPage);
        $('.dr-tab-page[data-dir="-1"]').prop('disabled', page <= 1);
        $('.dr-tab-page[data-dir="1"]').prop('disabled', page >= lastPage);

        if (!items.length) {
            $('#dr-tab-body').html('<tr><td colspan="6" class="text-center p-4 text-[#757575]">Nothing to review.</td></tr>');
            return;
        }

        $('#dr-tab-body').html(items.map(function (item) {
            var lead = item.lead || {};
            var open = item.id === openId;
            var row = '<tr class="border-t border-t-[#D9D9D9]">' +
                '<td class="p-3 text-left text-black font-semibold">' + esc(leadName(item)) + '</td>' +
                '<td class="p-3 text-left text-black">' + esc((lead.quotation && lead.quotation.quotation_no) || '-') + '</td>' +
                '<td class="p-3 text-left text-black">' + esc(lead.phone || '-') + '</td>' +
                '<td class="p-3 text-left text-black">' + esc((lead.factory_city && lead.factory_city.name) || '-') + '</td>' +
                '<td class="p-3 text-left"><span style="background:#FBEFC8;color:#7A5F00;font-size:11px;font-weight:600;padding:3px 10px;border-radius:6px;white-space:nowrap">Possible match</span></td>' +
                '<td class="p-3 text-center"><button type="button" class="dr-tab-toggle bg-white border border-[#D9D9D9] rounded-lg hover:bg-[#E8E8E8] cursor-pointer" data-id="' + item.id + '" style="font-size:11px;padding:5px 14px;font-weight:600">' + (open ? 'Close' : 'Review') + '</button></td></tr>';
            return row + (open ? compareRow() : '');
        }).join(''));
    }

    function loadDetail(id) {
        detail = null;
        render();
        $.getJSON(API + '/deal-reviews/' + id).done(function (res) {
            setBadge(res);
            if (openId !== id) return;
            detail = res.data;
            render();
        }).fail(function (xhr) {
            openId = null;
            render();
            showError(errorText(xhr, 'Failed to load the deal.'));
        });
    }

    function personBox(title, name, phone, cityText, extra) {
        return '<div class="bg-white" style="flex:1;min-width:220px;border:1px solid #E3E3E3;border-radius:8px;padding:10px 12px">' +
            '<p class="uppercase mb-1" style="font-size:9px;font-weight:700;color:#9A9A9A">' + title + '</p>' +
            '<p class="font-bold mb-0" style="font-size:12px">' + esc(name) + '</p>' +
            '<p class="mb-0" style="font-size:11px;color:#757575">Phone ' + esc(phone || '-') + ' &middot; ' + esc(cityText) + '</p>' +
            (extra ? '<p class="mb-0" style="font-size:11px;color:#757575">' + extra + '</p>' : '') + '</div>';
    }

    var SOURCE_LABEL = { manual: 'Manual', import: 'Import', deal_conversion: 'Deal' };

    function cityName(obj) { return (obj && obj.region && obj.region.name) || '-'; }
    function candidates() { return ((detail && detail.candidates) || []).map(function (c) { return c.customer; }); }

    function existingBox() {
        var cands = candidates();
        if (!cands.length) {
            return '<div class="bg-white" style="flex:1;min-width:220px;border:1px solid #E3E3E3;border-radius:8px;padding:10px 12px"><p class="uppercase mb-1" style="font-size:9px;font-weight:700;color:#9A9A9A">Existing customer</p><p class="mb-0" style="font-size:11px;color:#757575">No existing customer found.</p></div>';
        }
        var select = cands.length > 1
            ? '<select class="dr-tab-candidate" style="font-size:11px;border:1px solid #D9D9D9;border-radius:6px;padding:3px 6px;margin-bottom:6px;max-width:100%">' +
                cands.map(function (x) { return '<option value="' + x.id + '">' + esc(x.name + ' (' + (x.phone || '-') + ')') + '</option>'; }).join('') + '</select>'
            : '';
        return '<div style="flex:1;min-width:220px">' + select + '<div class="dr-tab-existing-box">' + existingDetail(cands[0]) + '</div></div>';
    }

    function existingDetail(c) {
        var extra = [esc(c.products_count || 0) + ' machines'];
        if (c.source) extra.push('Source: ' + esc(SOURCE_LABEL[c.source] || c.source));
        return personBox('Existing customer', c.name, c.phone, cityName(c), extra.join(' &middot; '));
    }

    function compareRow() {
        var wrap = function (html) { return '<tr style="background:#FFFCF3;border-top:1px solid #EDEDED"><td colspan="6" style="padding:14px 16px">' + html + '</td></tr>'; };
        if (!detail) return wrap('<p class="mb-0 text-[#757575]" style="font-size:11px">Loading...</p>');

        var from = detail.from_deal || {};
        var allowed = detail.allowed_actions || [];
        var canLink = allowed.indexOf('link') !== -1 && candidates().length;
        var btn = function (action, label, primary) {
            return '<button type="button" class="dr-tab-action ' + (primary ? 'bg-[#115640] text-white' : 'bg-white border border-[#D9D9D9]') + ' rounded-lg cursor-pointer" ' +
                'data-action="' + action + '" style="font-size:11px;padding:6px 14px;font-weight:600">' + label + '</button>';
        };
        var actions = !allowed.length
            ? '<p class="mb-0 text-[#757575]" style="font-size:11px">This Deal no longer needs a decision.</p>'
            : (canLink ? btn('link', 'Link to existing', true) : '') + (allowed.indexOf('create_new') !== -1 ? btn('create_new', 'Create new', !canLink) : '');
        return wrap('<div class="flex" style="gap:12px;flex-wrap:wrap">' +
            personBox('From Deal', from.name, from.phone, cityName(from), esc(from.machine_count || 0) + ' machines from quotation') + existingBox() + '</div>' +
            '<div class="flex" style="gap:8px;margin-top:12px">' + actions + '</div>');
    }

    $(document).on('click', '.dr-tab-toggle', function () {
        var id = parseInt($(this).data('id'), 10);
        openId = openId === id ? null : id;
        if (openId) loadDetail(openId); else render();
    });

    $(document).on('click', '.dr-tab-page', function () {
        openId = null;
        detail = null;
        load(page + parseInt($(this).data('dir'), 10));
    });

    $(document).on('change', '.dr-tab-candidate', function () {
        var id = $(this).val();
        var c = candidates().filter(function (x) { return String(x.id) === id; })[0];
        if (c) $('.dr-tab-existing-box').html(existingDetail(c));
    });

    
    $(document).on('click', '.dr-tab-action', function () {
        if (!detail) return;
        var id = detail.deal.id;
        var payload = { action: $(this).data('action'), updated_at: detail.deal.updated_at };
        if (payload.action === 'link') {
            payload.customer_id = parseInt($('.dr-tab-candidate').val() || candidates()[0].id, 10);
        }

        $('.dr-tab-action').prop('disabled', true);
        showError('');
        $.ajax({
            url: API + '/deal-reviews/' + id + '/resolve',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json'
        }).done(function (res) {
            setBadge(res);
            openId = null;
            detail = null;
            load();
            if (typeof loadCustomers === 'function') loadCustomers();
        }).fail(function (xhr) {
            showError(errorText(xhr, 'Failed to resolve review.'));
            $('.dr-tab-action').prop('disabled', false);
            if (xhr.status === 409) {
                load();
                if (openId) loadDetail(openId);
            }
        });
    });

    window.loadDealsReviewTab = load;
})();
</script>
@endpush
