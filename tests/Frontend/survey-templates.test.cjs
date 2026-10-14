const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");

test("survey template editor protects the core question and saves before preview and activation", async () => {
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
            querySelector() {
                return element("dialog-title");
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
    element("document-template-app").dataset.template = "satisfaction-survey";
    let template = {
        id: 1,
        version: 1,
        status: "active",
        lock_version: 0,
        title: "Persisted survey",
        content: {
            scale_labels: ["Very Poor", "Poor", "Fair", "Good", "Very Good"],
            sections: [
                {
                    section_key: "s1",
                    title: "Ratings",
                    is_active: true,
                    questions: [
                        {
                            question_key: "timeliness",
                            label: "Arrival",
                            hint: "",
                            type: "rating",
                            options: [],
                            is_active: true,
                            is_core: false,
                            required: true,
                        },
                    ],
                },
                {
                    section_key: "s2",
                    title: "Machine condition",
                    is_active: true,
                    questions: [
                        {
                            question_key: "machine_ok",
                            label: "Machine working?",
                            hint: "",
                            type: "yes_no",
                            options: [],
                            is_active: true,
                            is_core: true,
                            required: true,
                        },
                    ],
                },
            ],
        },
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
                sections: template.content.sections,
                preview_token: "survey-token",
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
        fs.readFileSync("public/assets/js/survey-templates.js", "utf8"),
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
    assert.ok(calls.every((c) => c.method === "GET"));
    assert.match(element("dt-content").innerHTML, /Arrival/);
    await click("create");
    await click("toggle-question", { section: "1", question: "0" });
    handlers["document-template-app:input"]({
        target: {
            dataset: { field: "label", section: "0", question: "0" },
            value: "Updated arrival question",
        },
    });
    handlers["document-template-app:input"]({
        target: { dataset: { scale: "0" }, value: "Needs improvement" },
    });
    await click("preview");
    const saved = calls.find((c) => c.method === "PUT");
    assert.equal(saved.body.content.sections[1].questions[0].is_active, true);
    assert.equal(saved.body.content.sections[1].questions[0].required, true);
    assert.equal(
        saved.body.content.sections[0].questions[0].question_key,
        "timeliness",
    );
    assert.equal(saved.body.content.scale_labels[0], "Needs improvement");
    assert.ok(
        calls.indexOf(saved) <
            calls.findIndex((c) => c.url.endsWith("/preview")),
    );
    assert.equal(element("dt-preview").open, true);
    assert.match(
        element("dt-preview-content").innerHTML,
        /Updated arrival question/,
    );
    await click("confirm-activate");
    const activation = calls.find((c) => c.url.endsWith("/activate"));
    assert.equal(activation.body.preview_token, "survey-token");
    assert.equal(activation.body.confirmed, true);
    assert.equal(activation.body.lock_version, 1);
    assert.match(element("dt-content").innerHTML, /read-only/);
});
