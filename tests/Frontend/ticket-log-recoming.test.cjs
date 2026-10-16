const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");
const source = fs.readFileSync(
    "resources/views/pages/aftersales/tickets/partials/ticket-update-logs-modal.blade.php",
    "utf8",
);
const code = source.slice(
    source.indexOf("    $(function () {"),
    source.indexOf("    // Maps each loggable step"),
);

function setup() {
    const elements = {},
        requests = [],
        notices = [];
    function $(selector) {
        if (typeof selector === "function") return selector();
        return (elements[selector] ||= {
            handlers: {},
            value: "",
            disabled: false,
            on(event, fn) {
                this.handlers[event] = fn;
                return this;
            },
            toggle(value) {
                this.visible = value;
                return this;
            },
            trigger() {
                return this;
            },
            attr() {
                return this;
            },
            prop(key, value) {
                this[key] = value;
                return this;
            },
            text(value) {
                this.content = value;
                return this;
            },
            val() {
                return this.value;
            },
            html(value) {
                this.content = value;
                return this;
            },
        });
    }
    $.ajax = (options) => {
        requests.push(options);
    };
    const context = {
        $,
        currentUpdateLogsTicketId: 28,
        Date,
        Swal: { fire: (options) => notices.push(options) },
        loadTickets() {
            context.refreshed = true;
        },
        renderTicketUpdateLogsRows(logs) {
            context.logs = logs;
        },
    };
    vm.createContext(context);
    vm.runInContext(code, context);
    $("#ticketLogsRecomingDate").value = "2026-12-01";
    $("#ticketLogsRecomingNotes").value = "  Follow up  ";
    const submit = () =>
        $("#ticketLogsRecomingForm").handlers.submit.call(
            { reportValidity: () => true },
            { preventDefault() {} },
        );
    return { $, context, requests, notices, submit };
}

test("all reasons submit to the existing Recoming endpoint and prevent double submission", () => {
    for (const [label, reason] of [
        ["Spare part not available yet", "sparepart_unavailable"],
        ["Further work needed", "further_work_needed"],
        ["Site access / conditions", "site_access"],
        ["Other", "other"],
    ]) {
        const s = setup();
        s.$('input[name="ticket_logs_recoming_reason"]:checked').value = label;
        s.submit();
        s.submit();
        assert.equal(s.requests.length, 1);
        assert.equal(s.requests[0].url, "/api/aftersales/tickets/28/recoming");
        assert.equal(s.requests[0].data.reason, reason);
        assert.equal(s.requests[0].data.planned_visit_at, "2026-12-01");
        assert.equal(s.requests[0].data.notes, "Follow up");
        assert.equal(s.$("#ticketLogsRecomingForm button").disabled, true);
    }
});
test("successful scheduling refreshes ticket list and history", () => {
    const s = setup();
    s.submit();
    s.requests[0].success();
    assert.equal(s.context.refreshed, true);
    assert.equal(s.$("#ticketLogsHistoryPanel").visible, true);
    s.requests[1].success({ data: { logs: [{ step: "recoming" }] } });
    assert.equal(s.context.logs[0].step, "recoming");
    s.requests[0].complete();
    assert.equal(s.$("#ticketLogsRecomingForm button").disabled, false);
});
test("server validation errors retain the form for correction and retry", () => {
    const s = setup();
    s.submit();
    s.requests[0].error({
        responseJSON: {
            errors: { planned_visit_at: ["Date must not be in the past."] },
        },
    });
    assert.equal(
        s.$("#ticketLogsRecomingFeedback").content,
        "Date must not be in the past.",
    );
    assert.equal(s.notices.length, 0);
    s.requests[0].complete();
    s.submit();
    assert.equal(s.requests.length, 2);
});
