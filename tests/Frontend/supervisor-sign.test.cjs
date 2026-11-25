const test = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const vm = require("node:vm");

const source = fs.readFileSync(
    path.join(
        __dirname,
        "../../resources/views/pages/aftersales/tickets/partials/documents-script.blade.php",
    ),
    "utf8",
);

function signResponse(status) {
    let handler;
    const calls = [], loads = [];
    const button = { prop() { return this; } };
    const document = { getElementById: () => ({ _pad: { ink: true, dataUrl: () => 'data:image/png;base64,png' } }) };
    const $ = (selector) => selector === document ? { on(event, target, fn) { handler = fn; } } : button;
    const context = {
        $, document, S: { ssDoc: 'service_form' }, currentId: () => 10,
        call(method, url, body) {
            calls.push({ method, url, body });
            return { done(fn) { fn({ data: { status } }); return this; }, fail() { return this; } };
        },
        saveDocument(type, done) {
            calls.push({ method: 'POST', url: '/save', type });
            done();
            return { fail() { return this; }, always(fn) { fn(); return this; } };
        },
        toast() {}, fail() { assert.fail('Signing should succeed'); },
        openSupervisorSign: () => loads.push('review'), loadDocs: () => loads.push('documents'),
        loadTicketDetail: () => loads.push('ticket'),
    };
    const start = source.indexOf("    $(document).on('click', '#ssSign', function () {");
    const end = source.indexOf('\n    });', start) + 8;
    vm.runInNewContext(source.slice(start, end), context);
    handler.call({});
    return { calls, loads };
}

test('TC-007: v3 signing refreshes publication without a forbidden legacy save request', () => {
    for (const status of ['awaiting_spv_signature', 'documentation_published']) {
        const { calls, loads } = signResponse(status);
        assert.equal(calls.length, 1);
        assert.equal(calls[0].url, '/tickets/10/documents/service_form/signatures');
        assert.equal(calls[0].body.role, 'supervisor');
        assert.deepEqual(loads, ['review', 'documents', 'ticket']);
    }
});

test('TC-007: legacy signing still finalizes through the existing save endpoint', () => {
    assert.equal(signResponse('draft').calls[1].url, '/save');
});

