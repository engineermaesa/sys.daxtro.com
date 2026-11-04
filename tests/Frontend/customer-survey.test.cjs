const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");
const flush = () => new Promise(setImmediate);

test("public customer page verifies OTP before loading questions and submitting a signed response", async () => {
    const elements = {},
        requests = [],
        fields = {
            namedItem(name) {
                return this[name];
            },
        };
    function element(id = "") {
        return {
            hidden: true,
            children: [],
            handlers: {},
            dataset: { token: "test-token" },
            elements: fields,
            addEventListener(name, fn) {
                this.handlers[name] = fn;
            },
            appendChild(child) {
                this.children.push(child);
                if (child.name) fields[child.name] = child;
            },
            replaceChildren() {
                this.children = [];
            },
            getContext() {
                return {
                    beginPath() {},
                    moveTo() {},
                    lineTo() {},
                    stroke() {},
                    clearRect() {},
                };
            },
            setPointerCapture() {},
            getBoundingClientRect() {
                return { left: 0, top: 0, width: 450, height: 160 };
            },
            width: 900,
            height: 320,
            toDataURL: () => "data:image/png;base64,signature",
        };
    }
    const get = (id) => (elements[id] ||= element(id));
    fields.code = { value: "123456" };
    fields.signer_name = { value: "Customer" };
    fields.signer_position = { value: "Owner" };
    fields.machine_issue_note = { value: "" };
    const payloads = [
        {
            state: "active",
            ticket_code: "DAX-1",
            customer: "Customer",
            phone_masked: "0812****1234",
        },
        {},
        { session_token: "verified" },
        {
            job_information: { ticket_code: "DAX-1" },
            template: {
                scale_labels: ["Poor", "Low", "Fair", "Good", "Great"],
                sections: [
                    {
                        title: "Service",
                        questions: [
                            {
                                question_key: "quality",
                                label: "Quality",
                                type: "rating",
                                required: true,
                            },
                            {
                                question_key: "machine_ok",
                                label: "Working?",
                                type: "yes_no",
                                required: true,
                            },
                        ],
                    },
                ],
            },
        },
        {},
    ];
    const context = {
        document: { getElementById: get, createElement: () => element() },
        fetch: async (url, options) => {
            requests.push({ url, options });
            const data = payloads.shift();
            return { ok: true, json: async () => ({ data }) };
        },
    };
    vm.createContext(context);
    vm.runInContext(
        fs.readFileSync("public/assets/js/customer-survey.js", "utf8"),
        context,
    );
    await flush();
    assert.equal(get("verification").hidden, false);
    assert.equal(get("surveyForm").hidden, true);
    assert.equal(requests.length, 1);
    await get("sendCode").handlers.click({ target: get("sendCode") });
    assert.equal(get("verifyCode").hidden, false);
    get("verifyCode").handlers.submit({
        preventDefault() {},
        submitter: {},
        target: get("verifyCode"),
    });
    await flush();
    assert.equal(requests[2].url, "/api/survey/test-token/otp/verify");
    assert.equal(requests[3].options.headers["X-Survey-Session"], "verified");
    assert.equal(get("surveyForm").hidden, false);
    assert.equal(fields["answer:quality"].children[1].textContent, "1 — Poor");
    assert.equal(fields["answer:quality"].children[5].textContent, "5 — Great");
    fields["answer:quality"].value = "5";
    fields["answer:machine_ok"].value = "0";
    get("surveyForm").handlers.change();
    assert.equal(fields.machine_issue_note.required, true);
    fields["answer:machine_ok"].value = "1";
    get("surveyForm").handlers.change();
    assert.equal(fields.machine_issue_note.required, false);
    const submit = {
        preventDefault() {},
        submitter: {},
        target: get("surveyForm"),
    };
    get("surveyForm").handlers.submit(submit);
    assert.match(get("message").textContent, /signature/);
    assert.equal(requests.length, 4);
    get("signature").handlers.pointerdown({
        preventDefault() {},
        clientX: 1,
        clientY: 1,
        pointerId: 1,
    });
    get("signature").handlers.pointermove({ clientX: 2, clientY: 2 });
    get("signature").handlers.pointerup();
    get("surveyForm").handlers.submit(submit);
    await flush();
    assert.equal(requests[4].url, "/api/survey/test-token/submit");
    const body = JSON.parse(requests[4].options.body);
    assert.equal(body.answers.quality, 5);
    assert.equal(body.answers.machine_ok, 1);
    assert.match(body.signature, /^data:image\/png/);
    assert.equal(get("surveyForm").hidden, true);
    assert.match(get("message").textContent, /submitted/);
});
