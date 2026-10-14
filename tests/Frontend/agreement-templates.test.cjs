const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");

test("agreement editor loads real data and saves before preview and confirmed activation", async () => {
    const handlers = {},
        calls = [],
        elements = {};
    function element(id) {
        return (elements[id] ??= {
            innerHTML: "",
            textContent: "",
            style: {},
            dataset: {},
            disabled: false,
            setAttribute() {},
            removeAttribute() {},
            querySelectorAll() {
                return [];
            },
            addEventListener(name, fn) {
                handlers[id + ":" + name] = fn;
            },
            showModal() {
                this.open = true;
            },
            close() {
                this.open = false;
            },
            append() {},
        });
    }
    const root = element("document-template-app");
    root.dataset.template = "service-agreement";
    let template = {
        id: 1,
        version: 1,
        status: "active",
        lock_version: 0,
        title: "Persisted agreement",
        closing_statement: "Persisted closing",
        sections: [
            {
                section_key: "s1",
                title: "Persisted section",
                is_active: true,
                clauses: [
                    { key: "A1", text: "Persisted clause", is_active: true },
                ],
            },
        ],
    };
    const response = (data) => ({
        ok: true,
        json: async () => structuredClone(data),
    });
    const settle = async () => {
        for (let i = 0; i < 12; i++)
            await new Promise((resolve) => setImmediate(resolve));
    };
    const click = async (action, extra = {}) => {
        const el = { dataset: { action, ...extra }, disabled: false };
        handlers["document-template-app:click"]({
            target: { closest: () => el },
        });
        await settle();
    };
    const fetch = async (url, opts) => {
        const body = opts.body ? JSON.parse(opts.body) : undefined;
        calls.push({ url, method: opts.method, body });
        if (url.endsWith("/draft")) {
            template = {
                ...structuredClone(template),
                id: 2,
                version: 2,
                status: "draft",
            };
            return response({ data: template });
        }
        if (opts.method === "PUT") {
            template = {
                ...template,
                ...body,
                lock_version: template.lock_version + 1,
            };
            return response({ data: template });
        }
        if (url.endsWith("/preview"))
            return response({
                data: template,
                sections: template.sections,
                preview_token: "signed-preview-token",
            });
        if (url.endsWith("/activate")) {
            template = {
                ...template,
                status: "active",
                lock_version: template.lock_version + 1,
            };
            return response({ data: template });
        }
        return response({
            data: {
                versions: [template],
                audit: { total: 0, data: [], last_page: 1 },
            },
        });
    };
    vm.runInNewContext(
        fs.readFileSync("public/assets/js/agreement-templates.js", "utf8"),
        {
            document: {
                getElementById: element,
                querySelector: () => ({ content: "csrf-token" }),
            },
            window: { addEventListener() {} },
            Swal: { fire: async () => ({ isConfirmed: true }) },
            fetch,
            structuredClone,
            Date,
            console,
        },
    );
    await settle();
    assert.equal(
        calls.length,
        1,
        "Opening an active agreement must not create a draft",
    );
    assert.match(element("dt-content").innerHTML, /Persisted clause/);
    assert.doesNotMatch(element("dt-content").innerHTML, /Frontend demo/);
    await click("create");
    handlers["document-template-app:input"]({
        target: {
            dataset: { field: "text", item: "0" },
            value: "Edited agreement clause",
        },
    });
    await click("preview");
    const put = calls.find((c) => c.method === "PUT");
    assert.equal(put.body.sections[0].clauses[0].key, "A1");
    assert.equal(
        put.body.sections[0].clauses[0].text,
        "Edited agreement clause",
    );
    const previewIndex = calls.findIndex((c) => c.url.endsWith("/preview"));
    assert.ok(
        calls.indexOf(put) < previewIndex,
        "Unsaved content must be persisted before preview",
    );
    assert.equal(calls[previewIndex].body.lock_version, 1);
    assert.equal(element("dt-preview").open, true);
    assert.match(
        element("dt-preview-content").innerHTML,
        /Edited agreement clause/,
    );
    await click("confirm-activate");
    const activation = calls.find((c) => c.url.endsWith("/activate"));
    assert.equal(activation.body.preview_token, "signed-preview-token");
    assert.equal(activation.body.confirmed, true);
    assert.equal(activation.body.lock_version, 1);
    assert.match(element("dt-content").innerHTML, /read-only/);
});
