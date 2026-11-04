const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function client() {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/views/pages/aftersales/tickets/partials/documents-script.blade.php'), 'utf8');
    const start = source.indexOf('function putForm(body) {');
    const end = source.indexOf('\n    function saveItem(', start);
    assert.ok(start >= 0 && end > start);
    const state = { form: { revision: 'original-revision' }, requests: [], renders: 0, docs: 0 };
    const pending = {
        done(callback) { state.onSuccess = callback; return pending; },
        fail(callback) { state.onFailure = callback; return pending; },
    };
    const put = vm.runInNewContext(source.slice(start, end) + '; putForm;', {
        S: state, currentId: () => 7,
        call(method, url, data) { state.requests.push({ method, url, data }); return pending; },
        renderForm() { state.renders++; }, loadDocs() { state.docs++; },
    });
    return { state, put };
}

test('online save echoes its loaded revision and adopts the successful server snapshot', () => {
    const { state, put } = client();
    const draft = { unit: { capacity: '5' }, diagnosis: [{ field: 'problem', body: 'Draft text' }] };
    put(draft);
    assert.equal(state.requests[0].method, 'PUT');
    assert.equal(state.requests[0].url, '/tickets/7/service-form');
    assert.equal(state.requests[0].data.revision, 'original-revision');
    assert.equal(state.requests[0].data.diagnosis[0].body, 'Draft text');
    assert.equal(Object.hasOwn(draft, 'revision'), false);
    state.onSuccess({ data: { revision: 'new-revision' } });
    assert.equal(state.form.revision, 'new-revision');
    assert.equal(state.renders, 1);
});

test('an explicit draft revision is never replaced by a fresher editor revision', () => {
    const { state, put } = client();
    put({ revision: 'offline-base', service_type: 'repair' });
    assert.equal(state.requests[0].data.revision, 'offline-base');
});

test('a failed save preserves the local snapshot and does not retry with another revision', () => {
    const { state, put } = client();
    const original = state.form;
    put({ service_type: 'repair' });
    assert.equal(state.form, original);
    assert.equal(state.form.revision, 'original-revision');
    assert.equal(state.requests.length, 1);
    assert.equal(state.renders, 0);
    assert.equal(state.docs, 0);
});
