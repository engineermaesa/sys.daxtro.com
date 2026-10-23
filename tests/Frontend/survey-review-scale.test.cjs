const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/pages/satisfaction-survey/index.blade.php', 'utf8');

function setup(average = 2) {
    const elements = {};
    const element = id => elements[id] ||= {
        textContent: '', innerHTML: '', style: {},
        classList: { toggle() {}, add() {} },
    };
    const detail = {
        id: 1, ticket_code: 'QA-TC-IM-041', summary: { average_score: average },
        feedback: { ratings: [1, 2, 3].map(score => ({ label: `Rating ${score}`, score })) },
        security_trail: { review_status: 'needs_review' },
    };
    const context = {
        document: { getElementById: element, querySelectorAll: () => [] },
        canReview: true, recomingIds: {}, state: { page: 1 },
        rows: [{ id: 1, ticket_code: detail.ticket_code, average_score: average }],
        tbody: element('surveyTableBody'), current: { detail },
        rvNote: element('rvNote'), rvFeedback: element('rvFeedback'),
        setSignature() {}, renderActions() {},
    };
    vm.createContext(context);
    for (const [start, end] of [
        ['        const RATING_SCALE_MAX', '        function errMsg'],
        ['        function fmtDateTime', '        function loadList'],
        ['        function renderDetail()', "        document.getElementById('dResponsesBody').addEventListener"],
        ['        function renderReview(d, form)', "        document.getElementById('rvStartReview').addEventListener"],
    ]) {
        vm.runInContext(source.slice(source.indexOf(start), source.indexOf(end)), context);
    }
    return { context, detail, element };
}

test('TC-IM-041: Review and Detail bars use the normalized 1–3 scale, with 3 filling the entire bar', () => {
    const { context, detail, element } = setup();
    context.renderReview(detail, {});
    context.renderDetail();
    for (const id of ['rvRatings', 'dBreakdown']) {
        const html = element(id).innerHTML;
        assert.match(html, /width:33\.33333333333333%; background-color:#EC221F/);
        assert.match(html, /width:66\.66666666666666%; background-color:#E8B931/);
        assert.match(html, /width:100%; background-color:#115640/);
    }
    assert.equal(element('rvAvg').textContent, '2.0');
    assert.equal(element('dAvg').textContent, '2.0');
});

test('survey list averages display / 3 and missing responses still display a dash', () => {
    const form = setup(3);
    form.context.renderList({});
    assert.match(form.element('surveyTableBody').innerHTML, /3\.0 \/ 3/);
    assert.doesNotMatch(form.element('surveyTableBody').innerHTML, /\/ 5/);
    const empty = setup(null);
    empty.context.renderList({});
    assert.doesNotMatch(empty.element('surveyTableBody').innerHTML, /NaN|0\.0 \/ 3/);
});

test('Review, Detail and ticket survey preview expose the correct scale denominator', () => {
    for (const path of [
        'resources/views/pages/satisfaction-survey/review.blade.php',
        'resources/views/pages/satisfaction-survey/details.blade.php',
        'resources/views/pages/aftersales/tickets/partials/ticket-log-preview-modal.blade.php',
    ]) {
        const html = fs.readFileSync(path, 'utf8');
        assert.match(html, /\/ 3/);
        assert.doesNotMatch(html, /\/ 5/);
    }
});
