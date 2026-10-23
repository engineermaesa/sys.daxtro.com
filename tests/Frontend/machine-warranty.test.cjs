const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/pages/aftersales/customers/create-machine.blade.php', 'utf8');
const code = source.slice(source.indexOf('    function calculateWarrantyPeriod()'), source.indexOf('    // Filled from GET /machine-references'));

function setup({ months = 12, picker = true } = {}) {
    const fields = {};
    for (const name of ['start', 'end', 'period']) {
        fields[`#machine-warranty-${name}`] = { value: '', handlers: {} };
    }
    const options = {};
    const context = {
        pageConfig: { warrantyMonths: months },
        document: { getElementById: id => fields[`#${id}`] },
        $(selector) {
            const field = fields[selector];
            return {
                val(value) {
                    if (value === undefined) return field.value;
                    field.value = value;
                    return this;
                },
                on(event, handler) { field.handlers[event] = handler; return this; },
            };
        },
    };
    if (picker) {
        context.flatpickr = (selector, config) => {
            options[selector] = config;
            fields[selector]._flatpickr = {
                setDate(value) { fields[selector].value = value; },
            };
        };
    }
    vm.createContext(context);
    vm.runInContext(code, context);
    context.initDatePickers();
    return {
        fields, options,
        start(value) {
            fields['#machine-warranty-start'].value = value;
            (options['#machine-warranty-start']?.onChange || fields['#machine-warranty-start'].handlers.change)();
        },
        end(value) {
            fields['#machine-warranty-end'].value = value;
            (options['#machine-warranty-end']?.onChange || fields['#machine-warranty-end'].handlers.change)();
        },
        value: name => fields[`#machine-warranty-${name}`].value,
    };
}

test('TC-IM-028: choosing Start Date immediately fills Finish Date and the default 1 Year period', () => {
    const form = setup();
    form.start('2026-10-23');
    assert.equal(form.value('end'), '2027-10-23');
    assert.equal(form.value('period'), '1 Year');
    form.start('2026-11-10');
    assert.equal(form.value('end'), '2027-11-10');
});

test('linked product warranty configuration controls the calculated date', () => {
    const form = setup({ months: 24 });
    form.start('2026-10-23');
    assert.equal(form.value('end'), '2028-10-23');
    assert.equal(form.value('period'), '2 Years');
});

test('month-end and leap-day dates are clamped instead of overflowing into the next month', () => {
    const leap = setup();
    leap.start('2024-02-29');
    assert.equal(leap.value('end'), '2025-02-28');
    const monthly = setup({ months: 1 });
    monthly.start('2026-01-31');
    assert.equal(monthly.value('end'), '2026-02-28');
});

test('Finish Date remains editable and updates the displayed full-month period', () => {
    const form = setup();
    form.start('2026-10-21');
    form.end('2027-10-01');
    assert.equal(form.value('end'), '2027-10-01');
    assert.equal(form.value('period'), '11 Months');
});

test('clearing Start Date clears the derived Finish Date and period', () => {
    const form = setup();
    form.start('2026-10-23');
    form.start('');
    assert.equal(form.value('end'), '');
    assert.equal(form.value('period'), '');
});

test('date calculations still work with native inputs when the date picker CDN is unavailable', () => {
    const form = setup({ picker: false });
    form.start('2026-10-23');
    assert.equal(form.value('end'), '2027-10-23');
    assert.equal(form.value('period'), '1 Year');
    form.end('2027-04-23');
    assert.equal(form.value('period'), '6 Months');
});
