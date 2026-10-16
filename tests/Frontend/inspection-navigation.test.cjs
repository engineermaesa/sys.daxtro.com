const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('resources/views/pages/aftersales/tickets/partials/documents-script.blade.php', 'utf8');
const code = source.slice(source.indexOf('    function step(number,'), source.indexOf("    $(document).on('click', '.sf-next'"));
function setup() {
    const state = { activeTab: 'D', openItem: 4 }, saved = [];
    let success, failure;
    const context = {
        S: state,
        itemsFlat: () => [
            { group: 'A', item: { number: '1' } },
            { group: 'D', item: { number: '4' } },
            { group: 'D', item: { number: '5' } },
            { group: 'E', item: { number: '6' } },
        ],
        formReadOnly: () => false,
        saveItem(number) {
            saved.push(number);
            return { done(fn) { success = fn; return this; }, fail(fn) { failure = fn; return this; } };
        },
        renderForm() {}, $: () => [], fail() {},
    };
    vm.createContext(context); vm.runInContext(code, context);
    return { state, saved, step: context.step, success: () => success(), failure: () => failure({}) };
}
test('Save & Next waits for save and advances from a string item number without returning to the first tab', () => {
    const s = setup(); s.step(4, 1, true);
    assert.deepEqual(s.saved, [4]);
    assert.equal(s.state.openItem, 4);
    s.success();
    assert.equal(s.state.openItem, 5);
    assert.equal(s.state.activeTab, 'D');
    s.step(5, 1, true); s.success();
    assert.equal(s.state.openItem, 6);
    assert.equal(s.state.activeTab, 'E');
});
test('failed save stays on the current item and tab', () => {
    const s = setup(); s.step(4, 1, true); s.failure();
    assert.equal(s.state.openItem, 4); assert.equal(s.state.activeTab, 'D');
});
test('previous crosses tabs; final and unknown items never wrap to the first tab', () => {
    const s = setup(); s.step(6, -1, false);
    assert.equal(s.state.openItem, 5); assert.equal(s.state.activeTab, 'D');
    s.state.activeTab = 'E'; s.step(6, 1, true); s.success();
    assert.equal(s.state.openItem, null); assert.equal(s.state.activeTab, 'E');
    s.step(99, 1, true);
    assert.deepEqual(s.saved, [6]); assert.equal(s.state.activeTab, 'E');
});
