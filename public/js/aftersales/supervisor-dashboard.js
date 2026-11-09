(function () {
    const C = {
        green: "#14573F",
        sage: "#7FAF9A",
        gold: "#D9B65B",
        grey: "#C9CFCB",
    };
    const HEAT = ["#E6F1EC", "#BFDCCD", "#8FBFA8", "#4F9374", "#14573F"];
    const root = document.getElementById("spv-dashboard");
    if (!root) return;

    let spvData = null;
    let aborter,
        generation = 0,
        drillAborter;
    const escapeHtml = (value) =>
        String(value ?? "").replace(
            /[&<>"']/g,
            (c) =>
                ({
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#39;",
                })[c],
        );
    const percent = (value) => (value === null ? "—" : value + "%");
    const selectedMode = (name) =>
        root.querySelector(`[data-toggle="${name}"] [aria-pressed="true"]`)
            ?.dataset.value;

    const fmtRp = (n) => "Rp " + (n / 1e6).toFixed(1) + "M";
    const charts = {};
    const mount = (id, opts) => {
        charts[id]?.destroy();
        charts[id] = new ApexCharts(document.getElementById(id), opts);
        charts[id].render();
    };
    const baseChart = {
        toolbar: { show: false },
        fontFamily: "inherit",
        animations: { speed: 400 },
    };

    function renderKpis() {
        Object.entries(spvData.kpi).forEach(([key, k]) => {
            const el = root.querySelector(`[data-kpi="${key}"]`);
            el.querySelector("[data-kpi-value]").textContent = fmtRp(k.value);
            const d = el.querySelector("[data-kpi-delta]");
            d.textContent =
                k.delta === null
                    ? "—"
                    : (k.delta > 0 ? "▴ " : k.delta < 0 ? "▾ " : "") +
                      Math.abs(k.delta) +
                      "%";
            const tone =
                key === "warranty"
                    ? "text-[#B7791F]"
                    : k.delta >= 0
                      ? "text-[#1F8A5B]"
                      : "text-[#C0392B]";
            d.className = "font-semibold " + tone;
        });
        document.getElementById("spv-unclassified").textContent =
            spvData.unclassified;
    }

    function renderRevenue() {
        mount("spv-chart-revenue", {
            chart: { ...baseChart, type: "bar", height: 280 },
            series: Object.entries(spvData.revenue).map(([name, data]) => ({
                name,
                data,
            })),
            colors: [C.green, C.sage, C.gold],
            plotOptions: { bar: { columnWidth: "55%", borderRadius: 2 } },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ["transparent"] },
            xaxis: {
                categories: spvData.months,
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: { show: false },
            grid: { show: false },
            legend: {
                position: "top",
                horizontalAlign: "left",
                markers: { radius: 2, size: 5 },
            },
            tooltip: { y: { formatter: fmtRp } },
        });
    }

    function renderSplit() {
        const s = spvData.split;
        mount("spv-chart-split", {
            chart: { ...baseChart, type: "donut", height: 260 },
            series: s.values,
            labels: s.labels,
            colors: [C.green, C.sage, C.gold, C.grey],
            dataLabels: { enabled: false },
            stroke: { width: 2, colors: ["#fff"] },
            legend: { position: "bottom", markers: { size: 5 } },
            plotOptions: {
                pie: {
                    donut: {
                        size: "68%",
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: "11px",
                                color: "#8A948F",
                                offsetY: 18,
                            },
                            value: {
                                show: true,
                                fontSize: "24px",
                                fontWeight: 700,
                                color: C.green,
                                offsetY: -14,
                                formatter: () => percent(s.ratio),
                            },
                            total: {
                                show: true,
                                label: "warranty ratio",
                                color: "#8A948F",
                                formatter: () => percent(s.ratio),
                            },
                        },
                    },
                },
            },
        });
    }

    function renderWarranty(mode = "model") {
        const rows = spvData.warranty[mode];
        const max = Math.max(1, ...rows.map((r) => r[1]));
        document.getElementById("spv-warranty-list").innerHTML = rows
            .map(
                ([name, v], i) => `
            <div class="flex items-center gap-4 text-sm" data-warranty-row="${i}" role="link" tabindex="0" title="${escapeHtml(name)}">
                <span class="w-20 shrink-0 text-[#2B3441] truncate">${escapeHtml(name)}</span>
                <div class="flex-1 h-2 rounded-full bg-[#E6EBE8] overflow-hidden">
                    <div class="h-full rounded-full bg-[#14573F]" style="width:${(v / max) * 100}%"></div>
                </div>
                <span class="w-20 text-right font-semibold text-[#2B3441] tabular-nums">${fmtRp(v)}</span>
            </div>`,
            )
            .join("");
    }

    function renderHealth() {
        const h = spvData.health;
        const rings = {
            sla: [h.sla ?? 0, percent(h.sla)],
            firstVisit: [h.firstVisit ?? 0, percent(h.firstVisit)],
            rating: [
                ((h.rating ?? 0) / h.ratingMax) * 100,
                h.rating === null ? "—" : h.rating + "/" + h.ratingMax,
            ],
        };
        Object.entries(rings).forEach(([key, [pct, text]]) => {
            const host = root.querySelector(
                `[data-ring="${key}"] [data-ring-chart]`,
            );
            charts["ring-" + key]?.destroy();
            charts["ring-" + key] = new ApexCharts(host, {
                chart: {
                    ...baseChart,
                    type: "radialBar",
                    height: 120,
                    sparkline: { enabled: true },
                },
                series: [pct],
                colors: [C.green],
                fill: { type: "solid", opacity: 1 },
                plotOptions: {
                    radialBar: {
                        hollow: { size: "52%" },
                        track: {
                            background: "#E6EBE8",
                            strokeWidth: "100%",
                            margin: 0,
                        },
                        dataLabels: {
                            name: { show: false },
                            value: {
                                offsetY: 6,
                                fontSize: "17px",
                                fontWeight: 700,
                                color: "#2B3441",
                                formatter: () => text,
                            },
                        },
                    },
                },
                stroke: { lineCap: "round" },
            });
            charts["ring-" + key].render();
        });
        const setBar = (key, pct) => {
            const el = root.querySelector(`[data-progress="${key}"]`);
            el.querySelector("[data-progress-value]").textContent =
                percent(pct);
            el.querySelector("[data-progress-bar]").style.width =
                Math.min(100, pct ?? 0) + "%";
        };
        setBar("recoming", h.recoming);
        setBar("survey", h.survey);
    }

    function renderRecoming() {
        const r = spvData.recoming;
        mount("spv-chart-recoming", {
            chart: { ...baseChart, type: "bar", height: 280 },
            series: [
                { name: "Counted", data: r.counted },
                { name: "Not counted", data: r.excluded },
            ],
            colors: [C.green, C.grey],
            plotOptions: { bar: { columnWidth: "40%", borderRadius: 2 } },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ["transparent"] },
            xaxis: {
                categories: r.labels,
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: { show: false },
            grid: { show: false },
            legend: {
                position: "top",
                horizontalAlign: "left",
                markers: { radius: 2, size: 5 },
            },
        });
    }

    function renderHeatmap(mode = "machine") {
        const p = spvData.problem[mode];
        const maxTotal = Math.max(1, ...p.totals);
        const maxCell = Math.max(1, ...p.matrix.flat());
        const tone = (value) =>
            HEAT[
                value === 0 ? 0 : Math.max(1, Math.ceil((value / maxCell) * 4))
            ];
        const barTone = [HEAT[4], "#3F9272", "#5FA68A", "#86B5A2"];
        document.getElementById("spv-problem-bars").innerHTML = p.rows
            .map(
                (name, i) => `
            <div class="spv-problem-row">
                <span class="spv-problem-label">${escapeHtml(name)}</span>
                <div class="spv-problem-track">
                    <div class="spv-problem-bar" style="width:${(p.totals[i] / maxTotal) * 100}%;background:${barTone[i] ?? HEAT[2]}" title="${p.totals[i]} tickets"></div>
                </div>
            </div>`,
            )
            .join("");

        const cols = `grid-template-columns:repeat(${Math.max(1, p.cols.length)},minmax(0,1fr))`;
        document.getElementById("spv-heatmap-head").setAttribute("style", cols);
        document.getElementById("spv-heatmap-head").innerHTML = (
            p.column_labels ?? p.cols
        )
            .map(
                (c) => `<span title="${escapeHtml(c)}">${escapeHtml(c)}</span>`,
            )
            .join("");
        document.getElementById("spv-heatmap-body").innerHTML = p.matrix
            .map(
                (row, i) => `
            <div class="spv-heatmap-row" style="${cols}">
                ${row.map((v, j) => `<button type="button" class="spv-heatmap-cell" style="background:${tone(v)}" title="${escapeHtml(p.rows[i])} / ${escapeHtml(p.cols[j])}: ${v} tickets" aria-label="${escapeHtml(p.rows[i])} / ${escapeHtml(p.cols[j])}: ${v} tickets" data-cell="${i},${j}"></button>`).join("")}
            </div>`,
            )
            .join("");
    }

    function initDateRange(initial) {
        const host = root.querySelector("[data-spv-date-filter]");
        const toggle = host.querySelector("[data-date-toggle]");
        const dropdown = host.querySelector("[data-date-dropdown]");
        const apply = host.querySelector("[data-date-apply]");
        const period = host.querySelector('[data-filter="period"]');
        const start = host.querySelector('[data-filter="start_date"]');
        const end = host.querySelector('[data-filter="end_date"]');
        const today = new Date().toLocaleDateString("sv-SE", { timeZone: "Asia/Jakarta" });
        const [year, month] = today.split("-").map(Number);
        const iso = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, "0"), String(date.getDate()).padStart(2, "0")].join("-");
        const validDate = (value) => {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(value || "")) return false;
            const [y, m, d] = value.split("-").map(Number);
            return iso(new Date(y, m - 1, d)) === value && value >= "2000-01-01" && value <= "2100-12-31";
        };
        let from = iso(new Date(year, month - 1, 1));
        let to = iso(new Date(year, month, 0));
        const requested = initial.get("period");
        if (requested === "custom" && validDate(initial.get("start_date")) && validDate(initial.get("end_date")) && initial.get("start_date") <= initial.get("end_date")) {
            period.value = "custom";
            from = start.value = initial.get("start_date");
            to = end.value = initial.get("end_date");
        } else if (requested === "last_month") {
            period.value = requested;
            from = iso(new Date(year, month - 2, 1));
            to = iso(new Date(year, month - 1, 0));
        } else if (requested === "this_year") {
            period.value = requested;
            from = `${year}-01-01`;
            to = `${year}-12-31`;
        } else if (requested === "this_quarter") {
            period.value = requested;
            const firstMonth = Math.floor((month - 1) / 3) * 3;
            from = iso(new Date(year, firstMonth, 1));
            to = iso(new Date(year, firstMonth + 3, 0));
        } else if (requested === "last_7_days") {
            period.value = requested;
            const [y, m, d] = today.split("-").map(Number);
            from = iso(new Date(y, m - 1, d - 6));
            to = today;
        }
        let applied = [from, to];
        const displayDate = (value) => {
            const [y, m, d] = value.split("-").map(Number);
            return new Date(y, m - 1, d).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
        };
        const updateLabel = () => { host.querySelector("[data-date-label]").textContent = applied.map(displayDate).join(" – "); };
        updateLabel();
        if (typeof flatpickr === "undefined") { toggle.disabled = true; return; }
        const picker = flatpickr(host.querySelector("[data-date-input]"), {
            mode: "range", inline: true, dateFormat: "Y-m-d", defaultDate: applied,
            minDate: "2000-01-01", maxDate: "2100-12-31",
            appendTo: host.querySelector("[data-date-calendar]"),
            onChange: (dates) => { apply.disabled = dates.length !== 2; },
        });
        const close = () => {
            dropdown.classList.add("hidden");
            toggle.setAttribute("aria-expanded", "false");
            picker.setDate(applied, false);
            apply.disabled = false;
        };
        toggle.addEventListener("click", () => {
            if (toggle.getAttribute("aria-expanded") === "true") { close(); return; }
            dropdown.classList.remove("hidden");
            toggle.setAttribute("aria-expanded", "true");
        });
        host.querySelector("[data-date-cancel]").addEventListener("click", close);
        apply.addEventListener("click", () => {
            if (picker.selectedDates.length !== 2) return;
            applied = picker.selectedDates.map((date) => picker.formatDate(date, "Y-m-d"));
            period.value = "custom";
            [start.value, end.value] = applied;
            updateLabel();
            close();
            load();
        });
        document.addEventListener("click", (event) => { if (!host.contains(event.target)) close(); });
        host.addEventListener("keydown", (event) => {
            if (event.key === "Escape") { close(); toggle.focus(); }
        });
    }

    function params() {
        const result = new URLSearchParams();
        root.querySelectorAll("[data-filter]").forEach((el) => {
            if (el.value && el.value !== "all")
                result.set(el.dataset.filter, el.value);
        });
        return result;
    }

    function populateOptions() {
        ["region", "category"].forEach((key, i) => {
            const select = root.querySelector(`[data-filter="${key}"]`);
            const value = select.value || "";
            if (window.jQuery?.fn?.select2) {
                const dropdown = window.jQuery(select);
                dropdown.off("change.spvDashboard");
                if (dropdown.data("select2")) dropdown.select2("destroy");
                dropdown.empty();
            }
            select.replaceChildren(
                new Option(i ? "All categories" : "All regions", ""),
                ...spvData.options[i ? "categories" : "regions"].map(
                    (row) => new Option(row.name, row.id),
                ),
            );
            select.value = [...select.options].some((option) => option.value === value) ? value : "";
            bindDimensionFilter(select);
        });
    }

    function bindDimensionFilter(select) {
        if (!window.jQuery?.fn?.select2) {
            select.removeEventListener("change", load);
            select.addEventListener("change", load);
            return;
        }
        window.jQuery(select).select2({
            width: "220px",
            minimumResultsForSearch: 0,
            minimumInputLength: 0,
            dropdownAutoWidth: true,
            dropdownParent: window.jQuery(root),
            language: {
                searching: () => "Searching…",
                noResults: () => "No results found",
            },
        }).on("change.spvDashboard", load);
    }

    function render() {
        renderKpis();
        renderRevenue();
        renderSplit();
        renderWarranty(selectedMode("warranty"));
        renderHealth();
        renderRecoming();
        renderHeatmap(selectedMode("problem"));
    }

    function clearData() {
        spvData = null;
        Object.values(charts).forEach((chart) => chart.destroy());
        Object.keys(charts).forEach((key) => delete charts[key]);
        root.querySelectorAll(
            "[data-kpi-value], [data-kpi-delta], [data-progress-value]",
        ).forEach((el) => {
            el.textContent = "—";
        });
        root.querySelectorAll("[data-progress-bar]").forEach((el) => {
            el.style.width = "0%";
        });
        document.getElementById("spv-unclassified").textContent = "—";
        [
            "spv-warranty-list",
            "spv-problem-bars",
            "spv-heatmap-head",
            "spv-heatmap-body",
        ].forEach((id) => document.getElementById(id).replaceChildren());
    }

    async function load() {
        const requestGeneration = ++generation;
        aborter?.abort();
        aborter = new AbortController();
        spvData = null;
        root.setAttribute("aria-busy", "true");
        try {
            const query = params();
            const response = await fetch(root.dataset.api + "?" + query, {
                headers: { Accept: "application/json" },
                signal: aborter.signal,
            });
            if (!response.ok)
                throw new Error("Unable to load dashboard. Please try again.");
            const payload = await response.json();
            if (requestGeneration !== generation) return;
            spvData = payload.data;
            populateOptions();
            render();
            history.replaceState(
                null,
                "",
                location.pathname + "?" + params() + location.hash,
            );
        } catch (error) {
            if (error.name === "AbortError" || requestGeneration !== generation)
                return;
            clearData();
            if (window.Swal) {
                const choice = await Swal.fire({
                    icon: "error",
                    title: "Dashboard unavailable",
                    text: error.message,
                    showCancelButton: true,
                    confirmButtonText: "Retry",
                });
                if (choice.isConfirmed && requestGeneration === generation)
                    load();
            }
        } finally {
            if (requestGeneration === generation)
                root.setAttribute("aria-busy", "false");
        }
    }

    async function openTickets(filters, page = 1) {
        if (
            !spvData ||
            !window.Swal ||
            root.getAttribute("aria-busy") === "true"
        )
            return;
        drillAborter?.abort();
        const controller = (drillAborter = new AbortController());
        const query = params();
        Object.entries(filters).forEach(([key, value]) =>
            query.set(key, value),
        );
        query.set("page", page);
        try {
            const response = await fetch(
                root.dataset.ticketsApi + "?" + query,
                {
                    headers: { Accept: "application/json" },
                    signal: controller.signal,
                },
            );
            if (!response.ok) throw new Error("Unable to load tickets.");
            const result = await response.json();
            if (controller.signal.aborted) return;
            const rows = result.data
                .map(
                    (ticket) =>
                        `<tr>${[ticket.ticket_code, ticket.customer_name, ticket.model, ticket.region, fmtRp(ticket.total)].map((value) => `<td>${escapeHtml(value)}</td>`).join("")}</tr>`,
                )
                .join("");
            const choice = await Swal.fire({
                title: "Dashboard tickets",
                width: 900,
                showCloseButton: true,
                html: `<div class="table-responsive"><table class="table table-sm text-left"><thead><tr><th>Ticket</th><th>Customer</th><th>Machine</th><th>Region</th><th>Value</th></tr></thead><tbody>${rows}</tbody></table></div><p>${result.total} tickets · Page ${result.current_page} / ${result.last_page}</p>`,
                showConfirmButton: page < result.last_page,
                confirmButtonText: "Next",
                showDenyButton: page > 1,
                denyButtonText: "Previous",
                showCancelButton: true,
                cancelButtonText: "Close",
            });
            if (controller.signal.aborted) return;
            if (choice.isConfirmed) openTickets(filters, page + 1);
            if (choice.isDenied) openTickets(filters, page - 1);
        } catch (error) {
            if (error.name !== "AbortError")
                Swal.fire({
                    icon: "error",
                    title: "Unable to load tickets",
                    text: error.message,
                });
        }
    }

    root.querySelectorAll("[data-toggle]").forEach((group) => {
        group.addEventListener("click", (event) => {
            const button = event.target.closest("button[data-value]");
            if (!button || !spvData) return;
            group
                .querySelectorAll("button")
                .forEach((b) =>
                    b.setAttribute(
                        "aria-pressed",
                        b === button ? "true" : "false",
                    ),
                );
            if (group.dataset.toggle === "warranty")
                renderWarranty(button.dataset.value);
            else renderHeatmap(button.dataset.value);
        });
    });
    function drillClick(event) {
        if (!spvData) return;
        const cell = event.target.closest("[data-cell]");
        const warranty = event.target.closest("[data-warranty-row]");
        if (cell) {
            const mode = selectedMode("problem") || "machine";
            const problem = spvData.problem[mode];
            const [i, j] = cell.dataset.cell.split(",").map(Number);
            openTickets({
                metric: "problem",
                mode,
                problem: problem.keys[i],
                column: problem.cols[j],
            });
        } else if (warranty) {
            const mode = selectedMode("warranty") || "model";
            openTickets({
                metric: "warranty",
                mode,
                column: spvData.warranty[mode][warranty.dataset.warrantyRow][0],
            });
        }
    }
    root.addEventListener("click", drillClick);
    root.addEventListener("keydown", (event) => {
        if (
            event.key === "Enter" &&
            event.target.matches("[data-warranty-row]")
        )
            drillClick(event);
    });
    root.querySelector("[data-unclassified]").addEventListener(
        "click",
        (event) => {
            event.preventDefault();
            openTickets({ metric: "unclassified" });
        },
    );
    const initial = new URLSearchParams(location.search);
    initDateRange(initial);
    root.querySelectorAll("select[data-filter]").forEach((select) => {
        const value = initial.get(select.dataset.filter);
        if (value && value !== "all") {
            if (select.dataset.filter !== "period" && /^\d+$/.test(value))
                select.add(new Option("", value));
            if ([...select.options].some((option) => option.value === value))
                select.value = value;
        }
    });
    load();
})();
