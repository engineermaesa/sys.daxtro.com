(() => {
    "use strict";
    const root = document.getElementById("document-template-app");
    if (!root || root.dataset.template !== "service-inspection-report") return;
    const API = "/api/aftersales/inspection-templates",
        content = document.getElementById("dt-content"),
        actions = document.getElementById("dt-actions"),
        feedback = document.getElementById("dt-feedback"),
        dialog = document.getElementById("dt-preview");
    const esc = (v) =>
        String(v ?? "").replace(
            /[&<>"']/g,
            (c) =>
                ({
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#39;",
                })[c],
        );
    const btn = (text, action, attrs = "", cls = "") =>
        `<button type="button" class="dt-button ${cls}" data-action="${action}" ${attrs}>${text}</button>`;
    const head = (label, title, action = "") =>
        `<div class="dt-card-head"><div><div class="dt-eyebrow">${esc(label)}</div><h2>${esc(title)}</h2></div>${action}</div>`;
    const code = (i) => {
        let s = "";
        do {
            s = String.fromCharCode(65 + (i % 26)) + s;
            i = Math.floor(i / 26) - 1;
        } while (i >= 0);
        return s;
    };
    const date = (v) =>
        v
            ? new Date(v).toLocaleString("en-GB", {
                  timeZone: "Asia/Jakarta",
                  day: "2-digit",
                  month: "short",
                  year: "numeric",
                  hour: "2-digit",
                  minute: "2-digit",
              })
            : "—";
    let versions = [],
        audit = null,
        current = null,
        selected = 0,
        listing = true,
        dirty = false,
        busy = false,
        previewToken = null,
        dragIndex = null;
    const editable = () => current?.status === "draft";
    const dis = () => (editable() ? "" : "disabled");
    function message(text, error = false) {
        feedback.textContent = text;
        feedback.style.color = error ? "#b42318" : "#115640";
    }
    async function request(path = "", method = "GET", body) {
        const response = await fetch(API + path, {
            method,
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]")
                    .content,
            },
            ...(body === undefined ? {} : { body: JSON.stringify(body) }),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(
                Object.values(result.errors || {}).flat()[0] ||
                    result.message ||
                    `Request failed (${response.status}).`,
            );
            error.status = response.status;
            throw error;
        }
        return result;
    }
    async function loadIndex(page = 1) {
        const result = await request(`?page=${page}`);
        versions = result.data.versions;
        audit = result.data.audit;
    }
    function setCurrent(data) {
        current = structuredClone(data);
        selected = Math.min(selected, Math.max(0, current.groups.length - 1));
        dirty = false;
        previewToken = null;
    }
    function changed() {
        dirty = true;
        previewToken = null;
        message("Unsaved changes");
    }
    async function save() {
        if (!editable() || !dirty) return;
        const result = await request(`/${current.id}`, "PUT", {
            lock_version: current.lock_version,
            title: current.title,
            groups: current.groups,
            diagnosis_section_title: current.diagnosis_section_title,
            diagnosis_fields: current.diagnosis_fields,
        });
        setCurrent(result.data);
        message("Draft saved.");
    }
    function number(gi, ii) {
        let n = 0;
        for (let g = 0; g <= gi; g++) {
            if (!current.groups[g].is_active) continue;
            const items = current.groups[g].items;
            for (let i = 0; i < items.length; i++) {
                if (items[i].is_active) n++;
                if (g === gi && i === ii) return items[i].is_active ? n : "—";
            }
        }
        return "—";
    }
    function render() {
        document.getElementById("dt-document-name").textContent =
            "Service Inspection & Report";
        document.getElementById("dt-title").textContent = listing
            ? "Template versions"
            : `${editable() ? "Edit" : "View"} Service Inspection & Report`;
        document.getElementById("dt-version").textContent = listing
            ? `${versions.length} versions`
            : `v${current?.version ?? "—"}`;
        const status = document.getElementById("dt-status");
        status.hidden = listing;
        status.textContent = current?.status || "";
        status.className = `dt-badge dt-${current?.status || "inactive"}`;
        const create = btn(
            versions.some((v) => v.status === "draft")
                ? "Continue editing draft"
                : "＋ Create new version",
            "create",
            "",
            "dt-primary",
        );
        actions.innerHTML = listing
            ? create
            : `${btn("Version history", "versions")}${editable() ? btn("Save draft", "save") : ""}${btn("◉ Preview", "preview")}${editable() ? btn(`✓ Activate v${current.version}`, "activate", "", "dt-primary") : create}`;
        if (listing) renderVersions();
        else if (current) renderEditor();
    }
    function renderVersions() {
        content.innerHTML = `<div class="dt-card">${head("Version history", "Service Inspection & Report")}<div class="dt-table-wrap"><table class="dt-table"><thead><tr><th>Version</th><th>Status</th><th>Modified By</th><th>Effective Since</th><th>Actions</th></tr></thead><tbody>${versions.map((v) => `<tr><td><strong>v${v.version}</strong></td><td><span class="dt-badge dt-${esc(v.status)}">${esc(v.status)}</span></td><td>${esc(v.modified_by)}</td><td>${date(v.activated_at)}</td><td><div class="dt-actions">${btn(v.status === "draft" ? "Edit" : "View", "open", `data-id="${v.id}"`, v.status === "draft" ? "dt-primary" : "")}${btn("Preview", "preview-version", `data-id="${v.id}"`)}</div></td></tr>`).join("") || '<tr><td colspan="5">No inspection template available.</td></tr>'}</tbody></table></div></div><div class="dt-card dt-card-body"><h2>Version rules</h2><div class="dt-rules">${["Only one version can be active at a time.", "Active versions cannot be edited directly.", "Create a draft from the active version before making changes.", "Inactive versions remain available for historical reports."].map((text) => `<p>${text}</p>`).join("")}</div></div><div class="dt-card">${head("Activity history", "Audit Log", `<span class="dt-badge">${audit?.total || 0} activities</span>`)}<div class="dt-audit"><table class="dt-table"><thead><tr><th>Time</th><th>Modified By</th><th>Activity</th><th>Version</th></tr></thead><tbody>${(audit?.data || []).map((x) => `<tr><td>${date(x.created_at)}</td><td>${esc(x.actor_name)}</td><td>${esc({ template_draft_created: "Created draft from active version", template_draft_saved: "Saved inspection changes", template_activated: "Activated template version" }[x.action] || x.action)}</td><td>v${x.version}</td></tr>`).join("") || '<tr><td colspan="4">No activity yet.</td></tr>'}</tbody></table></div>${audit?.last_page > 1 ? `<div class="dt-card-body dt-actions">${btn("Previous", "audit-page", `data-page="${audit.current_page - 1}" ${audit.current_page === 1 ? "disabled" : ""}`)}<span>Page ${audit.current_page} of ${audit.last_page}</span>${btn("Next", "audit-page", `data-page="${audit.current_page + 1}" ${audit.current_page === audit.last_page ? "disabled" : ""}`)}</div>` : ""}</div>`;
    }
    const input = (label, field, value, attrs = "") =>
        `<label><span class="dt-field">${label}</span><input type="text" data-field="${field}" value="${esc(value)}" ${attrs} ${dis()}></label>`;
    function warnings() {
        const list = [];
        for (const g of current.groups) {
            if (!g.is_active) continue;
            for (const item of g.items) {
                if (
                    !item.is_active ||
                    !(item.types_restricted || item.machine_types.length)
                )
                    continue;
                const missing = (current.product_types || []).filter(
                    (t) => !item.machine_types.includes(t.id),
                );
                if (missing.length)
                    list.push(
                        `<li><strong>${esc(item.name)}</strong>: ${missing.map((t) => esc(t.name)).join(", ")}</li>`,
                    );
            }
        }
        return list.length
            ? `<details class="dt-banner"><summary>${list.length} restricted item(s) exclude Product Types from the current master</summary><p style="margin-top:10px">These machine types will receive automatic N/A until included in the item’s applicability settings.</p><ul style="padding-left:20px">${list.join("")}</ul></details>`
            : "";
    }
    function fieldCard(field, ii, fi, itemDisabled) {
        const attr = `data-item="${ii}" data-field-index="${fi}"`,
            blocked =
                dis() || (!field.is_active || itemDisabled ? "disabled" : "");
        return `<div class="it-field ${!field.is_active ? "dt-disabled" : ""}"><div class="dt-actions" style="justify-content:space-between;margin-bottom:12px"><strong>Field ${fi + 1}</strong><div>${btn("↑", "field-up", `${attr} ${dis()} ${fi === 0 ? "disabled" : ""}`, "dt-muted")}${btn("↓", "field-down", `${attr} ${dis()} ${fi === current.groups[selected].items[ii].fields.length - 1 ? "disabled" : ""}`, "dt-muted")}${btn(field.is_active ? "Disable" : "↶ Restore", "toggle-field", `${attr} ${dis()}`, "dt-muted")}</div></div><div class="it-field-grid"><label><span class="dt-field">Label</span><input type="text" value="${esc(field.label)}" data-field="label" ${attr} ${blocked}></label><label><span class="dt-field">Type</span><select data-field="type" ${attr} ${blocked}>${[
            ["number", "Number"],
            ["checklist", "Checkbox / options"],
            ["text", "Text"],
        ]
            .map(
                ([key, label]) =>
                    `<option value="${key}" ${field.type === key ? "selected" : ""}>${label}</option>`,
            )
            .join(
                "",
            )}</select></label>${field.type === "number" ? `<label><span class="dt-field">Units (comma separated)</span><input type="text" value="${esc(field.units.join(", "))}" data-field="units" ${attr} ${blocked}></label><label><span class="dt-field">Minimum</span><input type="number" step="any" value="${esc(field.min_value)}" data-field="min_value" ${attr} ${blocked}></label><label><span class="dt-field">Maximum</span><input type="number" step="any" value="${esc(field.max_value)}" data-field="max_value" ${attr} ${blocked}></label><p class="dt-description">Evidence photo is required for numeric fields.</p>` : ""}${field.type === "checklist" ? `<label style="grid-column:1/-1"><span class="dt-field">Options (one per line)</span><textarea data-field="options" ${attr} ${blocked}>${esc(field.options.join("\n"))}</textarea></label><label class="dt-check"><input type="checkbox" data-field="is_multiple" ${attr} ${field.is_multiple ? "checked" : ""} ${blocked}> Allow multiple selections</label>` : ""}${field.type === "text" ? `<label><span class="dt-field">Required when checklist equals (optional)</span><input type="text" value="${esc(field.required_when)}" data-field="required_when" ${attr} ${blocked}></label>` : ""}</div></div>`;
    }
    function renderEditor() {
        const g = current.groups[selected];
        content.innerHTML = `${!editable() ? '<div class="dt-banner">This version is read-only. Create a draft from the active version to make changes.</div>' : ""}${warnings()}<div class="dt-editor-grid"><div><div class="dt-card">${head(g ? `Group ${code(selected)}` : "Inspection groups", g?.title || "No groups", btn("＋ Add item", "add-item", `${dis()} ${!g ? "disabled" : ""}`))}<div class="dt-card-body"><p class="dt-description">Configure inspection points, standards, fields, and applicable machine types.</p>${g ? g.items.map((item, ii) => `<div class="dt-clause ${!item.is_active || !g.is_active ? "dt-disabled" : ""}"><div class="dt-clause-head"><span class="dt-code">${number(selected, ii)}</span><strong>${esc(item.name || "New inspection item")}</strong><div class="dt-actions" style="margin-left:auto">${btn("↑", "item-up", `data-item="${ii}" ${dis()} ${ii === 0 ? "disabled" : ""}`, "dt-muted")}${btn("↓", "item-down", `data-item="${ii}" ${dis()} ${ii === g.items.length - 1 ? "disabled" : ""}`, "dt-muted")}${btn(item.is_active ? "Disable" : "↶ Restore", "toggle-item", `data-item="${ii}" ${dis()}`, "dt-muted")}</div></div><div class="dt-card-body"><div class="it-field-grid">${input("Item name", "name", item.name, `data-item="${ii}"`)}${input("Standard text", "standard", item.standard, `data-item="${ii}"`)}<label style="grid-column:1/-1"><span class="dt-field">Applies to Product Types (empty selection = all types)</span><select multiple size="4" data-field="machine_types" data-item="${ii}" ${dis()}>${(current.product_types || []).map((t) => `<option value="${t.id}" ${item.machine_types.includes(t.id) ? "selected" : ""}>${esc(t.name)}</option>`).join("")}</select><small>Use Ctrl / Cmd to select or clear multiple types.</small></label></div><div style="margin-top:18px">${item.fields.map((f, fi) => fieldCard(f, ii, fi, !item.is_active || !g.is_active)).join("")}</div>${btn("＋ Add field", "add-field", `data-item="${ii}" ${dis()}`)}</div></div>`).join("") : ""}${btn("＋ Add item", "add-item", `${dis()} ${!g ? "disabled" : ""}`, "dt-add-section")}</div></div><div class="dt-card">${head("Diagnosis settings", current.diagnosis_section_title)}<div class="dt-card-body">${input("Section title", "diagnosis_section_title", current.diagnosis_section_title)}<p class="dt-description" style="margin-top:12px">The three diagnosis fields and their order are fixed. Labels and instructions can be edited.</p>${["problem", "cause", "action"].map((key) => `<div class="it-field-grid" style="margin-bottom:14px">${input(`${key} label`, "diagnosis-label", current.diagnosis_fields[key].label, `data-diagnosis="${key}"`)}${input("Instructions", "diagnosis-hint", current.diagnosis_fields[key].hint, `data-diagnosis="${key}"`)}</div>`).join("")}</div></div></div><aside><div class="dt-card">${head("Group settings", "Document structure")}<div class="dt-card-body"><div class="dt-actions" style="justify-content:space-between"><strong>Manage group order</strong>${btn("＋ Add", "add-group", dis())}</div><p class="dt-description" style="margin-top:14px">Drag groups or use the arrows to change their order.</p>${current.groups.map((group, gi) => `<div class="dt-order ${gi === selected ? "selected" : ""}" draggable="${editable()}" data-order="${gi}"><span>⠿</span><strong>${code(gi)}</strong><button type="button" data-action="select" data-group="${gi}" style="flex:1;text-align:left;font-size:11px">${esc(group.title || "Untitled group")}${!group.is_active ? " (disabled)" : ""}</button><button type="button" data-action="group-up" data-group="${gi}" aria-label="Move group up" ${dis()} ${gi === 0 ? "disabled" : ""}>↑</button><button type="button" data-action="group-down" data-group="${gi}" aria-label="Move group down" ${dis()} ${gi === current.groups.length - 1 ? "disabled" : ""}>↓</button></div>`).join("")}</div>${g ? `<div class="dt-settings">${input("Group title", "group-title", g.title)}<label class="dt-check"><input type="checkbox" data-field="group-disabled" ${!g.is_active ? "checked" : ""} ${dis()}> Disable this group</label></div>` : ""}<div class="dt-settings">${input("Document title", "document-title", current.title)}</div></div></aside></div>`;
    }
    async function preview(id = current.id) {
        if (current?.id === id && dirty) await save();
        const target =
            current?.id === id ? current : (await request(`/${id}`)).data;
        const result = await request(`/${id}/preview`, "POST", {
            lock_version: target.lock_version,
        });
        if (current?.id === id) previewToken = result.preview_token;
        document.getElementById("dt-preview-content").innerHTML =
            `<h2>${esc(result.data.title)} · v${result.data.version}</h2>${result.groups.map((g) => `<section class="dt-preview-section"><h2>${esc(g.code)}. ${esc(g.title)}</h2>${g.items.map((item) => `<p><strong>${item.number}. ${esc(item.name)}</strong><br>${esc(item.standard)}</p><ul>${item.fields.map((f) => `<li>${esc(f.label)} (${esc(f.type)}) ${esc(f.units.join(", "))}${f.type === "checklist" ? ` · ${f.options.map(esc).join(" / ")}` : ""}${f.type === "number" ? ` · Evidence photo required${f.min_value !== null ? ` · Min ${esc(f.min_value)}` : ""}${f.max_value !== null ? ` · Max ${esc(f.max_value)}` : ""}` : ""}</li>`).join("")}</ul><p class="dt-description">Applies to: ${item.machine_types.length ? item.machine_types.map((id) => esc(result.data.product_types.find((t) => t.id === id)?.name || id)).join(", ") : item.types_restricted ? "No matching Product Type" : "All Product Types"}</p>`).join("")}</section>`).join("")}<section class="dt-preview-section"><h2>${esc(result.data.diagnosis_section_title)}</h2>${["problem", "cause", "action"].map((key) => `<p><strong>${esc(result.data.diagnosis_fields[key].label)}</strong><br>${esc(result.data.diagnosis_fields[key].hint)}</p>`).join("")}</section>${result.data.status === "draft" && current?.id === id ? `<div style="margin-top:20px">${btn(`✓ Activate v${result.data.version}`, "confirm-activate", "", "dt-primary")}</div>` : ""}`;
        dialog.showModal();
    }
    async function discard() {
        if (!dirty) return true;
        return (
            await Swal.fire({
                title: "Discard unsaved changes?",
                text: "Your unsaved draft changes will be lost.",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Discard changes",
                confirmButtonColor: "#115640",
            })
        ).isConfirmed;
    }
    async function run(work) {
        if (busy) return;
        busy = true;
        root.setAttribute("aria-busy", "true");
        root.querySelectorAll("button,input,select,textarea").forEach(
            (el) => (el.disabled = true),
        );
        try {
            await work();
        } catch (error) {
            message(error.message, true);
            if (error.status === 409) {
                previewToken = null;
                const reload = document.createElement("button");
                reload.type = "button";
                reload.className = "dt-button";
                reload.dataset.action = "reload";
                reload.textContent = "Reload draft";
                feedback.append(reload);
            }
        } finally {
            busy = false;
            root.removeAttribute("aria-busy");
            render();
            document.getElementById("dt-close-preview").disabled = false;
            dialog
                .querySelectorAll("[data-action]")
                .forEach((el) => (el.disabled = false));
        }
    }
    root.addEventListener("input", (e) => {
        if (!editable() || busy || !e.target.dataset.field) return;
        const el = e.target,
            field = el.dataset.field,
            g = current.groups[selected],
            ii = Number(el.dataset.item),
            fi = Number(el.dataset.fieldIndex);
        if (field === "document-title") current.title = el.value;
        else if (field === "group-title") g.title = el.value;
        else if (field === "group-disabled") g.is_active = !el.checked;
        else if (field === "diagnosis_section_title")
            current.diagnosis_section_title = el.value;
        else if (field === "diagnosis-label" || field === "diagnosis-hint")
            current.diagnosis_fields[el.dataset.diagnosis][
                field === "diagnosis-label" ? "label" : "hint"
            ] = el.value;
        else if (el.dataset.fieldIndex !== undefined) {
            const f = g.items[ii].fields[fi];
            f[field] =
                field === "units"
                    ? el.value
                          .split(",")
                          .map((x) => x.trim())
                          .filter(Boolean)
                    : field === "options"
                      ? el.value
                            .split("\n")
                            .map((x) => x.trim())
                            .filter(Boolean)
                      : field === "is_multiple"
                        ? el.checked
                        : field === "min_value" || field === "max_value"
                          ? el.value === ""
                              ? null
                              : Number(el.value)
                          : el.value;
            if (field === "type") {
                f.is_multiple = false;
                f.min_value = null;
                f.max_value = null;
                f.required_when = null;
            }
        } else if (field === "machine_types") {
            g.items[ii].machine_types = Array.from(el.selectedOptions).map(
                (o) => Number(o.value),
            );
            g.items[ii].types_restricted = g.items[ii].machine_types.length > 0;
        } else g.items[ii][field] = el.value;
        changed();
        if (["type", "group-disabled", "machine_types"].includes(field))
            render();
    });
    root.addEventListener("change", (e) => {
        if (
            ["group-title", "name", "diagnosis_section_title"].includes(
                e.target.dataset.field,
            )
        )
            render();
    });
    root.addEventListener("click", (e) => {
        const el = e.target.closest("[data-action]");
        if (!el || el.disabled || busy) return;
        const action = el.dataset.action,
            gi = Number(el.dataset.group ?? selected),
            ii = Number(el.dataset.item),
            fi = Number(el.dataset.fieldIndex);
        if (action === "select") {
            selected = gi;
            render();
            return;
        }
        const edits = [
            "add-group",
            "add-item",
            "add-field",
            "toggle-item",
            "toggle-field",
            "group-up",
            "group-down",
            "item-up",
            "item-down",
            "field-up",
            "field-down",
        ];
        if (edits.includes(action)) {
            if (!editable()) return;
            const g = current.groups[selected];
            if (action === "add-group") {
                current.groups.push({
                    group_key: null,
                    title: "New group",
                    is_active: true,
                    items: [],
                });
                selected = current.groups.length - 1;
            }
            if (action === "add-item")
                g.items.push({
                    item_key: null,
                    name: "New inspection item",
                    standard: "",
                    machine_types: [],
                    types_restricted: false,
                    is_active: true,
                    fields: [],
                });
            if (action === "add-field")
                g.items[ii].fields.push({
                    field_key: null,
                    label: "New field",
                    type: "number",
                    units: [],
                    options: [],
                    required_when: null,
                    min_value: null,
                    max_value: null,
                    is_multiple: false,
                    is_active: true,
                });
            if (action === "toggle-item")
                g.items[ii].is_active = !g.items[ii].is_active;
            if (action === "toggle-field")
                g.items[ii].fields[fi].is_active =
                    !g.items[ii].fields[fi].is_active;
            if (action.endsWith("-up") || action.endsWith("-down")) {
                const list = action.startsWith("group-")
                        ? current.groups
                        : action.startsWith("item-")
                          ? g.items
                          : g.items[ii].fields,
                    index = action.startsWith("group-")
                        ? gi
                        : action.startsWith("item-")
                          ? ii
                          : fi,
                    to = index + (action.endsWith("-up") ? -1 : 1);
                if (to < 0 || to >= list.length) return;
                const [entry] = list.splice(index, 1);
                list.splice(to, 0, entry);
                if (action.startsWith("group-")) selected = to;
            }
            changed();
            render();
            return;
        }
        run(async () => {
            if (action === "save") {
                await save();
                message("Draft saved.");
            }
            if (action === "versions") {
                if (!(await discard())) return;
                await loadIndex();
                listing = true;
                dirty = false;
                message("");
            }
            if (action === "create") {
                if (!(await discard())) return;
                setCurrent((await request("/draft", "POST")).data);
                listing = false;
                await loadIndex();
                message(`Draft v${current.version} ready to edit.`);
            }
            if (action === "open" || action === "activate-version") {
                if (!(await discard())) return;
                setCurrent((await request(`/${el.dataset.id}`)).data);
                listing = false;
                message("");
                if (action === "activate-version") await preview();
            }
            if (action === "preview" || action === "activate") await preview();
            if (action === "preview-version")
                await preview(Number(el.dataset.id));
            if (action === "reload") {
                if (!(await discard())) return;
                await loadIndex();
                if (current) setCurrent((await request(`/${current.id}`)).data);
                message("Latest version loaded.");
            }
            if (action === "audit-page")
                await loadIndex(Number(el.dataset.page));
            if (action === "confirm-activate") {
                if (!previewToken || dirty)
                    throw new Error("Preview the latest saved draft first.");
                dialog.close();
                const answer = await Swal.fire({
                    title: `Activate v${current.version}?`,
                    text: "New Service Forms will use this version. Existing documents keep their locked template version.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Activate version",
                    confirmButtonColor: "#115640",
                });
                if (!answer.isConfirmed) return;
                setCurrent(
                    (
                        await request(`/${current.id}/activate`, "POST", {
                            lock_version: current.lock_version,
                            confirmed: true,
                            preview_token: previewToken,
                        })
                    ).data,
                );
                await loadIndex();
                message(`v${current.version} activated successfully.`);
            }
        });
    });
    root.addEventListener("dragstart", (e) => {
        const row = e.target.closest("[data-order]");
        if (row && editable() && !busy) {
            dragIndex = Number(row.dataset.order);
            e.dataTransfer.setData("text/plain", String(dragIndex));
        }
    });
    root.addEventListener("dragover", (e) => {
        if (editable() && e.target.closest("[data-order]")) e.preventDefault();
    });
    root.addEventListener("drop", (e) => {
        const row = e.target.closest("[data-order]");
        if (!row || !editable() || busy || dragIndex === null) return;
        e.preventDefault();
        const to = Number(row.dataset.order),
            [g] = current.groups.splice(dragIndex, 1);
        current.groups.splice(to, 0, g);
        selected = to;
        dragIndex = null;
        changed();
        render();
    });
    root.addEventListener("dragend", () => {
        dragIndex = null;
    });
    document
        .getElementById("dt-close-preview")
        .addEventListener("click", () => dialog.close());
    window.addEventListener("beforeunload", (e) => {
        if (dirty) {
            e.preventDefault();
            e.returnValue = "";
        }
    });
    run(async () => {
        message("Loading inspection template versions…");
        await loadIndex();
        message("");
    });
})();
