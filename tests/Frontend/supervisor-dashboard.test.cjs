const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");

const source = fs.readFileSync(
    "public/js/aftersales/supervisor-dashboard.js",
    "utf8",
);
const loadCode = source.slice(
    source.indexOf("    async function load()"),
    source.indexOf("    async function openTickets("),
);

function setup() {
    const elements = new Map();
    const element = (id) => {
        if (!elements.has(id))
            elements.set(id, {
                textContent: "",
                hidden: false,
                style: {},
                replaceChildren() {
                    this.cleared = true;
                },
            });
        return elements.get(id);
    };
    const requests = [],
        rendered = [],
        urls = [],
        attributes = {};
    let selected = new URLSearchParams("period=this_month");
    const context = {
        AbortController,
        URLSearchParams,
        document: { getElementById: element },
        root: {
            dataset: { api: "/api/dashboard/aftersales/supervisor" },
            querySelectorAll: () => [],
            setAttribute: (key, value) => (attributes[key] = value),
        },
        status: element("status"),
        charts: {},
        location: { pathname: "/", hash: "" },
        history: { replaceState: (_, __, url) => urls.push(url) },
        params: () => selected,
        populateOptions() {},
        render() {
            rendered.push(context.spvData);
        },
        clearData() {
            context.spvData = null;
            element("spv-warranty-list").replaceChildren();
        },
        window: { Swal: true },
        Swal: {
            fire(options) {
                element("error").textContent = options.text;
                return Promise.resolve({ isConfirmed: false });
            },
        },
        fetch(url, options) {
            return new Promise((resolve, reject) =>
                requests.push({ url, options, resolve, reject }),
            );
        },
    };
    vm.createContext(context);
    vm.runInContext(
        "var spvData, aborter, generation = 0;\n" + loadCode,
        context,
    );
    return {
        context,
        requests,
        rendered,
        urls,
        attributes,
        element,
        select: (value) => (selected = new URLSearchParams(value)),
    };
}

const payload = (value) => ({
    data: { marker: value, split: { values: [value] } },
});
const respond = (request, value) =>
    request.resolve({ ok: true, json: async () => payload(value) });

test("filter values reach the backend and successful data replaces the current dashboard", async () => {
    const f = setup();
    f.select("period=last_month&region=12&category=7");
    const loading = f.context.load();
    assert.equal(
        f.requests[0].url,
        "/api/dashboard/aftersales/supervisor?period=last_month&region=12&category=7",
    );
    assert.equal(f.attributes["aria-busy"], "true");
    respond(f.requests[0], 100);
    await loading;
    assert.equal(f.rendered[0].marker, 100);
    assert.equal(f.attributes["aria-busy"], "false");
    assert.match(f.urls[0], /region=12/);
});

test("a late response cannot overwrite a newer filter selection even if transport ignores abort", async () => {
    const f = setup();
    const old = f.context.load();
    f.select("period=last_month");
    const latest = f.context.load();
    assert.equal(f.requests[0].options.signal.aborted, true);
    respond(f.requests[1], 200);
    await latest;
    respond(f.requests[0], 100);
    await old;
    assert.deepEqual(
        f.rendered.map((d) => d.marker),
        [200],
    );
    assert.equal(f.context.spvData.marker, 200);
});

test("failed loading clears stale values and uses the existing application alert without adding dashboard rows", async () => {
    const f = setup();
    f.context.spvData = payload(900).data;
    const loading = f.context.load();
    f.requests[0].resolve({
        ok: false,
        json: async () => ({ message: "Invalid period." }),
    });
    await loading;
    assert.equal(f.context.spvData, null);
    assert.equal(f.element("spv-warranty-list").cleared, true);
    assert.match(f.element("error").textContent, /Unable to load dashboard/);
});

function setupDateRange(query = "") {
    const nodes = new Map(), loads = [];
    const element = (selector) => {
        if (!nodes.has(selector)) nodes.set(selector, {
            value: "", dataset: {}, events: {}, attributes: {}, hidden: true,
            classList: { add() {}, remove() {} },
            addEventListener(name, fn) { this.events[name] = fn; },
            setAttribute(name, value) { this.attributes[name] = value; },
            getAttribute(name) { return this.attributes[name]; },
            focus() { this.focused = true; },
        });
        return nodes.get(selector);
    };
    const controls = ["period", "start_date", "end_date", "region", "category"].map(key => {
        const node = element(`[data-filter="${key}"]`);
        node.dataset.filter = key;
        node.value = ({ period: "this_month", region: "3", category: "9" })[key] || "";
        return node;
    });
    const host = { querySelector: element, addEventListener() {}, contains: () => false };
    let picker, options;
    const format = date => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, "0"), String(date.getDate()).padStart(2, "0")].join("-");
    const context = {
        URLSearchParams, Date,
        root: { querySelector: () => host, querySelectorAll: () => controls },
        document: { addEventListener() {} },
        flatpickr(_input, opts) {
            options = opts;
            picker = {
                selectedDates: [], formatDate: format,
                setDate(dates) { this.selectedDates = dates.map(value => new Date(value + "T00:00:00")); },
            };
            picker.setDate(opts.defaultDate);
            return picker;
        },
        load() { loads.push(context.params().toString()); },
    };
    vm.createContext(context);
    vm.runInContext(source.slice(source.indexOf("    function initDateRange("), source.indexOf("    function populateOptions()")), context);
    context.initDateRange(new URLSearchParams(query));
    return { context, element, loads, picker, options };
}

test("date range restores custom URL values and Apply sends both dates with the other dashboard filters", () => {
    const f = setupDateRange("period=custom&start_date=2026-10-01&end_date=2026-10-31");
    assert.equal(f.options.mode, "range");
    assert.equal(f.options.inline, true);
    assert.equal(f.element("[data-date-label]").textContent, "2026-10-01 → 2026-10-31");
    f.picker.setDate(["2026-11-02", "2026-11-09"]);
    f.options.onChange(f.picker.selectedDates);
    f.element("[data-date-apply]").events.click();
    const applied = new URLSearchParams(f.loads[0]);
    assert.equal(applied.get("period"), "custom");
    assert.equal(applied.get("start_date"), "2026-11-02");
    assert.equal(applied.get("end_date"), "2026-11-09");
    assert.equal(applied.get("region"), "3");
    assert.equal(applied.get("category"), "9");
    assert.equal(f.element("[data-date-toggle]").attributes["aria-expanded"], "false");
});

test("Cancel restores the applied dates and incomplete selections cannot reload the dashboard", () => {
    const f = setupDateRange("period=custom&start_date=2026-10-01&end_date=2026-10-31");
    f.picker.setDate(["2026-11-05"]);
    f.options.onChange(f.picker.selectedDates);
    assert.equal(f.element("[data-date-apply]").disabled, true);
    f.element("[data-date-apply]").events.click();
    assert.equal(f.loads.length, 0);
    f.element("[data-date-cancel]").events.click();
    assert.deepEqual(Array.from(f.picker.selectedDates, date => f.picker.formatDate(date)), ["2026-10-01", "2026-10-31"]);
    assert.equal(f.element("[data-date-label]").textContent, "2026-10-01 → 2026-10-31");
    assert.equal(f.loads.length, 0);
});

test("invalid custom URL dates fall back to the current month without submitting malformed parameters", () => {
    const f = setupDateRange("period=custom&start_date=2026-02-31&end_date=2026-03-10");
    assert.equal(f.context.params().get("period"), "this_month");
    assert.equal(f.context.params().has("start_date"), false);
    assert.equal(f.context.params().has("end_date"), false);
});