test('TC-007: inline document signature card permits SPV but keeps Technician fields locked', () => {
    let html;
    const start = source.indexOf('    function renderSignatureCards(');
    const end = source.indexOf('\n    }', start) + 6;
    const doc = { locked: true, can: { sign_supervisor: true, sign_technician: true, sign_customer: true } };
    const context = {
        ROLES: [['technician', 'Technician'], ['supervisor', 'Supervisor'], ['customer', 'Customer']],
        SIGN_CAP: { service_form: 'can_sign_service_form' }, capAllows: () => true,
        esc: String, dash: String, fmtDateTime: String,
        $: () => ({ html(value) { html = value; return this; }, find() { return { each() {} }; } }),
    };
    const render = vm.runInNewContext(source.slice(start, end) + '; renderSignatureCards;', context);
    render('#signatures', 'service_form', doc);
    assert.equal((html.match(/class="sig-sign /g) || []).length, 1);
    assert.match(html, /data-role="supervisor"/);
});

function setup({
    locked = true,
    allowed = true,
    reviewAllowed,
    fill = false,
    capability = true,
    signed = false,
} = {}) {
    const elements = {};
    function $(selector) {
        return (elements[selector] ||= {
            classes: {},
            props: {},
            value: "",
            content: "",
            toggleClass(name, value) {
                this.classes[name] = value;
                return this;
            },
            addClass(name) {
                this.classes[name] = true;
                return this;
            },
            text(value) {
                this.value = value;
                return this;
            },
            html(value) {
                this.content = value;
                return this;
            },
            css() {
                return this;
            },
            attr() {
                return this;
            },
            data() {
                return this;
            },
            prop(name, value) {
                this.props[name] = value;
                return this;
            },
        });
    }
    const doc = {
        status: signed ? "documentation_published" : "awaiting_spv_signature",
        locked,
        can: {
            fill,
            sign_supervisor: allowed,
            review_supervisor: reviewAllowed,
            save: false,
            unlock: false,
        },
        signatures: signed ? { supervisor: { url: "/signature.png" } } : {},
        completeness: {
            complete: signed,
            summary: "Supervisor signature is missing",
        },
    };
    const state = {
        docs: { documents: { service_form: doc } },
        ssDoc: "service_form",
        ssData: doc,
    };
    let pads = 0;
    const pad = {
        ink: false,
        clear() {
            this.ink = false;
            this.onChange();
        },
    };
    const context = vm.createContext({
        S: state,
        $,
        esc: String,
        dash: String,
        fmtDateTime: String,
        TYPE_TITLE: { service_form: "Service Form" },
        ROLES: [],
        COPY_LABEL: {},
        SIGN_CAP: {
            service_form: "can_sign_service_form",
            agreement: "can_sign_agreement",
        },
        capAllows: () => capability,
        document: { getElementById: () => ({}) },
        makePad() {
            pads++;
            return pad;
        },
    });
    const card = source.slice(
        source.indexOf("    function renderDocs() {"),
        source.indexOf(
            "\n    }",
            source.indexOf("    function renderDocs() {"),
        ) + 6,
    );
    const review = source.slice(
        source.indexOf("    function renderSupervisorSign() {"),
        source.indexOf(
            "\n    }",
            source.indexOf("    function renderSupervisorSign() {"),
        ) + 6,
    );
    vm.runInContext(
        card + "\n" + review + "\nrenderDocs(); renderSupervisorSign();",
        context,
    );
    return { elements, pad, pads };
}

test("TC-007: pending locked form exposes Review & Sign and an interactive SPV pad", () => {
    const { elements, pad, pads } = setup();
    const html = elements["#tdDocsDynamic"].content;
    assert.match(html, /doc-sign-supervisor/);
    assert.match(html, /Awaiting SPV signature/);
    assert.doesNotMatch(html, />Fill<|doc-save|doc-unlock/);
    assert.equal(elements["#ssSignArea"].classes.hidden, false);
    assert.equal(elements["#ssSign"].classes.hidden, false);
    assert.equal(elements["#ssSign"].props.disabled, true);
    assert.equal(elements["#ssStatusBadge"].value, "Awaiting SPV signature");
    assert.equal(pads, 1);
    pad.ink = true;
    pad.onChange();
    assert.equal(elements["#ssSign"].props.disabled, false);
});

test("TC-007: backend denial or ticket capability denial keeps signature controls hidden", () => {
    for (const options of [{ allowed: false }, { capability: false }]) {
        const { elements, pads } = setup(options);
        assert.doesNotMatch(
            elements["#tdDocsDynamic"].content,
            /doc-sign-supervisor/,
        );
        assert.equal(elements["#ssSignArea"].classes.hidden, true);
        assert.equal(elements["#ssSign"].classes.hidden, true);
        assert.equal(pads, 0);
    }
});

test("TC-007: published document shows its saved state and cannot be signed again in the UI", () => {
    const { elements, pads } = setup({ allowed: false, signed: true });
    assert.match(elements["#tdDocsDynamic"].content, /Saved/);
    assert.doesNotMatch(
        elements["#tdDocsDynamic"].content,
        /doc-sign-supervisor/,
    );
    assert.equal(elements["#ssSignedArea"].classes.hidden, false);
    assert.equal(pads, 0);
});

test('TC-007 adjustment: assigned SPV can open review before completion or submission, but cannot sign', () => {
    const { elements, pads } = setup({ locked: false, allowed: false, capability: false, reviewAllowed: true });
    assert.match(elements['#tdDocsDynamic'].content, /doc-sign-supervisor/);
    assert.equal(elements['#ssSignArea'].classes.hidden, true);
    assert.equal(elements['#ssSign'].classes.hidden, false);
    assert.equal(elements['#ssSign'].props.disabled, true);
    assert.match(elements['#ssCompleteness'].value, /Complete the required fields/);
    assert.equal(pads, 0);
});

test('TC-007 adjustment: assigned SPV can review a published document without signing again', () => {
    const { elements, pads } = setup({ allowed: false, reviewAllowed: true, signed: true });
    assert.match(elements['#tdDocsDynamic'].content, /doc-sign-supervisor/);
    assert.equal(elements['#ssSign'].classes.hidden, true);
    assert.equal(elements['#ssSignedArea'].classes.hidden, false);
    assert.equal(pads, 0);
});

test('SPV can open shared document entry and sign a complete draft without waiting for submit', () => {
    const { elements, pad } = setup({ locked: false, allowed: true, reviewAllowed: true, fill: true });
    assert.equal(elements['#ssFillDocument'].classes.hidden, false);
    assert.equal(elements['#ssSignArea'].classes.hidden, false);
    assert.match(elements['#ssCompleteness'].value, /without waiting for Technician submission/);
    pad.ink = true;
    pad.onChange();
    assert.equal(elements['#ssSign'].props.disabled, false);
});
