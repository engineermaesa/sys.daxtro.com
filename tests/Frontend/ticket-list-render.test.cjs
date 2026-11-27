const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/pages/aftersales/tickets/index.blade.php', 'utf8');
const listCode = source.slice(source.indexOf('    const PROGRESS_PERCENT'), source.indexOf('    function loadOverallRating('));

test('ticket rows render when the technician script is unavailable', () => {
    let html;
    const context = vm.createContext({
        window: {},
        CAN_MANAGE: false,
        IS_TECHNICIAN: false,
        $: () => ({ html: value => { html = value; }, find: () => ({ each() {} }) }),
    });

    vm.runInContext(listCode + `\nrenderTicketRows([{
        ticket_code: 'DAX-AF-TEST',
        technicians: [{ name: '<Alex>' }, { name: 'Budi' }],
    }, {
        ticket_code: 'DAX-AF-LEGACY',
        technicians: [],
        technician: { name: 'Dewi' },
    }]);`, context);

    assert.match(html, /DAX-AF-TEST/);
    assert.match(html, /&lt;Alex&gt;, Budi/);
    assert.match(html, /data-label="Technician">Dewi<\/td>/);
});

test('Ready to Close fills technician names when the technician script is unavailable', () => {
    const readyToClose = fs.readFileSync('resources/views/pages/aftersales/tickets/ready-to-close.blade.php', 'utf8');
    const renderRows = readyToClose.slice(readyToClose.indexOf('    function renderRows('), readyToClose.indexOf('    // Alert Happy Call overdue'));
    const names = [];
    const field = {
        attr() { return this; },
        css() { return this; },
        prop() { return this; },
        text(value) { names.push(value); },
    };
    const context = vm.createContext({
        window: {},
        TICKET_PAGE: '/aftersales/tickets/',
        $: () => ({ empty: () => ({ append() {} }) }),
        clone() {},
        fill: () => ({ find: () => field }),
    });

    vm.runInContext(renderRows + `\nrenderRows([
        { id: 1, technicians: [{ name: '<Alex>' }, { name: 'Budi' }] },
        { id: 2, technicians: [], technician: { name: 'Dewi' } },
        { id: 3 },
    ]);`, context);

    assert.deepEqual(names, ['<Alex>, Budi', 'Dewi', '-']);
});
