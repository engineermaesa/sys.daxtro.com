const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function setup(source, start, end, products) {
    const fields = {};
    const $ = selector => {
        fields[selector] ||= { value: '', html: '', attributes: {} };
        const field = fields[selector];
        return {
            val(value) { if (value === undefined) return field.value; field.value = value; return this; },
            html(value) { field.html = value; return this; },
            prop(key, value) { field.attributes[key] = value; return this; },
            attr(key, value) { field.attributes[key] = value; return this; },
            trigger() { return this; },
        };
    };
    $.ajax = request => {
        request.success({ data: { products } });
        return { always(callback) { callback(); } };
    };
    const context = {
        $, customerId: 1, apiBaseUrl: '/api/aftersales/customers',
        machineCreateBaseUrl: '/aftersales/customers/1/machines/create',
        machinesById: new Map(),
        escapeHtml: value => String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'),
    };
    vm.createContext(context);
    vm.runInContext(source.slice(source.indexOf(start), source.indexOf(end)), context);
    return { context, fields };
}

const step = fs.readFileSync('resources/views/pages/aftersales/tickets/partials/step-1-customer-machine.blade.php', 'utf8');
const list = fs.readFileSync('resources/views/pages/aftersales/customers/machines.blade.php', 'utf8');

test('new standalone machines appear by their saved name and unit ID in the ticket dropdown', () => {
    const { context, fields } = setup(step, '    let customersCache', "    document.addEventListener('DOMContentLoaded'", [
        { id: 21, machine_name: 'Custom Tube Machine', product_id: null, product: null, serial_number: 'SN-21' },
        { id: 22, machine_name: 'Imported Machine', product_id: null, serial_number: null },
        { id: 23, machine_name: 'Edited Name', product: { name: 'Product Name' }, serial_number: 'SN-23' },
    ]);
    let loaded = false;
    context.loadMachineOptions(1, () => { loaded = true; });
    const machine = fields['#field-machine'];
    assert.equal(machine.attributes.disabled, false);
    assert.match(machine.html, /value="21">Custom Tube Machine — SN-21<\/option>/);
    assert.match(machine.html, /value="22">Imported Machine<\/option>/);
    assert.match(machine.html, /value="23">Edited Name — SN-23<\/option>/);
    assert.equal(loaded, true);
});

test('legacy machines use the product name; unnamed units remain identifiable and labels are escaped', () => {
    const { context, fields } = setup(step, '    let customersCache', "    document.addEventListener('DOMContentLoaded'", [
        { id: 1, product: { name: 'Legacy Product' } },
        { id: 2, machine_name: '<img src=x>', product: null },
        { id: 3, product: null },
    ]);
    context.loadMachineOptions(1);
    assert.match(fields['#field-machine'].html, /Legacy Product/);
    assert.match(fields['#field-machine'].html, /&lt;img src=x&gt;/);
    assert.match(fields['#field-machine'].html, /Machine #3/);
    assert.doesNotMatch(fields['#field-machine'].html, /<img/);
});

test('an empty customer machine list stays disabled with an explanatory message', () => {
    const { context, fields } = setup(step, '    let customersCache', "    document.addEventListener('DOMContentLoaded'", []);
    context.loadMachineOptions(1);
    assert.equal(fields['#field-machine'].attributes.disabled, true);
    assert.match(fields['#field-machine'].html, /No machines registered yet/);
});

test('Machine List provides Detail and displays the saved multiline Note safely', () => {
    const note = 'Previously owned by A\nCheck valve <script>alert(1)</script> & pump';
    const { context, fields } = setup(list, '    function escapeHtml', '    $(function ()', [
        { id: 21, machine_name: 'Custom Machine', product: null, note, capacity: 0, serial_number: 'SN-21' },
    ]);
    context.loadMachines();
    assert.match(fields['#machine-table-body'].html, /btn-detail-machine/);
    const machine = context.machinesById.get('21');
    assert.equal(machine.note, note);
    context.renderMachineDetails(machine);
    const details = fields['#machine-detail-fields'].html;
    assert.match(details, /Custom Machine/);
    assert.match(details, /Previously owned by A\nCheck valve &lt;script&gt;alert\(1\)&lt;\/script&gt; &amp; pump/);
    assert.doesNotMatch(details, /<script>/);
    assert.match(details, />0<\/dd>/);
    assert.equal(fields['#machine-detail-edit'].attributes.href, '/aftersales/customers/1/machines/create?product=21');
});
