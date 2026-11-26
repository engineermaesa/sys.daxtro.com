// AS-TKT-01/02: tampilan roster technician (daftar ticket dan detail).
// Sumber: row.technicians [{id,name}] (urut assignment); fallback row.technician (objek atau string, teknisi utama).
(function () {
    function names(row) {
        const list = row && row.technicians && row.technicians.length ? row.technicians : [row && row.technician];
        return list.map(function (t) { return typeof t === 'string' ? t : t && t.name; }).filter(Boolean);
    }

    function escape(value) {
        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function listText(row) {
        return names(row).join(', ');
    }

    // dua nama pertama + "+n"; daftar lengkap saat hover (title) atau tap
    function cellHtml(row) {
        const all = names(row);
        if (!all.length) return '-';
        const short = escape(all.slice(0, 2).join(', '));
        if (all.length <= 2) return short;
        const full = escape(all.join(', '));
        return '<span class="as-tech" title="' + full + '">'
            + '<span class="as-tech-short">' + short + ' </span>'
            + '<span class="as-tech-full" style="display:none;">' + full + ' </span>'
            + '<button type="button" class="as-tech-more" aria-expanded="false" aria-label="Show all technicians" data-more="+' + (all.length - 2) + '" '
            + 'style="display:inline-block;padding:0 6px;border:0;border-radius:6px;background:#E7F3EE;color:#115640;font-weight:600;cursor:pointer;">+'
            + (all.length - 2) + '</button></span>';
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.as-tech-more');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        const wrap = button.closest('.as-tech');
        const open = button.getAttribute('aria-expanded') !== 'true';
        wrap.querySelector('.as-tech-short').style.display = open ? 'none' : '';
        wrap.querySelector('.as-tech-full').style.display = open ? '' : 'none';
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.textContent = open ? 'Less' : button.getAttribute('data-more');
    }, true);

    window.AsTechnician = { names: names, listText: listText, cellHtml: cellHtml };
})();
