(() => {
    "use strict";
    const root = document.getElementById("document-template-app");
    if (!root || root.dataset.template !== "satisfaction-survey") return;
    const API = "/api/aftersales/survey-templates",
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
    const head = (label, title, buttons = "") =>
        `<div class="dt-card-head"><div><div class="dt-eyebrow">${esc(label)}</div><h2>${esc(title)}</h2></div>${buttons}</div>`;
    const types = {
        rating: "Rating 1–5",
        yes_no: "Yes / No",
        short_text: "Short text",
        long_text: "Long text",
        choice: "Single choice",
    };
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
        listing = false,
        dirty = false,
        busy = false,
        previewToken = null,
        dragIndex = null;
    const editable = () => current?.status === "draft",
        dis = () => (editable() ? "" : "disabled");
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
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(
                Object.values(data.errors || {}).flat()[0] ||
                    data.message ||
                    `Request failed (${response.status}).`,
            );
            error.status = response.status;
            throw error;
        }
        return data;
    }
    async function loadIndex(page = 1) {
        const result = await request(`?page=${page}`);
        versions = result.data.versions;
        audit = result.data.audit;
    }
    function setCurrent(data) {
        current = structuredClone(data);
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
        setCurrent(
            (
                await request(`/${current.id}`, "PUT", {
                    lock_version: current.lock_version,
                    title: current.title,
                    content: current.content,
                })
            ).data,
        );
        message("Draft saved.");
    }
    function render() {
        document.getElementById("dt-document-name").textContent =
            "Satisfaction Survey";
        document.getElementById("dt-title").textContent = listing
            ? "Template versions"
            : `${editable() ? "Edit" : "View"} Satisfaction Survey`;
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
            ? `${btn("Rating results", "scores")}${create}`
            : `${btn("Version history", "versions")}${editable() ? btn("Save draft", "save") : ""}${current ? btn("◉ Preview", "preview") : ""}${editable() ? btn(`✓ Activate v${current.version}`, "activate", "", "dt-primary") : create}`;
        if (listing) renderVersions();
        else if (current) renderEditor();
        else
            content.innerHTML =
                '<div class="dt-card dt-card-body">Survey template could not be loaded.</div>';
    }
    function renderVersions() {
        content.innerHTML = `<div class="dt-card">${head("Version history", "Satisfaction Survey")}<div class="dt-table-wrap"><table class="dt-table"><thead><tr><th>Version</th><th>Status</th><th>Modified By</th><th>Effective Since</th><th>Actions</th></tr></thead><tbody>${versions.map((v) => `<tr><td><strong>v${v.version}</strong></td><td><span class="dt-badge dt-${esc(v.status)}">${esc(v.status)}</span></td><td>${esc(v.modified_by)}</td><td>${date(v.activated_at)}</td><td><div class="dt-actions">${btn(v.status === "draft" ? "Edit" : "View", "open", `data-id="${v.id}"`, v.status === "draft" ? "dt-primary" : "")}${btn("Preview", "preview-version", `data-id="${v.id}"`)}${v.status === "draft" ? btn("Activate", "activate-version", `data-id="${v.id}"`, "dt-soft") : ""}</div></td></tr>`).join("")}</tbody></table></div></div><div class="dt-card dt-card-body"><h2>Version rules</h2><div class="dt-rules">${["Only one version can be active at a time.", "Active versions cannot be edited directly.", "Create a draft from the active version before making changes.", "Existing survey links and responses retain their template version."].map((x) => `<p>${x}</p>`).join("")}</div></div><div class="dt-card">${head("Activity history", "Audit Log", `<span class="dt-badge">${audit?.total || 0} activities</span>`)}<div class="dt-audit"><table class="dt-table"><thead><tr><th>Time</th><th>Modified By</th><th>Activity</th><th>Version</th></tr></thead><tbody>${(audit?.data || []).map((x) => `<tr><td>${date(x.created_at)}</td><td>${esc(x.actor_name)}</td><td>${esc({ template_draft_created: "Created draft from active version", template_draft_saved: "Saved survey changes", template_activated: "Activated template version" }[x.action] || x.action)}</td><td>v${x.version}</td></tr>`).join("") || '<tr><td colspan="4">No activity yet.</td></tr>'}</tbody></table></div>${audit?.last_page > 1 ? `<div class="dt-card-body dt-actions">${btn("Previous", "audit-page", `data-page="${audit.current_page - 1}" ${audit.current_page === 1 ? "disabled" : ""}`)}<span>Page ${audit.current_page} of ${audit.last_page}</span>${btn("Next", "audit-page", `data-page="${audit.current_page + 1}" ${audit.current_page === audit.last_page ? "disabled" : ""}`)}</div>` : ""}</div>`;
    }
    function renderEditor() {
        const sections = current.content.sections;
        content.innerHTML = `${!editable() ? '<div class="dt-banner">This version is read-only. Create a draft from the active version to make changes.</div>' : ""}<div class="dt-banner"><strong>System-managed sections</strong> &nbsp; Job Information, OTP verification, customer name, position, and signature are locked. The core machine condition question stays required and active.</div><div class="dt-card">${head("Section A", "Rating scale labels")}<div class="dt-card-body dt-scale">${current.content.scale_labels.map((x, i) => `<label><span class="dt-field">Scale ${i + 1}</span><input type="text" data-scale="${i}" value="${esc(x)}" ${dis()}></label>`).join("")}</div></div>${sections
            .map((s, si) => {
                const core = s.questions.some((q) => q.is_core),
                    attrs = `data-section="${si}"`;
                return `<div class="dt-card ${!s.is_active ? "dt-disabled" : ""}" draggable="${editable()}" data-order="${si}">${head(`Section ${code(si + 1)}`, s.title, `<div class="dt-actions">${btn("↑", "section-up", `${attrs} ${dis()} ${si === 0 ? "disabled" : ""}`, "dt-muted")}${btn("↓", "section-down", `${attrs} ${dis()} ${si === sections.length - 1 ? "disabled" : ""}`, "dt-muted")}${btn(s.is_active ? "Disable section" : "↶ Restore section", "toggle-section", `${attrs} ${dis()} ${core ? "disabled" : ""}`, "dt-muted")}${btn("＋ Add question", "add-question", `${attrs} ${dis()}`)}</div>`)}<div class="dt-card-body"><label><span class="dt-field">Section title</span><input type="text" data-field="section-title" ${attrs} value="${esc(s.title)}" ${dis()}></label></div>${s.questions
                    .map((q, qi) => {
                        const qa = `${attrs} data-question="${qi}"`,
                            blocked =
                                dis() ||
                                (!q.is_active || !s.is_active
                                    ? "disabled"
                                    : "");
                        return `<div class="${!q.is_active ? "dt-disabled" : ""}"><div class="dt-question"><label><span class="dt-field">Question text ${q.is_core ? "· Core question" : ""}</span><input type="text" data-field="label" ${qa} value="${esc(q.label)}" ${blocked}></label><label><span class="dt-field">Type</span><select data-field="type" ${qa} ${blocked} ${q.is_core ? "disabled" : ""}>${Object.entries(
                            types,
                        )
                            .map(
                                ([key, label]) =>
                                    `<option value="${key}" ${q.type === key ? "selected" : ""}>${label}</option>`,
                            )
                            .join(
                                "",
                            )}</select></label><label class="dt-check"><input type="checkbox" data-field="required" ${qa} ${q.required ? "checked" : ""} ${blocked} ${q.is_core ? "disabled" : ""}> Required</label><div class="dt-actions">${btn("↑", "question-up", `${qa} ${dis()} ${qi === 0 ? "disabled" : ""}`, "dt-muted")}${btn("↓", "question-down", `${qa} ${dis()} ${qi === s.questions.length - 1 ? "disabled" : ""}`, "dt-muted")}${btn(q.is_active ? "Disable" : "↶ Restore", "toggle-question", `${qa} ${dis()} ${q.is_core ? "disabled" : ""}`, "dt-muted")}</div></div><div class="dt-card-body" style="padding-top:0"><label><span class="dt-field">Instructions (optional)</span><input type="text" data-field="hint" ${qa} value="${esc(q.hint)}" ${blocked}></label>${q.type === "choice" ? `<label style="display:block;margin-top:12px"><span class="dt-field">Choices (one per line)</span><textarea data-field="options" ${qa} ${blocked}>${esc(q.options.join("\n"))}</textarea></label>` : ""}${q.is_core ? '<p class="dt-description" style="margin-top:10px">A No answer requires an explanation and flags the ticket for follow-up.</p>' : ""}</div></div>`;
                    })
                    .join("")}</div>`;
            })
            .join(
                "",
            )}${btn(`＋ Add new section ${code(sections.length + 1)}`, "add-section", dis(), "dt-add-section")}<div class="dt-card" style="margin-top:14px">${head("Document settings", "Survey title")}<div class="dt-card-body"><input type="text" data-field="document-title" aria-label="Survey title" value="${esc(current.title)}" ${dis()}></div></div>`;
    }
    async function preview(id = current.id) {
        if (current?.id === id && dirty) await save();
        const target =
            current?.id === id ? current : (await request(`/${id}`)).data;
        const result = await request(`/${id}/preview`, "POST", {
            lock_version: target.lock_version,
        });
        if (current?.id === id) previewToken = result.preview_token;
        dialog.querySelector("h2").textContent = "Template preview";
        document.getElementById("dt-preview-content").innerHTML =
            `<h2>${esc(result.data.title)} · v${result.data.version}</h2><p class="dt-description" style="margin-top:12px">Job information, verification, and customer identity are supplied by the survey flow.</p>${result.sections.map((s) => `<section class="dt-preview-section"><h2>${esc(s.code)}. ${esc(s.title)}</h2>${s.questions.map((q) => `<p><strong>${esc(q.label)}${q.required ? " *" : ""}</strong>${q.hint ? `<br><small>${esc(q.hint)}</small>` : ""}</p>${q.type === "rating" ? `<div class="dt-scale">${result.data.content.scale_labels.map((label, i) => `<span class="dt-code">${i + 1} · ${esc(label)}</span>`).join("")}</div>` : q.type === "yes_no" ? "<p>○ Yes &nbsp; ○ No</p>" : q.type === "choice" ? `<p>${q.options.map((o) => `○ ${esc(o)}`).join(" &nbsp; ")}</p>` : `<${q.type === "long_text" ? "textarea" : "input"} disabled aria-label="${esc(q.label)}"></${q.type === "long_text" ? "textarea" : "input"}>`}${q.is_core ? '<p class="dt-description">Explanation is required when No is selected.</p>' : ""}`).join("")}</section>`).join("")}${result.data.status === "draft" && current?.id === id ? `<div style="margin-top:20px">${btn(`✓ Activate v${result.data.version}`, "confirm-activate", "", "dt-primary")}</div>` : ""}`;
        dialog.showModal();
    }
    async function discard() {
        return (
            !dirty ||
            (
                await Swal.fire({
                    title: "Discard unsaved changes?",
                    text: "Your unsaved draft changes will be lost.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Discard changes",
                    confirmButtonColor: "#115640",
                })
            ).isConfirmed
        );
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
        if (!editable() || busy) return;
        const el = e.target;
        if (el.dataset.scale !== undefined) {
            current.content.scale_labels[Number(el.dataset.scale)] = el.value;
            changed();
            return;
        }
        if (!el.dataset.field) return;
        const field = el.dataset.field,
            section = current.content.sections[Number(el.dataset.section)];
        if (field === "document-title") current.title = el.value;
        else if (field === "section-title") section.title = el.value;
        else {
            const q = section.questions[Number(el.dataset.question)];
            if (q.is_core && ["type", "required"].includes(field)) return;
            q[field] =
                field === "required"
                    ? el.checked
                    : field === "options"
                      ? el.value
                            .split("\n")
                            .map((x) => x.trim())
                            .filter(Boolean)
                      : el.value;
            if (field === "type") q.options = [];
        }
        changed();
        if (field === "type") render();
    });
    root.addEventListener("change", (e) => {
        if (e.target.dataset.field === "section-title") render();
    });
    root.addEventListener("click", (e) => {
        const el = e.target.closest("[data-action]");
        if (!el || el.disabled || busy) return;
        const action = el.dataset.action,
            si = Number(el.dataset.section),
            qi = Number(el.dataset.question);
        if (
            [
                "add-section",
                "add-question",
                "toggle-section",
                "toggle-question",
                "section-up",
                "section-down",
                "question-up",
                "question-down",
            ].includes(action)
        ) {
            if (!editable()) return;
            const sections = current.content.sections,
                s = sections[si];
            if (action === "add-section")
                sections.push({
                    section_key: null,
                    title: "New section",
                    is_active: true,
                    questions: [],
                });
            if (action === "add-question")
                s.questions.push({
                    question_key: null,
                    label: "New question",
                    hint: "",
                    type: "rating",
                    options: [],
                    required: true,
                    is_active: true,
                    is_core: false,
                });
            if (action === "toggle-section") {
                if (s.questions.some((q) => q.is_core)) return;
                s.is_active = !s.is_active;
            }
            if (action === "toggle-question") {
                if (s.questions[qi].is_core) return;
                s.questions[qi].is_active = !s.questions[qi].is_active;
            }
            if (action.endsWith("-up") || action.endsWith("-down")) {
                const list = action.startsWith("section-")
                        ? sections
                        : s.questions,
                    index = action.startsWith("section-") ? si : qi,
                    to = index + (action.endsWith("-up") ? -1 : 1);
                if (to < 0 || to >= list.length) return;
                const [entry] = list.splice(index, 1);
                list.splice(to, 0, entry);
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
            if (action === "scores") {
                const result = await request("/question-scores");
                dialog.querySelector("h2").textContent = "Rating results";
                document.getElementById("dt-preview-content").innerHTML =
                    `<p class="dt-description">Average scores across customer responses, grouped by question. Admin-assisted responses are excluded.</p><div class="dt-table-wrap"><table class="dt-table"><thead><tr><th>Question</th><th>Responses</th><th>Average</th></tr></thead><tbody>${result.data.map((row) => `<tr><td>${esc(row.label)}</td><td>${row.response_count}</td><td>${row.average_score} / 5</td></tr>`).join("") || '<tr><td colspan="3">No rating responses yet.</td></tr>'}</tbody></table></div>`;
                dialog.showModal();
            }
            if (action === "confirm-activate") {
                if (!previewToken || dirty)
                    throw new Error("Preview the latest saved draft first.");
                dialog.close();
                if (
                    !(
                        await Swal.fire({
                            title: `Activate v${current.version}?`,
                            text: "Newly activated survey links use this version. Existing links and responses keep their locked version.",
                            icon: "question",
                            showCancelButton: true,
                            confirmButtonText: "Activate version",
                            confirmButtonColor: "#115640",
                        })
                    ).isConfirmed
                )
                    return;
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
            [s] = current.content.sections.splice(dragIndex, 1);
        current.content.sections.splice(to, 0, s);
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
        message("Loading survey template…");
        await loadIndex();
        const target =
            versions.find((v) => v.status === "draft") ||
            versions.find((v) => v.status === "active");
        if (target) setCurrent((await request(`/${target.id}`)).data);
        listing = !target;
        message("");
    });
})();
