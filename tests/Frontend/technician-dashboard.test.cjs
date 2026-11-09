const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const view = fs.readFileSync('resources/views/pages/dashboard/after-sales/technician/index.blade.php', 'utf8');
const script = view.match(/<script>([\s\S]*?)<\/script>/)[1];
const flush = () => new Promise(resolve => setImmediate(resolve));

class Element {
    constructor() {
        this.children = []; this.nodes = new Map(); this.events = {}; this.attributes = {};
        this.style = {}; this.textContent = ''; this.disabled = false;
        this.classes = new Set();
        this.classList = {
            add: (...names) => names.forEach(n => this.classes.add(n)),
            remove: (...names) => names.forEach(n => this.classes.delete(n)),
            toggle: (name, force) => force ? this.classes.add(name) : this.classes.delete(name),
        };
    }
    querySelector(selector) {
        if (!this.nodes.has(selector)) this.nodes.set(selector, new Element());
        return this.nodes.get(selector);
    }
    setAttribute(key, value) { this.attributes[key] = value; }
    addEventListener(event, callback) { this.events[event] = callback; }
    append(...nodes) { this.children.push(...nodes); }
    replaceChildren(...nodes) { this.children = nodes; }
    cloneNode() { return new Element(); }
    showModal() { this.open = true; }
    close() { this.open = false; this.events.close?.(); }
}

function summary(tasks = []) {
    return { data: {
        rating: { score: 2.3, max: 3 },
        tickets: { total_active: 7, statuses: [
            { key: 'assigned', label: 'Assigned', count: 2 },
            { key: 'on_site', label: 'On Site', count: 1 },
            { key: 'repair', label: 'Repair', count: 4 },
            { key: 'waiting_sparepart', label: 'Waiting', count: 0 },
        ] },
        sla: { value: null, target: 96, months: [] },
        today: { total: tasks.length, has_more: false, items: tasks },
        closed: { total: 3, weeks: [{ label: 'W1', total: 3, on_time: 1, overtime: 1, unknown: 1 }] },
        todo: { items: [] },
    } };
}
const task = (id, time, status = 'next') => ({ id, time, ticket_code: `LIVE-${id}`,
    customer_name: `Customer ${id}`, machine_name: 'Machine', machine_category: 'Ice', type: 'visit_site', status });

function setup(responder) {
    const nodes = new Map(), requests = [], charts = {};
    const element = id => {
        if (!nodes.has(id)) nodes.set(id, new Element());
        return nodes.get(id);
    };
    const legends = Array.from({ length: 4 }, () => new Element());
    const document = {
        getElementById: element, createElement: () => new Element(),
        querySelector: () => ({ value: 'this_month' }), querySelectorAll: () => [],
    };
    const root = element('tech-dashboard');
    root.dataset = { ticketUrl: '/aftersales/tickets/__ID__' };
    root.ownerDocument = document;
    root.querySelectorAll = () => legends;
    for (const id of ['tech-task-template', 'tech-todo-template'])
        element(id).content = { firstElementChild: new Element() };
    const context = { document, AbortController, URLSearchParams,
        window: { AS: { colors: {}, baseChart: {}, mount: (id, options) => charts[id] = options } },
        fetch: async (url, options) => {
            requests.push({ url, options });
            const result = await responder(url);
            return { ok: true, json: async () => result };
        },
    };
    vm.runInNewContext(script, context);
    return { element, legends, requests, charts };
}

test('dashboard renders live task rows and status counts rather than the screenshot sample', async () => {
    const f = setup(() => summary([task(31, '07:15', 'done'), task(48, '14:45', 'waiting')]));
    await flush();
    const rows = f.element('tech-tasks').children;
    assert.deepEqual(rows.map(r => r.querySelector('[data-time]').textContent), ['07:15', '14:45']);
    assert.deepEqual(rows.map(r => r.querySelector('[data-code]').textContent), ['LIVE-31', 'LIVE-48']);
    assert.equal(rows[1].querySelector('[data-pill]').textContent, 'Waiting');
    assert.deepEqual(f.legends.map(r => r.querySelector('[data-legend-count]').textContent), [2, 1, 4, 0]);
    assert.equal(f.element('tech-tasks-total').textContent, '2 of 2 tasks today (Asia/Jakarta)');
    assert.equal(f.element('tech-closed-total').textContent, 3);
    assert.equal(f.element('tech-sla-value').textContent, '—');
    assert.equal(f.requests[0].options.credentials, 'same-origin');
    assert.equal(f.element('tech-dashboard').attributes['aria-busy'], 'false');
});

test('status legend and chart open the matching filtered API list with ticket links and pagination', async () => {
    const f = setup(url => {
        if (!url.includes('/tickets?')) return summary();
        const page = Number(new URL(url, 'http://localhost').searchParams.get('page'));
        return { data: [{ id: page + 40, ticket_code: `T-${page}` }], total: 21, current_page: page, last_page: 2 };
    });
    await flush();
    f.legends[2].onclick();
    await flush();
    assert.match(f.requests.at(-1).url, /metric=status&status=repair&page=1/);
    const dialog = f.element('tech-ticket-list');
    assert.equal(dialog.open, true);
    assert.equal(dialog.querySelector('[data-list-body]').children[0].href, '/aftersales/tickets/41');
    await dialog.querySelector('[data-list-more]').events.click();
    assert.match(f.requests.at(-1).url, /status=repair&page=2/);
    assert.equal(dialog.querySelector('[data-list-body]').children.length, 2);
    assert.equal(dialog.querySelector('[data-list-more]').classes.has('hidden'), true);
    f.charts['tech-chart-tickets'].chart.events.dataPointSelection(null, null, { dataPointIndex: 1 });
    await flush();
    assert.match(f.requests.at(-1).url, /status=on_site&page=1/);
    let prevented = false;
    f.legends[0].onkeydown({ key: 'Enter', preventDefault() { prevented = true; } });
    await flush();
    assert.equal(prevented, true);
    assert.match(f.requests.at(-1).url, /status=assigned&page=1/);
});

test('today load more continues the summary page size and keeps API ordering', async () => {
    const initial = summary(Array.from({ length: 10 }, (_, i) => task(i + 1, '08:00')));
    initial.data.today.total = 11; initial.data.today.has_more = true;
    const f = setup(url => url.includes('/tasks?')
        ? { data: [task(11, '17:00')], total: 11, current_page: 2, last_page: 2 } : initial);
    await flush();
    await f.element('tech-tasks-more').events.click();
    assert.match(f.requests.at(-1).url, /tasks\?page=2&per_page=10/);
    assert.equal(f.element('tech-tasks').children.length, 11);
    assert.equal(f.element('tech-tasks').children[10].querySelector('[data-time]').textContent, '17:00');
    assert.equal(f.element('tech-tasks-more').classes.has('hidden'), true);
});

test('empty results and request failures never display sample tasks or counts', async () => {
    const empty = summary();
    empty.data.tickets.total_active = 0;
    empty.data.tickets.statuses.forEach(s => s.count = 0);
    const f = setup(() => empty);
    await flush();
    assert.equal(f.element('tech-tasks').children[0].textContent, 'No assigned tasks today.');
    assert.deepEqual(f.legends.map(r => r.querySelector('[data-legend-count]').textContent), [0, 0, 0, 0]);
    const failed = setup(() => { throw new Error('Network error'); });
    await flush();
    assert.equal(failed.element('tech-tasks').children.length, 0);
    assert.match(failed.element('tech-error').querySelector('[data-error-text]').textContent, /Failed to load dashboard/);
});
