const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/pages/aftersales/tickets/partials/survey-on-behalf-modal.blade.php', 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];

function setup(canManage = true) {
    const document = {}, handlers = {}, elements = {}, requests = [];
    function $(selector) {
        if (selector === document) return {
            on(event, ...args) { handlers[event] = args.at(-1); },
        };
        return elements[selector] ||= {
            hidden: true,
            toggleClass(name, value) { if (name === 'hidden') this.hidden = value; return this; },
            text(value) { this.content = value; return this; },
            on() { return this; },
        };
    }
    $.ajax = options => {
        const request = {
            options,
            done(fn) { this.success = fn; return this; },
            fail(fn) { this.failure = fn; return this; },
        };
        requests.push(request);
        return request;
    };
    vm.runInNewContext(script, { $, document, CAN_MANAGE: canManage });
    return {
        emit(event, ...args) { handlers[event]({}, ...args); },
        visible() { return elements['#tdFillOnBehalf'].hidden === false; },
        requests,
    };
}

test('only phone-call surveys offer Fill on Behalf, including after changing tickets', () => {
    const state = setup();
    state.emit('ticket:survey-link', 1, { variant: 'site', template: { sections: [] } });
    assert.equal(state.visible(), false);
    assert.equal(state.requests.length, 0);
    state.emit('ticket:survey-link', 2, { variant: 'phone_call' });
    assert.equal(state.visible(), false);
    state.requests[0].success({ data: { can_submit: true, template: { sections: [] } } });
    assert.equal(state.visible(), true);
    state.emit('ticket:survey-link', 3, null);
    assert.equal(state.visible(), false);
    state.emit('ticket:survey-link', 3, { variant: 'site', template: { sections: [] } });
    assert.equal(state.visible(), false);
});

test('phone-call entry keeps permissions and submission eligibility', () => {
    const denied = setup(false);
    denied.emit('ticket:survey-link', 1, { variant: 'phone_call' });
    denied.requests[0].success({ data: { can_submit: true, template: {} } });
    assert.equal(denied.visible(), false);

    const completed = setup();
    completed.emit('ticket:survey-link', 1, { variant: 'phone_call' });
    completed.requests[0].success({ data: { can_submit: false, template: {} } });
    assert.equal(completed.visible(), false);

    const unavailable = setup();
    unavailable.emit('ticket:survey-link', 1, { variant: 'phone_call' });
    unavailable.requests[0].failure();
    assert.equal(unavailable.visible(), false);
});

test('a delayed phone-call form cannot reveal the button on another ticket', () => {
    const state = setup();
    state.emit('ticket:survey-link', 1, { variant: 'phone_call' });
    state.emit('ticket:survey-link', 2, { variant: 'site', template: {} });
    state.requests[0].success({ data: { can_submit: true, template: {} } });
    state.emit('ticket:detail', { id: 1, capabilities: { can_edit: true } });
    assert.equal(state.visible(), false);
});
