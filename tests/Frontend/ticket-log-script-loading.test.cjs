const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");

test("Update Logs script initializes before jQuery is loaded without failing", () => {
    const source = fs.readFileSync(
        "resources/views/pages/aftersales/tickets/partials/ticket-update-logs-modal.blade.php",
        "utf8",
    );
    const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];
    const listeners = [];
    const context = {
        document: {
            addEventListener(event, handler) {
                listeners.push({ event, handler });
            },
        },
    };
    vm.createContext(context);
    assert.doesNotThrow(() => vm.runInContext(script, context));
    assert.equal(vm.runInContext("currentUpdateLogsTicketId", context), null);
    assert.equal(typeof context.openTicketUpdateLogs, "function");
    assert.equal(listeners.length, 2);
    assert.ok(
        listeners.every((listener) => listener.event === "DOMContentLoaded"),
    );
});
