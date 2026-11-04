const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");

const source = fs.readFileSync(
    "resources/views/pages/aftersales/tickets/partials/documents-script.blade.php",
    "utf8",
);
const cameraCode = source.slice(
    source.indexOf("    function prefersCamera()"),
    source.indexOf("    function hasPhoto("),
);

function setup(userAgent = "Android", maxTouchPoints = 1) {
    const inputs = [],
        requests = [],
        modals = [],
        revoked = [];
    let complete;
    const context = {
        navigator: { userAgent, maxTouchPoints },
        document: {
            body: { appendChild() {} },
            createElement() {
                const input = {
                    files: [{ name: "camera.jpg" }],
                    handlers: {},
                    style: {},
                    setAttribute(key, value) {
                        this[key] = value;
                    },
                    addEventListener(event, handler) {
                        this.handlers[event] = handler;
                    },
                    remove() {
                        this.removed = true;
                    },
                    click() {
                        this.clicked = true;
                    },
                };
                inputs.push(input);
                return input;
            },
        },
        URL: {
            createObjectURL: () => "blob:preview",
            revokeObjectURL: (url) => revoked.push(url),
        },
        FormData: class {
            append(...args) {
                this.photo = args;
            }
        },
        Swal: {
            fire(options) {
                modals.push(options);
                return new Promise((resolve) => {
                    complete = resolve;
                });
            },
            isLoading: () => false,
            showValidationMessage(message) {
                context.validation = message;
            },
        },
        $: {
            ajax: async (options) => {
                requests.push(options);
                if (context.failUpload) throw {};
            },
        },
        currentId: () => 28,
        hasPhoto: () => false,
        esc: (value) => value,
        saveItem: async (item) => requests.push({ save: item }),
        call: async (method, url, body) => {
            requests.push({ method, url, body });
            return { data: { token: "existing-token" } };
        },
        errMsg: (_, fallback) => fallback,
        loadForm: () => {
            context.reloaded = true;
        },
        toast: () => {},
    };
    vm.createContext(context);
    vm.runInContext(cameraCode, context);
    context.cameraPhoto = async () => "compressed-photo";
    return {
        context,
        inputs,
        requests,
        modals,
        revoked,
        complete: (result) => complete(result),
    };
}

test("phones and tablets use the camera while desktop and touch laptops keep QR", () => {
    for (const [agent, touches, expected] of [
        ["Android", 5, true],
        ["iPhone", 5, true],
        ["iPad", 5, true],
        ["Macintosh", 5, true],
        ["Macintosh", 0, false],
        ["Windows NT", 10, false],
    ]) {
        assert.equal(setup(agent, touches).context.prefersCamera(), expected);
    }
});

test("camera opens before network requests; confirming saves then uploads to the existing token endpoint", async () => {
    const state = setup();
    state.context.openCamera(1, "rs");
    assert.equal(state.inputs[0].clicked, true);
    assert.equal(state.inputs[0].capture, "environment");
    assert.equal(state.requests.length, 0);
    const change = state.inputs[0].handlers.change();
    await new Promise(setImmediate);
    assert.match(state.modals[0].html, /blob:preview/);
    assert.equal(state.requests.length, 0);
    assert.equal(await state.modals[0].preConfirm(), true);
    assert.equal(state.requests[0].save, 1);
    assert.equal(state.requests[1].body.field_key, "rs");
    assert.equal(state.requests[2].url, "/api/inspection-photo/existing-token");
    assert.equal(state.requests[2].data.photo[1], "compressed-photo");
    state.complete({ isConfirmed: true });
    await change;
    assert.equal(state.context.reloaded, true);
    assert.deepEqual(state.revoked, ["blob:preview"]);
});

test("cancelling the camera or preview never uploads", async () => {
    const state = setup();
    state.context.openCamera(1, "rs");
    state.inputs[0].handlers.cancel();
    assert.equal(state.inputs[0].removed, true);
    state.context.openCamera(1, "rs");
    const change = state.inputs[1].handlers.change();
    await new Promise(setImmediate);
    state.complete({ isDismissed: true });
    await change;
    assert.equal(state.requests.length, 0);
    assert.deepEqual(state.revoked, ["blob:preview"]);
});

test("failed upload keeps preview available for retry; retake opens the camera again", async () => {
    const state = setup();
    state.context.openCamera(1, "rs");
    const change = state.inputs[0].handlers.change();
    await new Promise(setImmediate);
    state.context.failUpload = true;
    assert.equal(await state.modals[0].preConfirm(), false);
    assert.match(state.context.validation, /try again/);
    assert.equal(state.revoked.length, 0);
    state.context.failUpload = false;
    assert.equal(await state.modals[0].preConfirm(), true);
    state.complete({ isDenied: true });
    await change;
    assert.equal(state.inputs[1].clicked, true);
});
