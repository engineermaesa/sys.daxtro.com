const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/pages/aftersales/customers/machines.blade.php', 'utf8');

function setup() {
    let submit, request, focused = false, scrolled = false;
    const alert = {
        hidden: true,
        focus() { focused = true; },
        scrollIntoView() { scrolled = true; },
    };
    const message = { textContent: '' };
    const button = { disabled: false };
    const $ = selector => ({
        on(event, handler) { submit = handler; },
        val: () => selector === '#move-machine-target' ? '2' : 'Transfer note',
        find: () => ({ text: () => 'Destination' }),
        prop(key, value) { button[key] = value; return this; },
        modal() { throw new Error('A rejected transfer must keep the modal open'); },
    });
    $.ajax = options => { request = options; };
    const context = {
        $, apiBaseUrl: '/api/aftersales/customers', moveMachineId: 21,
        document: { getElementById: id => id === 'move-machine-error' ? alert : message },
        Swal: { fire() { throw new Error('Unexpected success notification'); } },
        loadMachines() { throw new Error('Do not remove/reload the machine on rejection'); },
    };
    vm.createContext(context);
    vm.runInContext(source.slice(source.indexOf('    function setMachineMoveError'), source.indexOf('    function renderHeaderExtras')), context);
    vm.runInContext(source.slice(source.indexOf("        $('#move-machine-submit').on"), source.indexOf("        $(document).on('click', '.btn-delete-machine'")), context);
    return { submit: () => submit(), request: () => request, alert, message, button, focused: () => focused, scrolled: () => scrolled };
}

test('TC-IM-033: an open-ticket rejection shows the server warning, focuses it, and keeps the move modal open', () => {
    const form = setup();
    form.submit();
    assert.equal(form.button.disabled, true);
    assert.equal(form.request().headers.Accept, 'application/json');
    assert.equal(form.request().dataType, 'json');
    assert.equal(form.request().url, '/api/aftersales/customers/products/21/move');
    form.request().error({ status: 422, responseJSON: { message: 'This machine has open tickets. Close them first.' } });
    assert.equal(form.alert.hidden, false);
    assert.equal(form.message.textContent, 'This machine has open tickets. Close them first.');
    assert.equal(form.focused(), true);
    assert.equal(form.scrolled(), true);
    form.request().complete();
    assert.equal(form.button.disabled, false);
    form.submit();
    assert.equal(form.alert.hidden, true);
    assert.equal(form.message.textContent, '');
});

test('field validation and missing server responses also produce a visible warning', () => {
    const form = setup();
    form.submit();
    form.request().error({ responseJSON: { errors: { customer_id: ['Select a different destination customer.'] } } });
    assert.equal(form.alert.hidden, false);
    assert.equal(form.message.textContent, 'Select a different destination customer.');
    form.request().error({ status: 500 });
    assert.equal(form.alert.hidden, false);
    assert.equal(form.message.textContent, 'Failed to move machine');
});
