const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");

test("diagnosis editor retains text on rerender and failed upload and exposes Retry", async () => {
    const handlers = {},
        target = { innerHTML: "" },
        window = {};
    class FormDataMock {
        append() {}
    }
    class XhrMock {
        constructor() {
            this.upload = {};
        }
        open() {}
        setRequestHeader() {}
        send() {
            this.onerror();
        }
    }
    vm.runInNewContext(
        fs.readFileSync("public/assets/js/service-diagnosis.js", "utf8"),
        {
            window,
            document: {
                getElementById: () => target,
                querySelector: () => ({ content: "csrf" }),
                addEventListener: (name, fn) => {
                    handlers[name] = fn;
                },
            },
            XMLHttpRequest: XhrMock,
            FormData: FormDataMock,
            Swal: { fire() {} },
            console,
        },
    );
    const data = {
        title: "Pinned diagnosis title",
        legacy: false,
        fields: ["problem", "cause", "action"].map((key) => ({
            key,
            label: key,
            hint: "Template instruction",
            visit_date: "2026-10-15",
            visit_time: "09:30",
            entries: [],
            photos: [],
        })),
    };
    const options = {
        ticketId: 1,
        number: "TEST-1",
        data,
        readOnly: false,
        persist: async () => {},
        reload: async () => {},
    };
    window.ServiceDiagnosisEditor.mount(options);
    assert.match(target.innerHTML, /Pinned diagnosis title/);
    assert.match(target.innerHTML, /Take a picture/);
    assert.match(target.innerHTML, /Upload from gallery/);
    assert.match(target.innerHTML, /capture="environment"/);
    assert.match(target.innerHTML, /data-sd-slot-caption/);
    assert.equal((target.innerHTML.match(/data-sd-action="camera"/g) || []).length, 3, 'One uploader per diagnosis section');
    handlers.input({
        target: {
            dataset: { sdField: "problem", sdProp: "body" },
            value: "Unsaved finding",
        },
    });
    window.ServiceDiagnosisEditor.mount(options);
    assert.equal(
        window.ServiceDiagnosisEditor.collect()[0].body,
        "Unsaved finding",
    );
    handlers.change({
        target: {
            dataset: { sdUpload: "problem" },
            files: [{ name: "proof.jpg", size: 1000 }],
        },
    });
    for (let i = 0; i < 10; i++) await new Promise((r) => setImmediate(r));
    assert.match(target.innerHTML, /Retry upload/);
    assert.equal(
        window.ServiceDiagnosisEditor.collect()[0].body,
        "Unsaved finding",
    );
    const withPhotos = structuredClone(data);
    withPhotos.fields[0].photos = Array.from({length:5}, (_,i) => ({id:i+1,url:'/photo/'+(i+1),caption:'Evidence '+(i+1),locked:false}));
    window.ServiceDiagnosisEditor.mount({...options,data:withPhotos});
    assert.match(target.innerHTML, /class="sd-preview-list"/);
    assert.equal((target.innerHTML.match(/<img /g) || []).length, 5);
    assert.match(target.innerHTML, /data-sd-action="delete"/);
    assert.match(target.innerHTML, /data-sd-action="camera" data-input="sd-camera-problem-0" disabled/);
    assert.match(target.innerHTML, /5\/5 photos/);
    assert.equal((target.innerHTML.match(/data-sd-action="camera"/g) || []).length, 3);
    window.ServiceDiagnosisEditor.mount({ ...options, readOnly: true });
    assert.match(target.innerHTML, /Only the assigned technician/);
    assert.match(target.innerHTML, /Problem name/);
    assert.match(target.innerHTML, /data-sd-action="camera"[^>]*disabled/);
    assert.match(target.innerHTML, /data-sd-action="gallery"[^>]*disabled/);
});
