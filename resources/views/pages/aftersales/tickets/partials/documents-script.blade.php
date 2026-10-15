{{-- Logika Supporting Documents, Service Agreement, Service Form, tanda tangan, dan Supervisor Sign.
     Dipasang dari tickets/index.blade.php; memakai `currentTicket` milik halaman itu.
     API: /api/aftersales/tickets/{id}/documents | agreement | service-form | documents/{type}/(signatures|save|unlock|pdf|dispatches) --}}
<style>
    .sf-combo input[list]::-webkit-calendar-picker-indicator { opacity: 0; position: absolute; right: 0; top: 0; width: 40px; height: 100%; cursor: pointer; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<link rel="stylesheet" href="{{ asset('assets/css/service-diagnosis.css') }}?v={{ filemtime(public_path('assets/css/service-diagnosis.css')) }}">
<script src="{{ asset('assets/js/service-diagnosis.js') }}?v={{ filemtime(public_path('assets/js/service-diagnosis.js')) }}"></script>
<script>
(function () {
    const API = '/api/aftersales';
    const S = { ticketId: null, docs: null, agreement: null, form: null, activeTab: null, openItem: null, ssDoc: null, ssData: null };
    const VIEWS = ['ticketListView', 'ticketDetailsView', 'ticketWorkOrderView', 'ticketAgreementView', 'ticketServiceFormView', 'ticketSupervisorSignView'];
    const TYPE_TITLE = { agreement: 'Service Agreement', service_form: 'Service Form' };
    const ROLES = [['technician', 'Serviced by / Technician'], ['supervisor', 'Checked by / Supervisor'], ['customer', 'Approved by / Customer']];
    const STATUS_LABEL = { ok: 'OK', rep: 'Rep', na: 'N/A' };
    const STATUS_STYLE = {
        ok: 'background:#E7F3EE;color:#115640;',
        rep: 'background:#FBEAEA;color:#EC221F;',
        na: 'background:#F0F0F0;color:#757575;',
        none: 'background:#F0F0F0;color:#4B5563;',
    };
    const COPY_LABEL = { customer: 'Copy for Customer', aftersales: 'Copy for Aftersales', technician: 'Copy for Technician' };

    const esc = (v) => String(v === null || v === undefined ? '' : v).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const dash = (v) => (v === null || v === undefined || v === '') ? '-' : v;

    function fmtDateTime(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        if (isNaN(d)) return iso;
        return d.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false });
    }

    function errMsg(xhr, fallback) {
        const j = xhr.responseJSON || {};
        return j.message || (j.errors ? Object.values(j.errors).flat()[0] : null) || fallback;
    }
    function fail(xhr, fallback) {
        Swal.fire({ title: 'Error', text: errMsg(xhr, fallback || 'Something went wrong.'), icon: 'error', confirmButtonColor: '#115640' });
    }
    function toast(title) {
        Swal.fire({ title: title, icon: 'success', timer: 1400, showConfirmButton: false });
    }

    function call(method, url, body) {
        const opts = { url: API + url, method: method, headers: { Accept: 'application/json' } };
        if (body !== undefined) {
            opts.contentType = 'application/json';
            opts.data = JSON.stringify(body);
        }
        return $.ajax(opts);
    }

    function showOnly(id) {
        VIEWS.forEach(function (v) { $('#' + v).toggleClass('hidden', v !== id); });
        window.scrollTo(0, 0);
    }

    function currentId() {
        return (window.currentTicket || currentTicket || {}).id || S.ticketId;
    }

    // ---------- signature pad ----------
    function makePad(canvas) {
        if (canvas._pad) {
            canvas._pad.resize();
            canvas._pad.clear();
            return canvas._pad;
        }
        const ctx = canvas.getContext('2d');
        const pad = { ink: false };
        let drawing = false;

        pad.resize = function () {
            const r = canvas.getBoundingClientRect();
            canvas.width = r.width;
            canvas.height = r.height;
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#1E1E1E';
        };
        pad.clear = function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            pad.ink = false;
            if (pad.onChange) pad.onChange();
        };
        pad.dataUrl = function () { return canvas.toDataURL('image/png'); };

        function pt(e) {
            const r = canvas.getBoundingClientRect();
            return { x: e.clientX - r.left, y: e.clientY - r.top };
        }
        canvas.addEventListener('pointerdown', function (e) {
            drawing = true;
            const p = pt(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            canvas.setPointerCapture(e.pointerId);
        });
        canvas.addEventListener('pointermove', function (e) {
            if (!drawing) return;
            const p = pt(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            if (!pad.ink) {
                pad.ink = true;
                if (pad.onChange) pad.onChange();
            }
        });
        ['pointerup', 'pointercancel'].forEach(function (t) {
            canvas.addEventListener(t, function () { drawing = false; });
        });

        pad.resize();
        canvas._pad = pad;
        return pad;
    }

    // ---------- signature cards (Agreement + Service Form) ----------
    function renderSignatureCards(containerSel, type, doc) {
        const sig = doc.signatures || {};
        const can = doc.can || {};
        const locked = !!doc.locked;

        const html = ROLES.map(function (r) {
            const role = r[0];
            const s = sig[role];
            const canSign = !locked && !!can['sign_' + role];
            let body = '';

            if (s && s.url) {
                body += '<div class="flex items-center justify-center" style="height: 90px;"><img src="' + esc(s.url) + '" alt="Signature" style="max-height: 86px; max-width: 100%;"></div>';
            }

            if (canSign) {
                body += '<div class="relative" style="border-top: 1px dashed #D9D9D9; height: 110px; background: #FAFAFA;"><canvas class="sig-canvas" style="width: 100%; height: 100%; display: block; touch-action: none; cursor: crosshair;"></canvas></div>';
                if (role === 'customer') {
                    body += '<input type="text" class="sig-name w-full border border-[#D9D9D9] rounded-md" style="margin: 8px 0 0; padding: 8px 10px;" placeholder="Customer name (required)" value="' + esc((s && s.signer_name) || doc.customer_name || '') + '">';
                }
                body += '<div class="flex items-center" style="gap: 8px; padding: 8px 0 0;">'
                    + '<button type="button" class="sig-clear bg-white border border-[#D9D9D9] rounded-md cursor-pointer text-xs" style="padding: 6px 12px;">Clear</button>'
                    + '<button type="button" class="sig-sign bg-[#115640] text-white font-semibold rounded-md cursor-pointer text-xs" style="padding: 6px 12px;">' + (s ? 'Replace signature' : 'Sign') + '</button>'
                    + '</div>';
            } else if (!s) {
                body += '<div class="flex items-center justify-center text-[#B0B0B0]" style="height: 110px;">Awaiting signature</div>';
            }

            const meta = s ? '<p class="text-xs border-t border-[#EDEDED]" style="padding: 8px 14px;">' + esc(dash(s.signer_name)) + ' &middot; ' + esc(fmtDateTime(s.signed_at)) + '</p>' : '';

            return '<div class="sig-card bg-white rounded-lg border border-[#D9D9D9]" data-doc="' + type + '" data-role="' + role + '" style="overflow: hidden;">'
                + '<p class="uppercase font-semibold border-b border-[#EDEDED]" style="font-size: 11px; padding: 12px 14px; background: #FAFAFA;">' + esc(r[1]) + '</p>'
                + '<div style="padding: 0 14px 12px;">' + body + '</div>' + meta + '</div>';
        }).join('');

        $(containerSel).html(html).find('.sig-canvas').each(function () { makePad(this); });
    }

    $(document).on('click', '.sig-clear', function () {
        const c = $(this).closest('.sig-card').find('.sig-canvas')[0];
        if (c && c._pad) c._pad.clear();
    });

    $(document).on('click', '.sig-sign', function () {
        const $card = $(this).closest('.sig-card');
        const type = $card.data('doc');
        const role = $card.data('role');
        const canvas = $card.find('.sig-canvas')[0];
        const pad = canvas && canvas._pad;

        if (!pad || !pad.ink) {
            return Swal.fire({ title: 'Signature required', text: 'Please sign in the box first.', icon: 'warning', confirmButtonColor: '#115640' });
        }

        const body = { role: role, signature: pad.dataUrl() };
        if (role === 'customer') {
            body.signer_name = ($card.find('.sig-name').val() || '').trim();
            if (!body.signer_name) {
                return Swal.fire({ title: 'Customer name required', text: 'Enter the customer name before signing.', icon: 'warning', confirmButtonColor: '#115640' });
            }
        }

        const $btn = $(this).prop('disabled', true);
        call('POST', '/tickets/' + currentId() + '/documents/' + type + '/signatures', body)
            .done(function () {
                toast('Signature saved');
                reloadDocument(type);
            })
            .fail(function (xhr) { fail(xhr, 'Failed to save signature.'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    function reloadDocument(type) {
        loadDocs();
        if (type === 'agreement' && !$('#ticketAgreementView').hasClass('hidden')) loadAgreement();
        if (type === 'service_form' && !$('#ticketServiceFormView').hasClass('hidden')) loadForm();
    }

    function completenessBox(sel, doc) {
        const c = doc.completeness || {};
        const $el = $(sel);
        if (doc.locked) {
            $el.attr('style', 'margin-top: 16px; padding: 10px 14px; background:#E7F3EE; color:#115640;')
                .text('This document is saved and locked' + (doc.saved_at ? ' (' + fmtDateTime(doc.saved_at) + ')' : '') + '.');
        } else if (c.complete) {
            $el.attr('style', 'margin-top: 16px; padding: 10px 14px; background:#E7F3EE; color:#115640;')
                .text('All requirements are complete. The document can be saved.');
        } else {
            $el.attr('style', 'margin-top: 16px; padding: 10px 14px; background:#FFF3CD; color:#8A6D00;')
                .text(c.summary || 'Some requirements are still missing.');
        }
    }

    function statusBadge(sel, doc) {
        const saved = doc.status === 'saved';
        $(sel).text(saved ? 'Saved' : 'Draft')
            .attr('style', 'padding: 4px 12px;' + (saved ? 'background:#E7F3EE;color:#115640;' : 'background:#FFF3CD;color:#D97706;'));
    }

    function setSaveState($btn, doc) {
        const ok = !doc.locked && !!(doc.can && doc.can.save) && !!(doc.completeness && doc.completeness.complete);
        $btn.prop('disabled', !ok).css('opacity', ok ? 1 : 0.5);
    }

    // ---------- Final Cost (sparepart + category) di Ticket Detail ----------
    function rupiah(n) { return 'Rp ' + Math.round(Number(n || 0)).toLocaleString('id-ID'); }

    function moneyIn(idr, cur, rates) {
        const per = cur === 'usd' ? rates.usd : (cur === 'cny' ? rates.cny : 1);
        if (cur === 'idr' || !per) return rupiah(idr);
        const v = idr / per;
        return (cur === 'usd' ? '$' : '\u00A5') + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function loadFinalCost() {
        const id = currentId();
        if (!id) return;

        $('#tdFcParts, #tdFcCosts').html('<tr><td class="p-2 text-[#9CA3AF]" colspan="6">Loading...</td></tr>');

        $.when(
            call('GET', '/tickets/' + id),
            call('GET', '/tickets/exchange-rates')
        ).done(function (tRes, rRes) {
            const t = tRes[0].data || {};
            const r = (rRes[0] && rRes[0].data) || {};
            const rates = { usd: Number(r.idr_per_usd || 0), cny: Number(r.idr_per_cny || 0) };
            const cur = ['idr', 'usd', 'cny'].indexOf(t.cost_display_currency) >= 0 ? t.cost_display_currency : 'idr';

            const parts = (t.parts || []).filter(function (p) { return p.type === 'estimated'; });
            let partsIdr = 0;
            $('#tdFcParts').html(parts.length ? parts.map(function (p) {
                const price = Number(p.unit_price || 0);
                const line = Number(p.qty || 0) * price;
                partsIdr += line;
                return '<tr class="border-t border-[#EDEDED]">'
                    + '<td class="p-2">' + esc(dash((p.sparepart || {}).name)) + '</td>'
                    + '<td class="p-2 text-[#757575]">' + esc(dash((p.sparepart || {}).part_number)) + '</td>'
                    + '<td class="p-2 text-right">' + esc(Number(p.qty)) + '</td>'
                    + '<td class="p-2">' + esc(dash(p.unit)) + '</td>'
                    + '<td class="p-2 text-right text-[#4B5563]">' + (price > 0 ? rupiah(price) : '-') + '</td>'
                    + '<td class="p-2 text-right font-semibold">' + (price > 0 ? rupiah(line) : '-') + '</td></tr>';
            }).join('') : '<tr class="border-t border-[#EDEDED]"><td class="p-2 text-[#9CA3AF]" colspan="6">No sparepart.</td></tr>');
            $('#tdFcPartsTotal').text(partsIdr > 0 ? moneyIn(partsIdr, cur, rates) : '-');

            const costs = t.costs || [];
            let costsIdr = 0;
            $('#tdFcCosts').html(costs.length ? costs.map(function (c) {
                const amount = Number(c.amount || 0);
                const idr = c.currency === 'idr' ? amount : amount * Number(c.exchange_rate || 1);
                costsIdr += idr;
                const sym = c.currency === 'usd' ? '$' : (c.currency === 'cny' ? '\u00A5' : 'Rp ');
                return '<tr class="border-t border-[#EDEDED]">'
                    + '<td class="p-2">' + esc(String(c.category || '-').replace(/^./, function (x) { return x.toUpperCase(); })) + '</td>'
                    + '<td class="p-2 text-right">' + sym + amount.toLocaleString('id-ID', { maximumFractionDigits: 2 }) + '</td>'
                    + '<td class="p-2">' + esc(String(c.currency || '').toUpperCase()) + '</td>'
                    + '<td class="p-2 text-[#757575]">' + esc(dash(c.remarks)) + '</td></tr>';
            }).join('') : '<tr class="border-t border-[#EDEDED]"><td class="p-2 text-[#9CA3AF]" colspan="4">No cost entered.</td></tr>');

            $('#tdFcCostsTotal').text(moneyIn(costsIdr, cur, rates));
            $('#tdFcFinal').text(moneyIn(costsIdr + partsIdr, cur, rates));
            $('#tdFcFinalCurrency').text(cur.toUpperCase());

            $('#tdFinalCostChips').html('<div class="rounded-md border text-center" style="padding: 6px 12px; border-color:#115640; background:#E7F3EE;">'
                + '<p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Total Currency</p>'
                + '<p class="font-bold text-[#115640] text-xs">' + cur.toUpperCase() + '</p></div>');
        }).fail(function (xhr) {
            $('#tdFcParts, #tdFcCosts').html('<tr><td class="p-2 text-[#EC221F]" colspan="6">' + esc(errMsg(xhr, 'Failed to load cost details.')) + '</td></tr>');
        });
    }

    // ---------- Supporting Documents card ----------
    function loadDocs() {
        const id = currentId();
        if (!id) return;
        S.ticketId = id;

        call('GET', '/tickets/' + id + '/documents')
            .done(function (res) {
                S.docs = res.data;
                if (S.docs.supervisor && S.docs.supervisor.name) $('#tdSupervisor').text(S.docs.supervisor.name);
                renderDocs();
            })
            .fail(function (xhr) {
                S.docs = null;
                $('#tdDocsWarning').addClass('hidden');
                const msg = xhr.status === 404 ? 'Documents are not available yet. Assign a technician to this ticket first.' : errMsg(xhr, 'Failed to load documents.');
                $('#tdDocsDynamic').html('<p class="text-[#9CA3AF] text-xs" style="padding: 14px 0;">' + esc(msg) + '</p>');
            });
    }

    function renderDocs() {
        const d = S.docs;
        $('#tdDocsWarning')
            .toggleClass('hidden', !d.supervisor_missing)
            .text('This ticket has no supervisor. Documents cannot be saved until a supervisor is assigned.');

        const html = ['agreement', 'service_form'].map(function (type) {
            const doc = (d.documents || {})[type];
            if (!doc) return '';
            const can = doc.can || {};
            const sig = doc.signatures || {};
            const saved = doc.status === 'saved';

            const chips = ROLES.map(function (r) {
                const on = !!sig[r[0]];
                return '<span class="rounded-full text-[10px] font-semibold" style="padding: 2px 8px; ' + (on ? 'background:#E7F3EE;color:#115640;' : 'background:#F0F0F0;color:#9CA3AF;') + '">'
                    + esc(r[0].charAt(0).toUpperCase() + r[0].slice(1)) + (on ? ' &#10003;' : '') + '</span>';
            }).join(' ');

            const note = saved ? 'Saved ' + fmtDateTime(doc.saved_at) : ((doc.completeness && doc.completeness.summary) || 'Ready to save');

            let buttons = '<button type="button" class="doc-open bg-white border border-[#D9D9D9] rounded-md cursor-pointer" data-doc="' + type + '" style="padding: 8px 14px;">'
                + (can.fill && !doc.locked ? 'Fill' : 'View') + '</button>';

            if (can.sign_supervisor && !doc.locked) {
                buttons += '<button type="button" class="doc-sign-supervisor bg-[#E7F3EE] text-[#115640] font-semibold rounded-md cursor-pointer" data-doc="' + type + '" style="padding: 8px 14px;">Supervisor Sign</button>';
            }
            if (can.save && !doc.locked) {
                const ready = !!(doc.completeness && doc.completeness.complete);
                buttons += '<button type="button" class="doc-save bg-[#115640] text-white font-semibold rounded-md cursor-pointer" data-doc="' + type + '" style="padding: 8px 14px;' + (ready ? '' : 'opacity:.5;') + '" ' + (ready ? '' : 'disabled') + '>Save</button>';
            }
            if (can.unlock && doc.locked) {
                buttons += '<button type="button" class="doc-unlock bg-white border border-[#EC221F] text-[#EC221F] font-semibold rounded-md cursor-pointer" data-doc="' + type + '" style="padding: 8px 14px;">Unlock</button>';
            }
            if (saved && (can.download_copies || []).length) {
                buttons += '<select class="doc-pdf border border-[#D9D9D9] rounded-md text-[#115640] font-semibold cursor-pointer" data-doc="' + type + '" style="padding: 8px 10px;">'
                    + '<option value="">Download PDF</option>'
                    + can.download_copies.map(function (c) { return '<option value="' + esc(c) + '">' + esc(COPY_LABEL[c] || c) + '</option>'; }).join('')
                    + '</select>';
            }

            let dispatches = '';
            if ((doc.dispatches || []).length) {
                dispatches = '<div class="rounded-md bg-[#F7F7F7]" style="margin-top: 10px; padding: 10px 12px;">'
                    + '<p class="text-[10px] uppercase font-semibold text-[#9CA3AF]" style="margin-bottom: 6px;">Email history</p>'
                    + doc.dispatches.map(function (x) {
                        const color = x.status === 'sent' ? '#115640' : (x.status === 'failed' ? '#EC221F' : '#757575');
                        return '<div class="flex items-center justify-between text-xs" style="gap: 8px; padding: 3px 0;">'
                            + '<span>' + esc(x.copy_label || x.recipient_role) + ' &middot; ' + esc(dash(x.recipient_email)) + '</span>'
                            + '<span class="flex items-center" style="gap: 8px;"><span style="color:' + color + '; font-weight: 600;">' + esc(x.status) + '</span>'
                            + (x.status === 'failed' || x.status === 'skipped' ? '<button type="button" class="doc-resend text-[#115640] underline cursor-pointer" data-doc="' + type + '" data-id="' + x.id + '">Resend</button>' : '')
                            + '</span></div>';
                    }).join('') + '</div>';
            }

            return '<div class="border-b border-[#EDEDED] last:border-b-0" style="padding: 14px 0;">'
                + '<div class="flex items-center justify-between flex-wrap" style="gap: 12px;">'
                + '<div><p class="font-semibold">' + TYPE_TITLE[type] + ' <span class="rounded-full text-[10px] font-semibold" style="padding: 2px 8px; ' + (saved ? 'background:#E7F3EE;color:#115640;' : 'background:#FFF3CD;color:#D97706;') + '">' + (saved ? 'Saved' : 'Draft') + '</span></p>'
                + '<p class="text-[#9CA3AF] text-xs" style="margin-top: 4px;">' + esc(note) + (doc.template_version ? ' &middot; Template v' + esc(doc.template_version) : '') + '</p>'
                + '<div style="margin-top: 6px; display: flex; gap: 6px; flex-wrap: wrap;">' + chips + '</div></div>'
                + '<div class="flex items-center flex-wrap" style="gap: 8px;">' + buttons + '</div></div>'
                + dispatches + '</div>';
        }).join('');

        $('#tdDocsDynamic').html(html || '<p class="text-[#9CA3AF] text-xs" style="padding: 14px 0;">No documents yet.</p>');
    }

    $(function () {
        // dijalankan setelah handler halaman mengisi currentTicket
        $(document).on('click', '.btn-ticket-details', function () {
            S.ticketId = (currentTicket || {}).id;
            $('#tdDocsDynamic').html('<p class="text-[#9CA3AF] text-xs" style="padding: 14px 0;">Loading documents...</p>');
            loadDocs();
            loadFinalCost();
        });
    });

    $(document).on('click', '.doc-open', function () {
        $(this).data('doc') === 'agreement' ? openAgreement() : openForm();
    });
    $(document).on('click', '.doc-sign-supervisor', function () { openSupervisorSign($(this).data('doc')); });
    $(document).on('click', '.doc-save', function () { saveDocument($(this).data('doc')); });

    $(document).on('click', '.doc-unlock', function () {
        const type = $(this).data('doc');
        Swal.fire({
            title: 'Unlock ' + TYPE_TITLE[type] + '?',
            text: 'All signatures on this document will be removed and it becomes a draft again.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#115640',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, unlock',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            call('POST', '/tickets/' + currentId() + '/documents/' + type + '/unlock')
                .done(function () { toast('Document unlocked'); reloadDocument(type); })
                .fail(function (xhr) { fail(xhr, 'Failed to unlock document.'); });
        });
    });

    $(document).on('change', '.doc-pdf', function () {
        const copy = $(this).val();
        const type = $(this).data('doc');
        if (!copy) return;
        window.open(API + '/tickets/' + currentId() + '/documents/' + type + '/pdf?copy=' + encodeURIComponent(copy), '_blank');
        $(this).val('');
    });

    $(document).on('click', '.doc-resend', function () {
        const type = $(this).data('doc');
        call('POST', '/tickets/' + currentId() + '/documents/' + type + '/dispatches/' + $(this).data('id') + '/resend')
            .done(function () { toast('Email queued'); loadDocs(); })
            .fail(function (xhr) { fail(xhr, 'Failed to resend email.'); });
    });

    // Save dokumen (POST .../save); callback dipanggil setelah sukses
    function saveDocument(type, done) {
        call('POST', '/tickets/' + currentId() + '/documents/' + type + '/save')
            .done(function (res) {
                const d = res.data || {};
                let text = TYPE_TITLE[type] + ' has been saved and locked.';
                if ((d.warnings || []).length) text += '\n' + d.warnings.join('\n');
                if (d.documentation_published) text += '\nDocumentation Published: both documents are saved.';
                Swal.fire({ title: 'Saved', text: text, icon: 'success', confirmButtonColor: '#115640' });
                reloadDocument(type);
                if (done) done();
            })
            .fail(function (xhr) { fail(xhr, 'Cannot save the document yet.'); });
    }

    // ---------- Service Agreement ----------
    function openAgreement() {
        const t = currentTicket || {};
        $('#saSubtitle').text((t.ticket_code || '-') + ' · ' + ((t.customer || {}).name || '-'));
        $('#saBody').addClass('hidden');
        $('#saLoading').removeClass('hidden').text('Loading...');
        showOnly('ticketAgreementView');
        loadAgreement();
    }

    function loadAgreement() {
        call('GET', '/tickets/' + currentId() + '/agreement')
            .done(function (res) { S.agreement = res.data; renderAgreement(); })
            .fail(function (xhr) { $('#saLoading').removeClass('hidden').text(errMsg(xhr, 'Failed to load agreement.')); });
    }

    function renderAgreement() {
        const d = S.agreement;
        const readOnly = !!d.locked || !(d.can && d.can.fill);
        const accepted = d.accepted_clauses || [];
        const tpl = d.template || {};

        $('#saLoading').addClass('hidden');
        $('#saBody').removeClass('hidden');
        statusBadge('#saStatusBadge', d);
        $('#saTitle').text(tpl.title || 'Service / Repair Work Agreement Sheet');
        $('#saTemplateVersion').text(tpl.version ? '(template v' + tpl.version + ')' : '');
        $('#saCustomerName').val(d.customer_name || '').prop('disabled', readOnly);
        $('#saClosing').text(tpl.closing_statement || '');

        $('#saSections').html((tpl.sections || []).map(function (sec) {
            const clauses = (sec.clauses || []).map(function (c, index) {
                return '<label class="flex items-start cursor-pointer" style="gap: 12px; padding: 8px 0;">'
                    + '<input type="checkbox" class="sa-clause" data-key="' + esc(c.key) + '" ' + (accepted.indexOf(c.key) >= 0 ? 'checked' : '') + ' ' + (readOnly ? 'disabled' : '') + ' style="width: 16px; height: 16px; margin-top: 2px;">'
                    + '<span class="text-[#4B5563]"><strong>' + esc(sec.code) + (index + 1) + '.</strong> ' + esc(c.text) + '</span></label>';
            }).join('');
            return '<div class="sa-section border-b border-[#EDEDED] last:border-b-0">'
                + '<div class="flex items-center justify-between" style="padding: 14px 16px; background: #FAFAFA;">'
                + '<label class="flex items-center cursor-pointer" style="gap: 12px;"><input type="checkbox" class="sa-section-check" ' + (readOnly ? 'disabled' : '') + ' style="width: 18px; height: 18px;">'
                + '<span class="font-bold">' + esc(sec.code) + '. ' + esc(sec.title) + '</span></label>'
                + '<span class="sa-count text-[#9CA3AF] text-xs"></span></div>'
                + '<div style="padding: 8px 16px 12px 36px;">' + clauses + '</div></div>';
        }).join(''));

        $('#saCheckAll').prop('disabled', readOnly);
        refreshAgreement();

        renderSignatureCards('#saSignatures', 'agreement', d);
        completenessBox('#saCompleteness', d);
        $('#saSaveDraft').toggleClass('hidden', readOnly);
        setSaveState($('#saSave'), d);
        $('#saSave').toggleClass('hidden', !(d.can && d.can.save) || d.locked);
    }

    function refreshAgreement() {
        let all = 0, checked = 0;
        $('#ticketAgreementView .sa-section').each(function () {
            const $c = $(this).find('.sa-clause');
            const done = $c.filter(':checked').length;
            all += $c.length;
            checked += done;
            $(this).find('.sa-count').text(done + ' / ' + $c.length + ' clauses');
            $(this).find('.sa-section-check')
                .prop('checked', $c.length > 0 && done === $c.length)
                .prop('indeterminate', done > 0 && done < $c.length);
        });
        $('#saCheckAll')
            .prop('checked', all > 0 && checked === all)
            .prop('indeterminate', checked > 0 && checked < all);
    }

    $(document).on('change', '#ticketAgreementView .sa-clause', refreshAgreement);
    $(document).on('change', '#ticketAgreementView .sa-section-check', function () {
        $(this).closest('.sa-section').find('.sa-clause').prop('checked', this.checked);
        refreshAgreement();
    });
    $(document).on('change', '#saCheckAll', function () {
        $('#ticketAgreementView .sa-clause').prop('checked', this.checked);
        refreshAgreement();
    });

    function saveAgreementDraft() {
        const keys = $('#ticketAgreementView .sa-clause:checked').map(function () { return $(this).data('key'); }).get();
        return call('PUT', '/tickets/' + currentId() + '/agreement', {
            accepted_clauses: keys,
            customer_name: ($('#saCustomerName').val() || '').trim() || null,
        }).done(function (res) {
            S.agreement = res.data;
            renderAgreement();
            loadDocs();
        });
    }

    $(document).on('click', '#saSaveDraft', function () {
        const $b = $(this).prop('disabled', true);
        saveAgreementDraft()
            .done(function () { toast('Draft saved'); })
            .fail(function (xhr) { fail(xhr, 'Failed to save draft.'); })
            .always(function () { $b.prop('disabled', false); });
    });

    $(document).on('click', '#saSave', function () {
        const canEdit = S.agreement && S.agreement.can && S.agreement.can.fill && !S.agreement.locked;
        const run = function () { saveDocument('agreement'); };
        if (canEdit) {
            saveAgreementDraft().done(run).fail(function (xhr) { fail(xhr, 'Failed to save draft.'); });
        } else {
            run();
        }
    });

    $(document).on('click', '.btn-sa-back', function () {
        showOnly('ticketDetailsView');
        loadDocs();
    });

    // ---------- Service Form ----------
    function openForm() {
        const t = currentTicket || {};
        $('#sfSubtitle').text((t.ticket_code || '-') + ' · ' + (((t.customer_product || {}).product || {}).name || '-'));
        $('#sfBody').addClass('hidden');
        $('#sfLoading').removeClass('hidden').text('Loading...');
        S.activeTab = null;
        S.openItem = null;
        showOnly('ticketServiceFormView');
        loadForm();
    }

    function loadForm() {
        return call('GET', '/tickets/' + currentId() + '/service-form')
            .done(function (res) { S.form = res.data; renderForm(); })
            .fail(function (xhr) { $('#sfLoading').removeClass('hidden').text(errMsg(xhr, 'Failed to load service form.')); });
    }

    function formReadOnly() {
        const f = S.form || {};
        return !!f.locked || !(f.can && f.can.fill);
    }

    function itemsFlat() {
        const out = [];
        ((S.form || {}).groups || []).forEach(function (g) {
            (g.items || []).forEach(function (it) { out.push({ group: g.code, item: it }); });
        });
        return out;
    }

    function itemSummary(item) {
        const parts = (item.fields || []).filter(function (f) { return f.value !== null && f.value !== undefined && f.value !== ''; })
            .map(function (f) { return f.label + ': ' + f.value + (f.unit || ''); });
        if (parts.length) return parts.join(' · ');
        return item.status === 'na' ? 'Not applicable' : (item.status ? 'Condition recorded' : 'Not entered');
    }

    function renderFieldCard(item, f, ro) {
        const id = 'sf-' + item.number + '-' + f.key;
        const label = esc(f.label) + (f.unit && f.type === 'number' ? ' (' + esc(f.unit) + ')' : '');
        const dis = ro ? 'disabled' : '';

        if (f.type === 'checklist') {
            const opts = (f.options || []).map(function (o) {
                const checked = f.is_multiple ? Array.isArray(f.value) && f.value.indexOf(o) >= 0 : f.value === o;
                return '<label class="flex items-center cursor-pointer" style="gap: 8px;"><input type="' + (f.is_multiple ? 'checkbox' : 'radio') + '" class="sf-input" name="' + id + '" data-key="' + esc(f.key) + '" data-type="checklist" data-multiple="' + (f.is_multiple ? '1' : '0') + '" value="' + esc(o) + '" ' + (checked ? 'checked' : '') + ' ' + dis + '> ' + esc(o) + '</label>';
            }).join('');
            return '<div class="rounded-lg border border-[#D9D9D9] bg-white lg:col-span-3" style="padding: 16px;">'
                + '<p class="font-semibold" style="margin-bottom: 10px;">' + label + '</p>'
                + '<div class="flex flex-wrap" style="gap: 24px;">' + opts + '</div>'
                + '<p class="text-[#757575] text-xs" style="margin-top: 12px;">Checklist and text items do not require a photo.</p></div>';
        }

        if (f.type === 'text') {
            const need = f.required_when ? '<span class="text-[#757575] text-xs"> (required when "' + esc(f.required_when) + '")</span>' : '';
            return '<div class="rounded-lg border border-[#D9D9D9] bg-white" style="padding: 14px;">'
                + '<p class="font-semibold" style="margin-bottom: 10px;">' + label + need + '</p>'
                + '<input type="text" class="sf-input w-full border border-[#D9D9D9] rounded-md" data-key="' + esc(f.key) + '" data-type="text" value="' + esc(f.value || '') + '" style="padding: 10px 12px;" ' + dis + '></div>';
        }

        // number: ketik bebas + daftar saran (datalist) bila ada
        const hasList = f.unit === 'V';
        const filled = f.value !== null && f.value !== undefined && f.value !== '';
        const photo = f.photo;
        const photoBlock = photo
            ? '<a href="' + esc(photo.url) + '" target="_blank"><img src="' + esc(photo.thumbnail_url || photo.url) + '" alt="Proof photo" style="margin-top: 10px; width: 100%; max-height: 120px; object-fit: cover; border-radius: 6px;"></a>'
            : '';
        return '<div class="sf-field rounded-lg border border-[#D9D9D9] bg-white" data-key="' + esc(f.key) + '" data-photo="' + (photo ? 1 : 0) + '" data-saved="' + esc(filled ? f.value : '') + '" style="padding: 14px;">'
            + '<p class="font-semibold" style="margin-bottom: 10px;">' + label + '</p>'
            + '<div class="sf-combo relative">'
            + '<input type="text" inputmode="decimal" class="sf-input sf-number w-full rounded-md focus:outline-none" data-key="' + esc(f.key) + '" data-type="number"'
            + (hasList ? ' list="sf-volt-list"' : '') + ' placeholder="Enter value" value="' + esc(filled ? f.value : '') + '" '
            + 'style="padding: 10px ' + (hasList ? '36' : '12') + 'px 10px 12px; border: 1px solid ' + (filled ? '#115640' : '#D9D9D9') + ';" ' + dis + '>'
            + (hasList ? '<i class="bi bi-chevron-down" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 13px;"></i>' : '')
            + '</div>'
            + ((f.units || []).length > 1 ? '<select class="sf-unit-choice w-full border border-[#D9D9D9] rounded-md" data-key="' + esc(f.key) + '" style="padding:8px;margin-top:8px;" ' + dis + '>' + f.units.map(function (unit) { return '<option ' + (unit === f.unit ? 'selected' : '') + '>' + esc(unit) + '</option>'; }).join('') + '</select>' : '')
            + (f.min_value !== null || f.max_value !== null ? '<p class="text-xs text-[#757575]" style="margin-top:6px;">' + (f.min_value !== null ? 'Min: ' + esc(f.min_value) : '') + (f.max_value !== null ? ' Max: ' + esc(f.max_value) : '') + '</p>' : '')
            + photoBlock
            + (ro ? '' : '<button type="button" class="sf-photo flex items-center justify-center w-full rounded-md border border-dashed border-[#9CA3AF] bg-white cursor-pointer text-xs" data-item="' + item.number + '" data-key="' + esc(f.key) + '" style="margin-top: 10px; padding: 14px; gap: 6px;"><i class="bi bi-plus"></i> ' + (photo ? 'Retake photo' : 'Upload photo') + '</button>')
            + '<p class="sf-helper text-[11px]" style="margin-top: 8px;"></p></div>';
    }

    function renderItemRows(item, ro) {
        const lockedNa = item.applicable === false;
        const statusKey = item.status || 'none';
        const statusText = item.status ? STATUS_LABEL[item.status] : 'Empty';
        const roItem = ro || lockedNa;
        const open = S.openItem === item.number;

        const statusButtons = ['ok', 'rep', 'na'].map(function (s) {
            const on = item.status === s;
            return '<button type="button" data-status="' + s + '" class="sf-status-btn rounded-lg border font-medium ' + (roItem ? '' : 'cursor-pointer ') + (on ? 'bg-[#115640] text-white border-[#115640]' : 'bg-white text-[#1E1E1E] border-[#D9D9D9]') + '" style="padding: 10px 28px; min-width: 76px;" ' + (roItem ? 'disabled' : '') + '>' + STATUS_LABEL[s] + '</button>';
        }).join('');

        const fields = (item.fields || []).map(function (f) { return renderFieldCard(item, f, roItem); }).join('');

        return '<tr class="sf-row border-b border-[#EDEDED] cursor-pointer" data-item="' + item.number + '">'
            + '<td class="text-[#9CA3AF]" style="padding: 14px 16px;">' + item.number + '</td>'
            + '<td class="font-semibold" style="padding: 14px 16px;">' + esc(item.name) + '</td>'
            + '<td class="text-[#757575]" style="padding: 14px 16px;">' + esc(dash(item.standard)) + '</td>'
            + '<td class="sf-result text-[#757575]" style="padding: 14px 16px;">' + esc(itemSummary(item)) + '</td>'
            + '<td style="padding: 14px 16px;"><div class="flex items-center justify-between" style="gap: 12px;">'
            + '<span class="sf-badge rounded-full font-semibold text-xs" style="padding: 3px 12px; ' + STATUS_STYLE[statusKey] + '">' + statusText + '</span>'
            + '<i class="bi bi-chevron-down text-[#115640] sf-chevron" style="transition: transform .15s;' + (open ? 'transform: rotate(180deg);' : '') + '"></i></div></td></tr>'
            + '<tr class="sf-detail ' + (open ? '' : 'hidden') + ' border-b border-[#EDEDED]" data-item="' + item.number + '" style="background: #FCFCFC;">'
            + '<td colspan="5" style="padding: 18px 24px 22px; white-space: normal;">'
            + '<p class="text-[#4B5563] text-xs" style="margin-bottom: 14px;">Standard: ' + esc(dash(item.standard)) + (lockedNa ? ' &middot; Not applicable to this machine type' : '') + '</p>'
            + '<div class="flex flex-wrap" style="gap: 10px; margin-bottom: 16px;">' + statusButtons + '</div>'
            + '<div class="grid grid-cols-1 lg:grid-cols-3" style="gap: 16px;">' + fields + '</div>'
            + '<div class="flex items-center justify-between" style="margin-top: 18px;">'
            + '<button type="button" class="sf-prev bg-white border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding: 8px 16px;">&larr; Previous</button>'
            + (roItem ? '<button type="button" class="sf-next bg-white border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding: 8px 18px;">Next &rarr;</button>'
                : '<button type="button" class="sf-next bg-[#115640] text-white font-semibold rounded-lg cursor-pointer" style="padding: 8px 18px;">Save &amp; Next</button>')
            + '</div></td></tr>';
    }

    function renderForm() {
        const f = S.form;
        const ro = formReadOnly();
        const s1 = f.section1 || {};
        const s2 = f.section2 || {};

        $('#sfLoading').addClass('hidden');
        $('#sfBody').removeClass('hidden');
        statusBadge('#sfStatusBadge', f);
        $('#sfSubtitle').text(f.template ? 'Template v' + f.template.version + ' · ' + f.template.title : '');

        $('#sfFormNumber').val(f.number || '');
        $('#sfServiceDate').val(s1.service_date || '');
        $('#sfTechnician').val(s1.technician || '');
        $('#sfSupervisor').val(s1.supervisor || '');
        $('#sfCustomer').val(s1.customer || '');
        $('#sfPic').val(s1.pic_name || '');
        $('#sfPhone').val(s1.phone || '');
        $('#sfAddress').val(s1.address || '');
        $('#sfMachineType').val(s2.machine_type || '');
        $('#sfSerialNumber').val(s2.serial_number || '');
        $('#sfServiceType').val(s2.service_type || '').prop('disabled', ro);
        $('.sf-unit').each(function () {
            const v = s2[$(this).data('unit')];
            $(this).val(v === null || v === undefined ? '' : v).prop('disabled', ro);
        });

        const groups = f.groups || [];
        if (!S.activeTab || !groups.some(function (g) { return g.code === S.activeTab; })) S.activeTab = groups.length ? groups[0].code : null;

        $('#sfItemCount').text(itemsFlat().length + ' inspection items');

        $('#sfTabs').html(groups.map(function (g) {
            const on = g.code === S.activeTab;
            return '<button type="button" data-tab="' + esc(g.code) + '" class="sf-tab rounded-md border cursor-pointer font-semibold ' + (on ? 'bg-[#115640] text-white border-[#115640]' : 'bg-white text-[#4B5563] border-[#D9D9D9]') + '" style="padding: 8px 14px;">'
                + esc(g.code) + '. ' + esc(g.title) + ' <span style="opacity:.7">(' + (g.items || []).length + ')</span></button>';
        }).join(''));

        $('#sfPanels').html('<datalist id="sf-volt-list">' + Array.from({ length: 41 }, function (_, i) { return '<option value="' + (360 + i) + '">'; }).join('') + '</datalist>'
            + groups.map(function (g) {
                return '<div class="sf-panel overflow-x-auto ' + (g.code === S.activeTab ? '' : 'hidden') + '" data-tab="' + esc(g.code) + '">'
                    + '<table class="w-full whitespace-nowrap"><thead><tr class="text-left border-y border-[#EDEDED] text-[#1E1E1E]" style="background: #FAFAFA;">'
                    + '<th style="padding: 12px 16px;">No</th><th style="padding: 12px 16px;">Inspection Item &amp; Component</th>'
                    + '<th style="padding: 12px 16px;">Parameter / Reference Standard</th><th style="padding: 12px 16px;">Measurement Result / Condition</th>'
                    + '<th style="padding: 12px 16px;">Status</th></tr></thead><tbody>'
                    + (g.items || []).map(function (it) { return renderItemRows(it, ro); }).join('')
                    + '</tbody></table></div>';
            }).join(''));

        $('.sf-field').each(function () { refreshHelper($(this)); });

        window.ServiceDiagnosisEditor.mount({ticketId: currentId(), number: f.number, data: f.diagnosis, readOnly: ro,
            readOnlyReason: f.locked ? 'This Service Form is saved and locked. Diagnosis and attachments are read-only.' : 'Only the technician assigned to this ticket can enter diagnosis and attach photos. This account has view-only access.',
            persist: function () { return putForm({service_type: $('#sfServiceType').val() || null, unit: collectUnit(), inspections: collectAll(), diagnosis: window.ServiceDiagnosisEditor.collect()}); },
            reload: function () { return loadForm(); }
        });
        renderSignatureCards('#sfSignatures', 'service_form', f);
        completenessBox('#sfCompleteness', f);
        $('#sfSaveDraft').toggleClass('hidden', ro);
        $('#sfSaveForm').toggleClass('hidden', !(f.can && f.can.save) || !!f.locked);
        setSaveState($('#sfSaveForm'), f);
    }

    function refreshHelper($field) {
        const hasValue = String($field.find('.sf-number').val() || '').trim() !== '';
        const hasPhoto = Number($field.attr('data-photo')) === 1;
        const $h = $field.find('.sf-helper');
        if (hasValue && hasPhoto) $h.text('Proof photo attached').css('color', '#115640');
        else if (hasValue) $h.text('Photo required because the value is filled in').css('color', '#EC221F');
        else $h.text('Phone camera only (scan QR), no file upload').css('color', '#757575');
    }

    // ---- form interactions ----
    $(document).on('click', '.btn-sf-back', function () {
        showOnly('ticketDetailsView');
        loadDocs();
    });

    $(document).on('click', '.sf-tab', function () {
        S.activeTab = $(this).data('tab');
        $('.sf-tab').removeClass('bg-[#115640] text-white border-[#115640]').addClass('bg-white text-[#4B5563] border-[#D9D9D9]');
        $(this).removeClass('bg-white text-[#4B5563] border-[#D9D9D9]').addClass('bg-[#115640] text-white border-[#115640]');
        $('.sf-panel').addClass('hidden').filter('[data-tab="' + S.activeTab + '"]').removeClass('hidden');
    });

    function toggleItem(number) {
        const wasOpen = S.openItem === number;
        S.openItem = wasOpen ? null : number;
        $('.sf-detail').addClass('hidden');
        $('.sf-chevron').css('transform', '');
        if (!wasOpen) {
            $('.sf-detail[data-item="' + number + '"]').removeClass('hidden');
            $('.sf-row[data-item="' + number + '"] .sf-chevron').css('transform', 'rotate(180deg)');
        }
    }

    $(document).on('click', '.sf-row', function () { toggleItem(Number($(this).data('item'))); });

    $(document).on('click', '.sf-status-btn', function () {
        if ($(this).prop('disabled')) return;
        const status = $(this).data('status');
        const $detail = $(this).closest('.sf-detail');
        $detail.find('.sf-status-btn').removeClass('bg-[#115640] text-white border-[#115640]').addClass('bg-white text-[#1E1E1E] border-[#D9D9D9]');
        $(this).removeClass('bg-white text-[#1E1E1E] border-[#D9D9D9]').addClass('bg-[#115640] text-white border-[#115640]');
        $detail.prev('.sf-row').find('.sf-badge').text(STATUS_LABEL[status]).attr('style', 'padding: 3px 12px; ' + STATUS_STYLE[status]);
    });

    $(document).on('input', '.sf-number', function () {
        $(this).css('border-color', String($(this).val() || '').trim() !== '' ? '#115640' : '#D9D9D9');
        refreshHelper($(this).closest('.sf-field'));
    });

    // kumpulkan nilai satu poin dari DOM
    function collectItem(number) {
        const $d = $('.sf-detail[data-item="' + number + '"]');
        const status = $d.find('.sf-status-btn.bg-\\[\\#115640\\]').data('status') || null;
        const values = {};
        $d.find('.sf-input').each(function () {
            const key = $(this).data('key');
            const type = $(this).data('type');
            if (type === 'checklist') {
                if ($(this).data('multiple') === 1) {
                    if (!(key in values)) values[key] = [];
                    if ($(this).is(':checked')) values[key].push($(this).val());
                } else {
                    if ($(this).is(':checked')) values[key] = $(this).val();
                    else if (!(key in values)) values[key] = null;
                }
            } else {
                const v = String($(this).val() || '').trim();
                values[key] = v === '' ? null : v;
            }
        });
        $d.find('.sf-unit-choice').each(function () {
            const key = $(this).data('key');
            if (values[key] !== null && values[key] !== undefined) values[key] = { value: values[key], unit: $(this).val() };
        });
        return { item_number: number, status: status, values: values };
    }

    function collectAll() {
        const out = [];
        itemsFlat().forEach(function (x) {
            if (x.item.applicable === false) return;
            const c = collectItem(x.item.number);
            const hasValue = Object.keys(c.values).some(function (k) { return c.values[k] !== null && c.values[k] !== ''; });
            if (c.status || hasValue) out.push(c);
        });
        return out;
    }

    function collectUnit() {
        const unit = {};
        $('.sf-unit').each(function () {
            const v = String($(this).val() || '').trim();
            unit[$(this).data('unit')] = v === '' ? null : v;
        });
        return unit;
    }

    function putForm(body) {
        return call('PUT', '/tickets/' + currentId() + '/service-form', body).done(function (res) {
            S.form = res.data;
            renderForm();
            loadDocs();
        });
    }

    function saveItem(number) {
        return putForm({ inspections: [collectItem(number)] });
    }

    function step(number, direction, withSave) {
        const flat = itemsFlat();
        const idx = flat.findIndex(function (x) { return x.item.number === number; });
        const target = flat[idx + direction];

        const go = function () {
            if (!target) { S.openItem = null; renderForm(); return; }
            S.openItem = target.item.number;
            S.activeTab = target.group;
            renderForm();
            const el = $('.sf-row[data-item="' + target.item.number + '"]')[0];
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };

        if (withSave && !formReadOnly()) {
            saveItem(number).done(go).fail(function (xhr) { fail(xhr, 'Failed to save this item.'); });
        } else {
            go();
        }
    }

    $(document).on('click', '.sf-next', function () {
        step(Number($(this).closest('.sf-detail').data('item')), 1, true);
    });
    $(document).on('click', '.sf-prev', function () {
        step(Number($(this).closest('.sf-detail').data('item')), -1, false);
    });

    $(document).on('click', '#sfSaveDraft', function () {
        const $b = $(this).prop('disabled', true);
        putForm({
            service_type: $('#sfServiceType').val() || null,
            unit: collectUnit(),
            inspections: collectAll(),
            diagnosis: window.ServiceDiagnosisEditor.collect(),
        })
            .done(function () { toast('Draft saved'); })
            .fail(function (xhr) { fail(xhr, 'Failed to save draft.'); })
            .always(function () { $b.prop('disabled', false); });
    });

    $(document).on('click', '#sfSaveForm', function () {
        const run = function () { saveDocument('service_form'); };
        if (!formReadOnly()) {
            putForm({ service_type: $('#sfServiceType').val() || null, unit: collectUnit(), inspections: collectAll(), diagnosis: window.ServiceDiagnosisEditor.collect() })
                .done(run)
                .fail(function (xhr) { fail(xhr, 'Failed to save draft.'); });
        } else {
            run();
        }
    });

    // ---- foto bukti via QR ----
    let pollTimer = null;
    function stopPoll() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

    function hasPhoto(itemNumber, key) {
        const x = itemsFlat().find(function (y) { return y.item.number === itemNumber; });
        if (!x) return false;
        const f = (x.item.fields || []).find(function (z) { return z.key === key; });
        return !!(f && f.photo);
    }

    function openQr(itemNumber, key) {
        call('POST', '/tickets/' + currentId() + '/service-form/photo-links', { item_number: itemNumber, field_key: key })
            .done(function (res) {
                const url = (res.data || {}).url;
                const wasPhoto = hasPhoto(itemNumber, key);
                const onOpen = function () {
                    const box = document.getElementById('qrBox');
                    if (box && typeof QRCode !== 'undefined') {
                        new QRCode(box, { text: url, width: 200, height: 200 });
                    }
                    stopPoll();
                    let lastPhotoId = null;
                    const x = itemsFlat().find(function (y) { return y.item.number === itemNumber; });
                    const f0 = x && (x.item.fields || []).find(function (z) { return z.key === key; });
                    lastPhotoId = f0 && f0.photo ? f0.photo.id : null;
                    pollTimer = setInterval(function () {
                        call('GET', '/tickets/' + currentId() + '/service-form').done(function (r) {
                            const flat = [];
                            (r.data.groups || []).forEach(function (g) { (g.items || []).forEach(function (it) { flat.push(it); }); });
                            const it = flat.find(function (y) { return y.number === itemNumber; });
                            const f = it && (it.fields || []).find(function (z) { return z.key === key; });
                            const pid = f && f.photo ? f.photo.id : null;
                            if (pid && pid !== lastPhotoId) {
                                stopPoll();
                                S.form = r.data;
                                renderForm();
                                Swal.close();
                            }
                        });
                    }, 3000);
                };
                Swal.fire({
                    title: wasPhoto ? 'Retake photo with phone' : 'Take photo with phone',
                    html: '<div id="qrBox" style="display:flex;justify-content:center;margin:8px 0;"></div>'
                        + '<p style="font-size:12px;color:#757575;word-break:break-all;">' + esc(url) + '</p>'
                        + '<p style="font-size:12px;">Scan the QR code with your phone camera. This link is valid for 15 minutes. Waiting for the photo...</p>',
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#115640',
                    didOpen: onOpen,
                    onOpen: onOpen,
                    willClose: stopPoll,
                    onClose: stopPoll,
                }).then(function () { stopPoll(); loadForm(); });
            })
            .fail(function (xhr) { fail(xhr, 'Failed to create the photo link.'); });
    }

    $(document).on('click', '.sf-photo', function () {
        const itemNumber = Number($(this).data('item'));
        const key = $(this).data('key');
        const $field = $(this).closest('.sf-field');
        const typed = String($field.find('.sf-number').val() || '').trim();

        if (typed === '') {
            return Swal.fire({ title: 'Enter the value first', text: 'A photo can only be attached to a saved value.', icon: 'info', confirmButtonColor: '#115640' });
        }

        // simpan poin ini dulu agar nilai tersimpan, baru minta link foto
        saveItem(itemNumber)
            .done(function () { openQr(itemNumber, key); })
            .fail(function (xhr) { fail(xhr, 'Failed to save the value.'); });
    });

    // ---------- Supervisor Sign ----------
    function openSupervisorSign(type) {
        S.ssDoc = type;
        const t = currentTicket || {};
        $('#ssTitle').text('Supervisor Signature · ' + TYPE_TITLE[type]);
        $('#ssCrumb').text(TYPE_TITLE[type]);
        $('#ssTicket').text(t.ticket_code || '-');
        $('#ssCustomer').text((t.customer || {}).name || '-');
        $('#ssMachine').text(((t.customer_product || {}).product || {}).name || '-');
        $('#ssTechnician').text((t.technician || {}).name || '-');
        $('#ssName').val((S.docs && S.docs.supervisor && S.docs.supervisor.name) || '');
        $('#ssError').addClass('hidden');
        $('#ssCompleteness').text('Loading...');
        showOnly('ticketSupervisorSignView');

        const path = type === 'agreement' ? '/agreement' : '/service-form';
        call('GET', '/tickets/' + currentId() + path)
            .done(function (res) { S.ssData = res.data; renderSupervisorSign(); })
            .fail(function (xhr) { fail(xhr, 'Failed to load the document.'); });
    }

    function renderSupervisorSign() {
        const d = S.ssData;
        const sig = (d.signatures || {}).supervisor;
        const can = d.can || {};
        const canSign = !!can.sign_supervisor && !d.locked;

        $('#ssStatusBadge').text(sig ? 'Signed' : 'Awaiting signature')
            .css(sig ? { background: '#E7F3EE', color: '#115640' } : { background: '#FFF3CD', color: '#D97706' });
        $('#ssCompleteness').text(d.locked ? 'Document is saved and locked.' : ((d.completeness || {}).summary || 'All requirements are complete.'));

        $('#ssSignArea').toggleClass('hidden', !canSign);
        $('#ssSign').toggleClass('hidden', !canSign);
        $('#ssSignedArea').toggleClass('hidden', !sig);
        if (sig && sig.url) $('#ssSignedImage').attr('src', sig.url);

        if (canSign) {
            const pad = makePad(document.getElementById('ssCanvas'));
            pad.onChange = function () {
                $('#ssPlaceholder').toggleClass('hidden', pad.ink);
                if (pad.ink) $('#ssError').addClass('hidden');
            };
            pad.clear();
        }

        $('#ssSave').toggleClass('hidden', !(can.save && !d.locked));
        setSaveState($('#ssSave'), d);
    }

    $(document).on('click', '#ssClear', function () {
        const c = document.getElementById('ssCanvas');
        if (c && c._pad) c._pad.clear();
    });

    $(document).on('click', '#ssSign', function () {
        const c = document.getElementById('ssCanvas');
        const pad = c && c._pad;
        if (!pad || !pad.ink) return $('#ssError').removeClass('hidden');

        const $b = $(this).prop('disabled', true);
        call('POST', '/tickets/' + currentId() + '/documents/' + S.ssDoc + '/signatures', { role: 'supervisor', signature: pad.dataUrl() })
            .done(function () {
                toast('Signature saved');
                openSupervisorSign(S.ssDoc);
                loadDocs();
            })
            .fail(function (xhr) { fail(xhr, 'Failed to save signature.'); })
            .always(function () { $b.prop('disabled', false); });
    });

    $(document).on('click', '#ssSave', function () {
        saveDocument(S.ssDoc, function () { openSupervisorSign(S.ssDoc); });
    });

    $(document).on('click', '.btn-ss-back', function () {
        showOnly('ticketDetailsView');
        loadDocs();
    });
    $(document).on('click', '.btn-ss-list', function () {
        showOnly('ticketListView');
    });
})();
</script>
