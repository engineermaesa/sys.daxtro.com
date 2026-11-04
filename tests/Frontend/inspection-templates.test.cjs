const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");

test("inspection version editor loads API content, saves configuration, previews and confirms activation", async () => {
    const handlers = {},
        elements = {},
        calls = [];
    const element = (id) =>
        (elements[id] ??= {
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
    element("document-template-app").dataset.template =
        "service-inspection-report";
    let template = {
        id: 1,
        version: 1,
        status: "active",
        lock_version: 0,
        title: "Persisted inspection",
        diagnosis_section_title: "Analysis",
        diagnosis_fields: {
            problem: { label: "Problem", hint: "Describe problem" },
            cause: { label: "Cause", hint: "Describe cause" },
            action: { label: "Action", hint: "Describe action" },
        },
        product_types: [{ id: 1, name: "Tube Ice" }],
        groups: [
            {
                group_key: "g1",
                title: "Electrical",
                is_active: true,
                items: [
                    {
                        item_key: "i1",
                        name: "Voltage",
                        standard: "220 V",
                        is_active: true,
                        machine_types: [],
                        types_restricted: false,
                        fields: [
                            {
                                field_key: "f1",
                                key: "rs",
                                label: "R-S",
                                type: "number",
                                units: ["V"],
                                options: [],
                                is_active: true,
                                is_multiple: false,
                                min_value: null,
                                max_value: null,
                                required_when: null,
                            },
                        ],
                    },
                ],
            },
        ],
    };
    const response = (data) => ({
        ok: true,
        json: async () => structuredClone(data),
    });
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
                groups: template.groups.map((g) => ({
                    ...g,
                    code: "A",
                    items: g.items.map((item) => ({ ...item, number: 1 })),
                })),
                preview_token: "inspection-token",
            });
        if (url.endsWith("/activate")) {
            template = {
                ...template,
                status: "active",
                lock_version: template.lock_version + 1,
            };
            return response({ data: template });
        }
        if (/\/\d+$/.test(url)) return response({ data: template });
        return response({
            data: {
                versions: [template],
                audit: { total: 0, data: [], last_page: 1 },
            },
        });
    };
    vm.runInNewContext(
        fs.readFileSync("public/assets/js/inspection-templates.js", "utf8"),
        {
            document: {
                getElementById: element,
                querySelector: () => ({ content: "csrf" }),
            },
            window: { addEventListener() {} },
            Swal: { fire: async () => ({ isConfirmed: true }) },
            fetch,
            structuredClone,
            Date,
            console,
        },
    );
    const settle = async () => {
        for (let i = 0; i < 12; i++) await new Promise((r) => setImmediate(r));
    };
    const click = async (action, extra = {}) => {
        handlers["document-template-app:click"]({
            target: {
                closest: () => ({
                    dataset: { action, ...extra },
                    disabled: false,
                }),
            },
        });
        await settle();
    };
    await settle();
    assert.equal(calls.length, 1);
    assert.match(element("dt-title").textContent, /Template versions/);
    await click("create");
    assert.match(element("dt-content").innerHTML, /Voltage/);
    assert.doesNotMatch(element("dt-content").innerHTML, /Frontend demo/);
    handlers["document-template-app:input"]({
        target: {
            dataset: { field: "min_value", item: "0", fieldIndex: "0" },
            value: "200",
        },
    });
    handlers["document-template-app:input"]({
        target: {
            dataset: { field: "diagnosis-hint", diagnosis: "cause" },
            value: "Updated cause instructions",
        },
    });
    await click("preview");
    const saved = calls.find((c) => c.method === "PUT");
    assert.equal(saved.body.groups[0].items[0].fields[0].field_key, "f1");
    assert.equal(saved.body.groups[0].items[0].fields[0].min_value, 200);
    assert.equal(
        saved.body.diagnosis_fields.cause.hint,
        "Updated cause instructions",
    );
    assert.ok(
        calls.indexOf(saved) <
            calls.findIndex((c) => c.url.endsWith("/preview")),
    );
    assert.equal(element("dt-preview").open, true);
    assert.match(
        element("dt-preview-content").innerHTML,
        /Updated cause instructions/,
    );
    await click("confirm-activate");
    const activation = calls.find((c) => c.url.endsWith("/activate"));
    assert.equal(activation.body.preview_token, "inspection-token");
    assert.equal(activation.body.lock_version, 1);
    assert.equal(activation.body.confirmed, true);
    assert.match(element("dt-content").innerHTML, /read-only/);
});
