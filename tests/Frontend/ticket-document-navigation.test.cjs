const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/pages/aftersales/tickets/partials/documents-script.blade.php', 'utf8');
const detailCode = source.slice(source.indexOf('    function loadTicketDetail()'), source.indexOf('    // ---------- v3.0 Ticket Detail'));

for (const type of ['agreement', 'service_form', 'invalid', null]) {
    test(`ticket detail opens requested document once after loading: ${type}`, () => {
        const opened = [];
        const context = {
            requestedDocument: type, S: {}, document: {}, currentId: () => 7,
            openAgreement: () => opened.push('agreement'), openForm: () => opened.push('service_form'),
            renderVisitHistory() {}, renderSurveyCard() {}, renderV3Detail() {}, renderFinalCost() {},
            $: () => ({ html() {}, trigger() {} }),
            call: (_method, path) => ({
                done(callback) { callback({ data: path.includes('exchange-rates') ? {} : { id: 7 } }); return this; },
                fail() { return this; },
            }),
        };
        vm.createContext(context);
        vm.runInContext(detailCode + '\nloadTicketDetail(); loadTicketDetail();', context);
        assert.deepEqual(opened, ['agreement', 'service_form'].includes(type) ? [type] : []);
        assert.equal(context.S.ticket.id, 7);
    });
}
