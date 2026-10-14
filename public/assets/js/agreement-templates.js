(() => {
    "use strict";
    const root = document.getElementById("document-template-app");
    if (!root || root.dataset.template !== "service-agreement") return;
    const api = "/api/aftersales/agreement-templates";
    const content = document.getElementById("dt-content");
    const actions = document.getElementById("dt-actions");
    const dialog = document.getElementById("dt-preview");
    const feedback = document.getElementById("dt-feedback");
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
    const button = (label, action, cls = "", attrs = "") =>
        `<button type="button" class="dt-button ${cls}" data-action="${action}" ${attrs}>${label}</button>`;
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
    const head = (label, title, action = "") =>
        `<div class="dt-card-head"><div><div class="dt-eyebrow">${esc(label)}</div><h2>${esc(title)}</h2></div>${action}</div>`;
    let versions = [],
        audit = null,
        current = null,
        selected = 0,
        listing = false,
        dirty = false,
        busy = false,
        previewToken = null,
        dragIndex = null;
    const editable = () => current?.status === "draft";
    const disabled = () => (editable() ? "" : "disabled");
    function message(text, error = false) {
        feedback.textContent = text;
        feedback.style.color = error ? "#b42318" : "#115640";
    }
    async function request(path = "", method = "GET", body) {
        const response = await fetch(api + path, {
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
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const err = new Error(
                Object.values(data.errors || {}).flat()[0] ||
                    data.message ||
                    `Request failed (${response.status}).`,
            );
            err.status = response.status;
            throw err;
        }
        return data;
    }
    function setCurrent(data) {
        current = structuredClone(data);
        selected = Math.min(selected, Math.max(0, current.sections.length - 1));
        dirty = false;
        previewToken = null;
    }
    async function refreshIndex(page = 1) {
        const result = await request(`?page=${page}`);
        versions = result.data.versions;
        audit = result.data.audit;
    }
    function markDirty() {
        dirty = true;
        previewToken = null;
        feedback.textContent = "Unsaved changes";
    }
    async function save() {
        if (!editable() || !dirty) return;
        const result = await request(`/${current.id}`, "PUT", {
            lock_version: current.lock_version,
            title: current.title,
            closing_statement: current.closing_statement,
            sections: current.sections,
        });
        setCurrent(result.data);
        message("Draft saved.");
    }
    function render() {
        document.getElementById("dt-document-name").textContent =
            "Service Agreement";
        document.getElementById("dt-title").textContent = listing
            ? "Template versions"
            : `${editable() ? "Edit" : "View"} Service Agreement`;
        document.getElementById("dt-version").textContent = listing
            ? `${versions.length} versions`
            : current
              ? `v${current.version}`
              : "—";
        const badge = document.getElementById("dt-status");
        badge.textContent = listing ? "" : current?.status || "";
        badge.hidden = listing;
        badge.className = `dt-badge dt-${current?.status || "inactive"}`;
        actions.innerHTML = listing
            ? button(
                  versions.some((v) => v.status === "draft")
                      ? "Continue editing draft"
                      : "＋ Create new version",
                  "create",
                  "dt-primary",
              )
            : `${button("Version history", "versions")}${editable() ? button("Save draft", "save") : ""}${current ? button("◉ Preview", "preview") : ""}${editable() ? button(`✓ Activate v${current.version}`, "activate", "dt-primary") : button(versions.some((v) => v.status === "draft") ? "Continue editing draft" : "＋ Create new version", "create", "dt-primary")}`;
        if (listing) renderVersions();
        else if (current) renderEditor();
        else
            content.innerHTML =
                '<div class="dt-card dt-card-body">No agreement template available.</div>';
    }
    function renderVersions() {
        const draft = versions.find((v) => v.status === "draft");
        content.innerHTML = `<div class="dt-card">${head("Version history", "Service Agreement")}<div class="dt-table-wrap"><table class="dt-table"><thead><tr><th>Version</th><th>Status</th><th>Modified By</th><th>Effective Since</th><th>Actions</th></tr></thead><tbody>${versions.map((v) => `<tr><td><strong>v${v.version}</strong></td><td><span class="dt-badge dt-${esc(v.status)}">${esc(v.status)}</span></td><td>${esc(v.modified_by)}</td><td>${date(v.activated_at)}</td><td><div class="dt-actions">${button(v.status === "draft" ? "Edit" : "View", "open", v.status === "draft" ? "dt-primary" : "", `data-id="${v.id}"`)}${button("Preview", "preview-version", "", `data-id="${v.id}"`)}</div></td></tr>`).join("")}</tbody></table></div></div><div class="dt-card dt-card-body"><h2>Version rules</h2><div class="dt-rules">${["Only one version can be active at a time.", "Active versions cannot be edited directly.", "Create a draft from the active version before making changes.", "Inactive versions remain available for historical reports."].map((x) => `<p>${x}</p>`).join("")}</div>${draft ? `<p class="dt-description">Draft v${draft.version} is available for editing.</p>` : ""}</div><div class="dt-card">${head("Activity history", "Audit Log", `<span class="dt-badge">${audit?.total || 0} activities</span>`)}<div class="dt-audit"><table class="dt-table"><thead><tr><th>Time</th><th>Modified By</th><th>Activity</th><th>Version</th></tr></thead><tbody>${(audit?.data || []).map((x) => `<tr><td>${date(x.created_at)}</td><td>${esc(x.actor_name)}</td><td>${esc({ template_draft_created: "Created draft from active version", template_draft_saved: "Saved draft changes", template_activated: "Activated template version" }[x.action] || x.action)}</td><td>v${x.version}</td></tr>`).join("") || '<tr><td colspan="4">No activity yet.</td></tr>'}</tbody></table></div>${audit?.last_page > 1 ? `<div class="dt-card-body dt-actions">${button("Previous", "audit-page", "", `data-page="${audit.current_page - 1}" ${audit.current_page === 1 ? "disabled" : ""}`)}<span>Page ${audit.current_page} of ${audit.last_page}</span>${button("Next", "audit-page", "", `data-page="${audit.current_page + 1}" ${audit.current_page === audit.last_page ? "disabled" : ""}`)}</div>` : ""}</div>`;
    }
    function renderEditor() {
        const sections = current.sections,
            s = sections[selected];
        const intro = !editable()
            ? '<div class="dt-banner">This version is read-only. Create a draft from the active version to make changes.</div>'
            : "";
        content.innerHTML =
            intro +
            `<div class="dt-editor-grid"><div><div class="dt-card">${head(s ? `Section ${code(selected)}` : "Sections", s ? `Clauses in ${s.title}` : "No sections", button("＋ Add clause", "add-clause", "", `${disabled()} ${!s ? "disabled" : ""}`))}<div class="dt-card-body"><p class="dt-description">Arrange and edit the clauses that will appear in this section of the agreement.</p>${s ? s.clauses.map((c, i) => `<div class="dt-clause ${!c.is_active || !s.is_active ? "dt-disabled" : ""}"><div class="dt-clause-head"><span class="dt-code">${code(selected)}.${i + 1}</span><strong>Agreement clause</strong><div class="dt-actions" style="margin-left:auto">${button("↑", "clause-up", "dt-muted", `data-item="${i}" aria-label="Move clause up" ${disabled()} ${i === 0 ? "disabled" : ""}`)}${button("↓", "clause-down", "dt-muted", `data-item="${i}" aria-label="Move clause down" ${disabled()} ${i === s.clauses.length - 1 ? "disabled" : ""}`)}${button(c.is_active ? "Disable in this version" : "↶ Restore", "toggle-clause", "dt-muted", `data-item="${i}" ${disabled()}`)}</div></div><div class="dt-card-body"><label class="dt-field" for="ag-clause-${i}">Clause text</label><textarea id="ag-clause-${i}" data-field="text" data-item="${i}" ${disabled()} ${!c.is_active || !s.is_active ? "disabled" : ""}>${esc(c.text)}</textarea></div></div>`).join("") : ""}${button("＋ Add clause", "add-clause", "dt-add-section", `${disabled()} ${!s ? "disabled" : ""}`)}</div></div><div class="dt-card">${head("Document settings", "Agreement content")}<div class="dt-card-body"><label class="dt-field" for="ag-title">Document title</label><input id="ag-title" type="text" data-field="document-title" value="${esc(current.title)}" ${disabled()}><label class="dt-field" style="margin-top:14px" for="ag-closing">Closing statement</label><textarea id="ag-closing" data-field="closing" ${disabled()}>${esc(current.closing_statement)}</textarea></div></div></div><aside><div class="dt-card">${head("Section settings", "Document structure")}<div class="dt-card-body"><div class="dt-eyebrow">Document sections</div><div class="dt-actions" style="justify-content:space-between;align-items:center"><strong>Manage section order</strong>${button("＋ Add", "add-section", "", disabled())}</div><p class="dt-description" style="margin-top:15px">Drag sections or use the arrows to change their order.</p>${sections.map((x, i) => `<div class="dt-order ${selected === i ? "selected" : ""}" draggable="${editable()}" data-order="${i}"><span aria-hidden="true">⠿</span><strong>${code(i)}</strong><button type="button" data-action="select" data-section="${i}" style="flex:1;text-align:left;font-size:11px">${esc(x.title || "Untitled section")}${!x.is_active ? " (disabled)" : ""}</button><button type="button" data-action="section-up" data-section="${i}" aria-label="Move section up" ${disabled()} ${i === 0 ? "disabled" : ""}>↑</button><button type="button" data-action="section-down" data-section="${i}" aria-label="Move section down" ${disabled()} ${i === sections.length - 1 ? "disabled" : ""}>↓</button></div>`).join("")}</div>${s ? `<div class="dt-settings"><div class="dt-eyebrow">Clauses settings</div><h2>Selected section</h2><label class="dt-field" style="margin-top:28px" for="ag-section-title">Section title</label><input id="ag-section-title" type="text" data-field="section-title" value="${esc(s.title)}" ${disabled()}><label class="dt-check"><input type="checkbox" data-field="section-disabled" ${!s.is_active ? "checked" : ""} ${disabled()}> Disable this section</label></div>` : ""}</div></aside></div>`;
    }
    async function preview(id = current.id) {
        if (current?.id === id && dirty) await save();
        const target =
            current?.id === id ? current : (await request(`/${id}`)).data;
        const result = await request(`/${id}/preview`, "POST", {
            lock_version: target.lock_version,
        });
        if (current?.id === id) previewToken = result.preview_token;
        const box = document.getElementById("dt-preview-content");
        box.innerHTML = `<h2>${esc(result.data.title)} · v${result.data.version}</h2>${result.sections.map((s) => `<section class="dt-preview-section"><h2>${esc(s.code)}. ${esc(s.title)}</h2>${s.clauses.map((c) => `<p>${esc(c.text)}</p>`).join("")}</section>`).join("")}<p style="margin-top:18px">${esc(result.data.closing_statement)}</p>${result.data.status === "draft" && current?.id === id ? `<div class="dt-actions" style="margin-top:20px">${button(`✓ Activate v${result.data.version}`, "confirm-activate", "dt-primary")}</div>` : ""}`;
        dialog.showModal();
        render();
    }
    async function confirmDiscard() {
        if (!dirty) return true;
        const result = await Swal.fire({
            title: "Discard unsaved changes?",
            text: "Your unsaved draft changes will be lost.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Discard changes",
            confirmButtonColor: "#115640",
        });
        return result.isConfirmed;
    }
    async function run(work) {
        if (busy) return;
        busy = true;
        root.setAttribute("aria-busy", "true");
        root.querySelectorAll("button,input,textarea").forEach(
            (el) => (el.disabled = true),
        );
        try {
            await work();
        } catch (err) {
            message(err.message, true);
            if (err.status === 409) {
                previewToken = null;
                feedback.append(document.createTextNode(" "));
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
            s = current.sections[selected],
            field = el.dataset.field;
        if (field === "text")
            s.clauses[Number(el.dataset.item)].text = el.value;
        if (field === "document-title") current.title = el.value;
        if (field === "closing") current.closing_statement = el.value;
        if (field === "section-title") s.title = el.value;
        if (field === "section-disabled") {
            s.is_active = !el.checked;
            render();
        }
        markDirty();
    });
    root.addEventListener("change", (e) => {
        if (e.target.dataset.field === "section-title") render();
    });
    root.addEventListener("click", (e) => {
        const el = e.target.closest("[data-action]");
        if (!el || el.disabled || busy) return;
        const action = el.dataset.action,
            index = Number(el.dataset.section ?? selected),
            item = Number(el.dataset.item);
        if (action === "select") {
            selected = index;
            render();
            return;
        }
        if (
            [
                "add-section",
                "add-clause",
                "toggle-clause",
                "section-up",
                "section-down",
                "clause-up",
                "clause-down",
            ].includes(action)
        ) {
            if (!editable()) return;
            if (action === "add-section") {
                current.sections.push({
                    section_key: null,
                    title: "New section",
                    is_active: true,
                    clauses: [],
                });
                selected = current.sections.length - 1;
            }
            if (action === "add-clause")
                current.sections[selected].clauses.push({
                    key: null,
                    text: "",
                    is_active: true,
                });
            if (action === "toggle-clause") {
                const c = current.sections[selected].clauses[item];
                c.is_active = !c.is_active;
            }
            if (action.startsWith("section-")) {
                const to = index + (action.endsWith("up") ? -1 : 1);
                if (to < 0 || to >= current.sections.length) return;
                const [s] = current.sections.splice(index, 1);
                current.sections.splice(to, 0, s);
                selected = to;
            }
            if (action.startsWith("clause-")) {
                const clauses = current.sections[selected].clauses,
                    to = item + (action.endsWith("up") ? -1 : 1);
                if (to < 0 || to >= clauses.length) return;
                const [c] = clauses.splice(item, 1);
                clauses.splice(to, 0, c);
            }
            markDirty();
            render();
            return;
        }
        run(async () => {
            if (action === "save") {
                await save();
                if (!dirty) message("Draft saved.");
            }
            if (action === "versions") {
                if (!(await confirmDiscard())) return;
                await refreshIndex();
                listing = true;
                dirty = false;
                message("");
            }
            if (action === "create") {
                if (!(await confirmDiscard())) return;
                const result = await request("/draft", "POST");
                setCurrent(result.data);
                listing = false;
                await refreshIndex();
                message(`Draft v${current.version} ready to edit.`);
            }
            if (action === "open") {
                if (!(await confirmDiscard())) return;
                setCurrent((await request(`/${el.dataset.id}`)).data);
                listing = false;
                message("");
            }
            if (action === "reload") {
                if (!(await confirmDiscard())) return;
                await refreshIndex();
                if (current) setCurrent((await request(`/${current.id}`)).data);
                message("Latest draft loaded.");
            }
            if (action === "audit-page")
                await refreshIndex(Number(el.dataset.page));
            if (action === "preview" || action === "activate") await preview();
            if (action === "preview-version")
                await preview(Number(el.dataset.id));
            if (action === "confirm-activate") {
                if (!previewToken || dirty)
                    throw new Error(
                        "Preview the latest saved draft before activation.",
                    );
                dialog.close();
                const answer = await Swal.fire({
                    title: `Activate v${current.version}?`,
                    text: "The previous active version becomes inactive. New agreements use this version; existing documents keep their locked version.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Activate version",
                    confirmButtonColor: "#115640",
                });
                if (!answer.isConfirmed) return;
                const result = await request(
                    `/${current.id}/activate`,
                    "POST",
                    {
                        lock_version: current.lock_version,
                        confirmed: true,
                        preview_token: previewToken,
                    },
                );
                setCurrent(result.data);
                await refreshIndex();
                message(`v${current.version} activated successfully.`);
            }
        });
    });
    root.addEventListener("dragstart", (e) => {
        const row = e.target.closest("[data-order]");
        if (!row || !editable() || busy) return;
        dragIndex = Number(row.dataset.order);
        e.dataTransfer.setData("text/plain", String(dragIndex));
    });
    root.addEventListener("dragover", (e) => {
        if (editable() && e.target.closest("[data-order]")) e.preventDefault();
    });
    root.addEventListener("drop", (e) => {
        const row = e.target.closest("[data-order]");
        if (!row || !editable() || busy || dragIndex === null) return;
        e.preventDefault();
        const to = Number(row.dataset.order),
            [s] = current.sections.splice(dragIndex, 1);
        current.sections.splice(to, 0, s);
        selected = to;
        dragIndex = null;
        markDirty();
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
        message("Loading Service Agreement…");
        await refreshIndex();
        const target =
            versions.find((v) => v.status === "draft") ||
            versions.find((v) => v.status === "active");
        if (target) setCurrent(target);
        listing = !target;
        message("");
    });
})();
