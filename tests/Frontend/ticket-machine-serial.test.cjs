const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function setup() {
    const elements = new Map();
    function $(selector) {
        if (!elements.has(selector)) {
            elements.set(selector, {
                value: 'filled', classes: new Set(),
                val(value) {
                    if (value === undefined) return this.value;
                    this.value = value;
                    return this;
                },
                addClass(name) { this.classes.add(name); return this; },
                removeClass(name) { this.classes.delete(name); return this; },
                toggleClass(name, enabled) {
                    if (enabled) this.classes.add(name);
                    else this.classes.delete(name);
                    return this;
                },
                next() { return this; },
                find() { return this; },
            });
        }
        return elements.get(selector);
    }
    const context = vm.createContext({
        $, currentStep: 1, Swal: { fire() {} },
        document: { addEventListener() {} },
    });
    const partial = fs.readFileSync('resources/views/pages/aftersales/tickets/partials/step-1-customer-machine.blade.php', 'utf8');
    vm.runInContext(partial.split('<script>')[1].split('</script>')[0], context);
    const wizard = fs.readFileSync('resources/views/pages/aftersales/tickets/create.blade.php', 'utf8');
    vm.runInContext(wizard.slice(wizard.indexOf('    const REQUIRED_FIELDS_BY_STEP'), wizard.indexOf('    function collectPayload()')), context);
    vm.runInContext('currentProducts = [{ id: 1, is_daxtro: false }, { id: 2, is_daxtro: true }]', context);
    $('#field-serial-number').val('');
    return { $, context };
}

test('non-Daxtro machine can proceed without a serial and clears the required marker and error', () => {
    const { $, context } = setup();
    $('#field-machine').val('1');
    $('#field-serial-number').addClass('border-red-500');
    context.resetMachineFields();
    assert.equal(context.validateCurrentStep(), true);
    assert.equal($('#serial-number-required').classes.has('hidden'), true);
    assert.equal($('#field-serial-number').classes.has('border-red-500'), false);
});

test('switching from non-Daxtro to Daxtro restores mandatory serial validation', () => {
    const { $, context } = setup();
    $('#field-machine').val('1');
    context.resetMachineFields();
    $('#field-machine').val('2');
    context.resetMachineFields();
    assert.equal(context.validateCurrentStep(), false);
    assert.equal($('#serial-number-required').classes.has('hidden'), false);
    assert.equal($('#field-serial-number').classes.has('border-red-500'), true);
    $('#field-serial-number').val('DX-123');
    assert.equal(context.validateCurrentStep(), true);
});

test('non-Daxtro still requires a customer and a selected machine', () => {
    const { $, context } = setup();
    $('#field-machine').val('1');
    $('#field-customer').val('');
    assert.equal(context.validateCurrentStep(), false);
    $('#field-customer').val('10');
    $('#field-machine').val('');
    assert.equal(context.validateCurrentStep(), false);
});
