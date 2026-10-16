const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");
const source = fs.readFileSync(
    "resources/views/pages/aftersales/tickets/index.blade.php",
    "utf8",
);
const code = source.slice(
    source.indexOf("        function loadTicketSurveyLink("),
    source.indexOf("        $(document).on('click', '#tdShareLink'"),
);

function setup() {
    const elements = {},
        requests = [];
    function $(id) {
        return (elements[id] ||= {
            attrs: {},
            props: {},
            values: {},
            removeAttr(key) {
                delete this.attrs[key];
                return this;
            },
            attr(key, value) {
                this.attrs[key] = value;
                return this;
            },
            text(value) {
                this.content = value;
                return this;
            },
            prop(key, value) {
                this.props[key] = value;
                return this;
            },
            data(key, value) {
                this.values[key] = value;
                return this;
            },
        });
    }
    $.ajax = (options) => {
        const response = {
            options,
            done(fn) {
                this.success = fn;
                return this;
            },
            fail(fn) {
                this.failure = fn;
                return this;
            },
        };
        requests.push(response);
        return response;
    };
    const context = { $, currentTicket: { id: 28 } };
    vm.createContext(context);
    vm.runInContext(code, context);
    return { context, elements, requests };
}

test("ticket displays and shares the actual token URL returned by the API", () => {
    const state = setup();
    state.context.loadTicketSurveyLink(28);
    assert.equal(
        state.requests[0].options.url,
        "/api/aftersales/tickets/28/survey-link",
    );
    assert.equal(state.elements["#tdShareLink"].props.disabled, true);
    state.requests[0].success({
        data: {
            status: "active",
            can_share: true,
            url: "https://example.test/survey/secret-token",
        },
    });
    assert.equal(
        state.elements["#tdSurveyUrl"].attrs.href,
        "https://example.test/survey/secret-token",
    );
    assert.equal(
        state.elements["#tdShareLink"].values.url,
        state.elements["#tdSurveyUrl"].attrs.href,
    );
    assert.equal(state.elements["#tdShareLink"].props.disabled, false);
});

test("inactive links and failed requests never offer a fabricated URL", () => {
    const state = setup();
    state.context.loadTicketSurveyLink(28);
    state.requests[0].success({
        data: { status: "used", can_share: false, url: null },
    });
    assert.equal(state.elements["#tdSurveyStatus"].content, "Completed");
    assert.equal(state.elements["#tdSurveyUrl"].attrs.href, undefined);
    assert.equal(state.elements["#tdShareLink"].props.disabled, true);
    state.context.loadTicketSurveyLink(28);
    state.requests[1].failure();
    assert.equal(state.elements["#tdSurveyStatus"].content, "Unavailable");
    assert.equal(state.elements["#tdShareLink"].values.url, "");
});

test("a delayed response from another ticket cannot replace the displayed survey link", () => {
    const state = setup();
    state.context.loadTicketSurveyLink(28);
    state.context.currentTicket = { id: 29 };
    state.context.loadTicketSurveyLink(29);
    state.requests[1].success({
        data: { status: "active", can_share: true, url: "/survey/ticket-29" },
    });
    state.requests[0].success({
        data: { status: "active", can_share: true, url: "/survey/ticket-28" },
    });
    assert.equal(
        state.elements["#tdSurveyUrl"].attrs.href,
        "/survey/ticket-29",
    );
});
