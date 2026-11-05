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
