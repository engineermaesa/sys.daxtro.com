const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/pages/aftersales/customers/index.blade.php', 'utf8');
const code = source.slice(source.indexOf('    async function deleteCustomer'), source.indexOf("    $(document).on('click', '.btn-delete-customer'"));

function setup(confirmed) {
    const prompts = [], requests = [];
    let reloads = 0;
    const context = {
        Swal: { fire(options) { prompts.push(options); return Promise.resolve({ isConfirmed: confirmed }); } },
        $: { ajax(options) { requests.push(options); } },
        loadCustomers() { reloads++; },
    };
    vm.createContext(context);
    vm.runInContext(code, context);
    return { context, prompts, requests, reloads: () => reloads };
}

test('canceling customer deletion sends no DELETE request', async () => {
    const form = setup(false);
    await form.context.deleteCustomer(7, 'Customer A');
    assert.equal(form.requests.length, 0);
    assert.equal(form.reloads(), 0);
});

test('confirmed customer deletion calls the soft-delete endpoint and reloads only on success', async () => {
    const form = setup(true);
    await form.context.deleteCustomer(7, 'Customer A');
    assert.match(form.prompts[0].text, /machine and ticket records will be retained/);
    assert.equal(form.requests[0].method, 'DELETE');
    assert.equal(form.requests[0].url, '/api/aftersales/customers/7');
    assert.equal(form.reloads(), 0);
    form.requests[0].success();
    assert.equal(form.reloads(), 1);
    assert.equal(form.prompts.at(-1).title, 'Customer deleted');
});

test('failed deletion preserves the customer list and displays the server message', async () => {
    const form = setup(true);
    await form.context.deleteCustomer(7, 'Customer A');
    form.requests[0].error({ responseJSON: { message: 'Customer changed. Reload before deleting.' } });
    assert.equal(form.reloads(), 0);
    assert.equal(form.prompts.at(-1).text, 'Customer changed. Reload before deleting.');
});

test('import review explains that machines stay archived, including after a match decision', () => {
    const source = fs.readFileSync('resources/views/pages/aftersales/customers/partials/import-modal.blade.php', 'utf8');
    const context = { esc: value => String(value) };
    vm.createContext(context);
    vm.runInContext(source.slice(source.indexOf('    function decisionLabel'), source.indexOf('    function candidateLabel')), context);
    const row = { review: { keep_machine_archived: true, errors: [], warnings: ['Other warning 1', 'Other warning 2'] } };
    assert.match(context.detailText(row), /machine stays archived and will not be copied or moved/);
    row.decision = { action: 'create_new' };
    assert.match(context.detailText(row), /Create new; existing machine stays archived/);
    row.decision = { action: 'skip' };
    assert.equal(context.detailText(row), 'Skip row');
});

test('import result distinguishes saved customers from machine rows kept in the archive', () => {
    const source = fs.readFileSync('resources/views/pages/aftersales/customers/partials/import-modal.blade.php', 'utf8');
    let message;
    const banner = { text(value) { message = value; return this; }, removeClass() { return this; } };
    const context = {
        $: () => banner, isApplied: () => true,
        preview: { import: { created_count: 1, matched_count: 0, error_count: 0 }, rows: [{ result: { machine_status: 'kept_archived' } }] },
    };
    vm.createContext(context);
    vm.runInContext(source.slice(source.indexOf('    function renderBanner'), source.indexOf('    function renderPreview')), context);
    context.renderBanner();
    assert.match(message, /1 new customer\(s\)/);
    assert.match(message, /1 machine row\(s\) kept in archive without copying or moving/);
});
