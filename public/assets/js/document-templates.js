(() => {
    "use strict";
    const root = document.getElementById("document-template-app");
    if (!root) return;
    const kind = root.dataset.template;
    const inspection = kind === "service-inspection-report";
    const survey = kind === "satisfaction-survey";
    const name = inspection
        ? "Service Inspection & Report"
        : survey
          ? "Satisfaction Survey"
          : "Service Agreement";
    const content = document.getElementById("dt-content");
    const actions = document.getElementById("dt-actions");
    const esc = (value) =>
        String(value).replace(
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
    const button = (label, action, cls = "", extra = "") =>
        `<button type="button" class="dt-button ${cls}" data-action="${action}" ${extra}>${label}</button>`;
    let editor = !inspection;
    let selected = 0;
    let version = 3;
    let status = "Draft";
    const versions = [
        {
            number: 3,
            status: "Draft",

            user: "Novia Rajmi",
            date: "12 Jun 2025, 14:32",
        },
        {
            number: 2,
            status: "Active",
            user: "Ahmad Fauzi",
            date: "04 Apr 2025, 09:18",
        },
        {
            number: 1,
            status: "Inactive",
            user: "Dian Pratama",
            date: "18 Jan 2025, 16:04",
        },
    ];
    const logs = [
        {
            time: "12 Jun 2025, 14:32",
            user: "Novia Rajmi",
            activity: "Updated inspection fields",
            version: 3,
        },
        {
            time: "12 Jun 2025, 11:08",
            user: "Novia Rajmi",
            activity: "Added Electrical group item",
            version: 3,
        },
        {
            time: "11 Jun 2025, 16:45",
            user: "Ahmad Fauzi",
            activity: "Created draft from active version",
            version: 3,
        },
        {
            time: "04 Apr 2025, 09:18",
            user: "Ahmad Fauzi",
            activity: "Activated template version",
            version: 2,
        },
    ];
    const sections = survey
        ? [
              {
                  title: "Ratings",
                  items: [
                      {
                          text: "Technician’s professionalism and communication",
                          type: "Rating 1–5",
                          required: true,
                      },
                      {
                          text: "Quality and completeness of service",
                          type: "Rating 1–5",
                          required: true,
                      },
                      {
                          text: "Response time and punctuality",
                          type: "Rating 1–5",
                          required: true,
                          disabled: true,
                      },
                  ],
              },
              {
                  title: "Machine condition",
                  items: [
                      {
                          text: "Is the machine working properly after service?",
                          type: "Yes / No",
                          required: true,
                      },
                  ],
              },
              {
                  title: "Comments & Suggestions",
                  items: [
                      {
                          text: "Please share any comments or suggestions.",
                          type: "Text",
                          required: false,
                      },
                  ],
              },
          ]
        : inspection
          ? [
                {
                    title: "Mechanical",
                    items: [
                        { text: "Check machine frame and panels" },
                        { text: "Check compressor condition" },
                    ],
                },
                {
                    title: "Electrical",
                    items: [
                        { text: "Check electrical connections" },
                        { text: "Check power supply" },
                    ],
                },
                {
                    title: "Refrigeration",
                    items: [{ text: "Check refrigerant pressure" }],
                },
            ]
          : [
                {
                    title: "General Terms",
                    items: [
                        {
                            text: "The service provider will perform maintenance according to the agreed scope and schedule.",
                        },
                        {
                            text: "All replacement parts require customer approval before installation.",
                        },
                        {
                            text: "The customer must provide safe and unrestricted access to the machine.",
                            disabled: true,
                        },
                    ],
                },
                {
                    title: "Service Scope",
                    items: [
                        {
                            text: "Service includes inspection, diagnosis, and agreed maintenance.",
                        },
                    ],
                },
                {
                    title: "Customer Responsibilities",
                    items: [
                        {
                            text: "The customer will provide access to the machine and relevant service information.",
                        },
                    ],
                },
                {
                    title: "Payment & Warranty",
                    items: [
                        {
                            text: "Payment and warranty follow the approved service quotation.",
                        },
                    ],
                },
            ];
    const scales = ["Very Poor", "Poor", "Fair", "Good", "Very Good"];
    const letter = (index) => String.fromCharCode(65 + index);
    const head = (eyebrow, title, action = "") =>
        `<div class="dt-card-head"><div><div class="dt-eyebrow">${eyebrow}</div><h2>${esc(title)}</h2></div>${action}</div>`;
    function render() {
        document.getElementById("dt-document-name").textContent = name;
        document.getElementById("dt-title").textContent = editor
            ? `Edit ${name}`
            : "Template versions";
        document.getElementById("dt-version").textContent = editor
            ? `v${version}`
            : `${versions.length} versions`;
        document.getElementById("dt-status").textContent = status;
        document.getElementById("dt-status").className =
            `dt-badge dt-${status.toLowerCase()}`;
        actions.innerHTML = editor
            ? `${inspection ? button("← Versions", "versions") : ""}${status === "Draft" ? button("Save draft", "save") : ""}${button("◉ Preview", "preview")}${status === "Draft" ? button(`✓ Activate v${version}`, "activate", "dt-primary") : button("＋ Create new version", "create", "dt-primary")}`
            : button("＋ Create new version", "create", "dt-primary");
        if (!editor) return renderVersions();
        if (survey) renderSurvey();
        else renderClauses();
        if (status !== "Draft")
            content
                .querySelectorAll("input,textarea,select,button")
                .forEach((el) => (el.disabled = true));
    }
    function renderVersions() {
        content.innerHTML = `<div class="dt-card">${head("Version history", name)}<div class="dt-table-wrap"><table class="dt-table"><thead><tr>${["Version", "Status", "Modified By", "Effective Since", "Actions"].map((x) => `<th>${x}</th>`).join("")}</tr></thead><tbody>${versions.map((v) => `<tr><td><strong style="color:#115640">v${v.number}</strong></td><td><span class="dt-badge dt-${v.status.toLowerCase()}">${v.status}</span></td><td>${esc(v.user)}</td><td>${v.date}</td><td><div class="dt-actions">${v.status === "Draft" ? button("Edit", "edit", "dt-primary", `data-version="${v.number}"`) : ""}${button("Preview", "preview", "", `data-version="${v.number}"`)}${v.status === "Draft" ? button("Activate", "activate", "dt-soft", `data-version="${v.number}"`) : ""}</div></td></tr>`).join("")}</tbody></table></div></div><div class="dt-card dt-card-body"><h2>Version rules</h2><div class="dt-rules">${["Only one version can be active at a time.", "Active versions cannot be edited directly.", "Create a draft from the active version before making changes.", "Inactive versions remain available for historical reports."].map((x) => `<p>${x}</p>`).join("")}</div></div><div class="dt-card">${head("Activity history", "Audit Log", `<span class="dt-badge">${logs.length} activities</span>`)}<div class="dt-audit"><table class="dt-table"><thead><tr><th>Time</th><th>Modified By</th><th>Activity</th><th>Version</th></tr></thead><tbody>${logs.map((x) => `<tr><td>${esc(x.time)}</td><td><strong>${esc(x.user)}</strong></td><td>${esc(x.activity)}</td><td>v${x.version}</td></tr>`).join("")}</tbody></table></div></div>`;
    }
    function renderClauses() {
        const s = sections[selected];
        content.innerHTML = `<div class="dt-editor-grid"><div class="dt-card">${head(`Section ${letter(selected)}`, inspection ? `Inspection items in ${s.title}` : `Clauses in ${s.title}`, button(inspection ? "＋ Add item" : "＋ Add clause", "add-item"))}<div class="dt-card-body"><p class="dt-description">Arrange and edit the ${inspection ? "inspection items" : "clauses"} that will appear in this section of the ${inspection ? "report" : "agreement"}.</p>${s.items.map((item, i) => `<div class="dt-clause ${item.disabled || s.disabled ? "dt-disabled" : ""}"><div class="dt-clause-head"><span class="dt-code">${letter(selected)}.${i + 1}</span><strong>${inspection ? "Inspection item" : "Agreement clause"}</strong>${button(item.disabled ? "↶ Restore" : "Disable in this version", "toggle-item", "dt-muted", `data-section="${selected}" data-item="${i}"`)}</div><div class="dt-card-body"><label class="dt-field" for="dt-clause-${i}">${inspection ? "Item" : "Clause"} text</label><textarea id="dt-clause-${i}" data-field="text" data-section="${selected}" data-item="${i}" ${item.disabled || s.disabled ? "disabled" : ""}>${esc(item.text)}</textarea></div></div>`).join("")}${button(inspection ? "＋ Add item" : "＋ Add clause", "add-item", "dt-add-section")}</div></div><aside><div class="dt-card">${head("Section settings", "Document structure")}<div class="dt-card-body"><div class="dt-eyebrow">Document sections</div><div class="dt-actions" style="justify-content:space-between;align-items:center"><strong>Manage section order</strong>${button("＋ Add", "add-section")}</div><p class="dt-description" style="margin-top:15px">Drag sections to change their order in the final document.</p>${sections.map((x, i) => `<div class="dt-order ${selected === i ? "selected" : ""}" draggable="true" data-order="${i}"><span aria-hidden="true">⠿</span><strong>${letter(i)}</strong><small data-action="select" data-section="${i}">${esc(x.title)}</small><button type="button" data-action="up" data-section="${i}" aria-label="Move ${esc(x.title)} up" ${i === 0 ? "disabled" : ""}>↑</button><button type="button" data-action="down" data-section="${i}" aria-label="Move ${esc(x.title)} down" ${i === sections.length - 1 ? "disabled" : ""}>↓</button></div>`).join("")}</div><div class="dt-settings"><div class="dt-eyebrow">Clauses settings</div><h2>Selected section</h2><label class="dt-field" style="margin-top:28px" for="dt-section-title">Section title</label><input id="dt-section-title" type="text" data-field="title" data-section="${selected}" value="${esc(s.title)}"><label class="dt-check"><input type="checkbox" data-field="section-disabled" data-section="${selected}" ${s.disabled ? "checked" : ""}> Disable this section</label></div></div></aside></div>`;
    }
    function renderSurvey() {
        content.innerHTML = `<div class="dt-banner"><strong>System-managed sections</strong> &nbsp; Job Information, OTP verification, customer name, position, and signature are locked and cannot be edited.</div><div class="dt-card">${head("Section A", "Rating scale labels")}<div class="dt-card-body dt-scale">${scales.map((x, i) => `<label><span class="dt-field">Scale ${i + 1}</span><input type="text" data-scale="${i}" value="${esc(x)}"></label>`).join("")}</div></div>${sections.map((s, si) => `<div class="dt-card">${head(`Section ${letter(si + 1)}`, s.title, button("＋ Add question", "add-item", "", `data-section="${si}"`))}${s.items.map((x, i) => `<div class="dt-question ${x.disabled ? "dt-disabled" : ""}"><label><span class="dt-field">Question text</span><input type="text" value="${esc(x.text)}" data-field="text" data-section="${si}" data-item="${i}" ${x.disabled ? "disabled" : ""}></label><label><span class="dt-field">Type</span><select data-field="type" data-section="${si}" data-item="${i}" ${x.disabled ? "disabled" : ""}>${["Rating 1–5", "Yes / No", "Text"].map((t) => `<option ${t === x.type ? "selected" : ""}>${t}</option>`).join("")}</select></label><label class="dt-check"><input type="checkbox" data-field="required" data-section="${si}" data-item="${i}" ${x.required ? "checked" : ""} ${x.disabled ? "disabled" : ""}>Required</label>${button(x.disabled ? "↶ Restore" : "Disable", "toggle-item", "dt-muted", `data-section="${si}" data-item="${i}"`)}</div>`).join("")}</div>`).join("")}${button(`＋ Add new section ${letter(sections.length + 1)}`, "add-section", "dt-add-section")}`;
    }
    function feedback(message) {
        document.getElementById("dt-feedback").textContent = message;
    }
    function preview(number = version) {
        const target = document.getElementById("dt-preview-content");
        target.replaceChildren();
        const title = document.createElement("h2");
        title.textContent = `${name} · v${number}`;
        target.append(title);
        const note = document.createElement("p");
        note.textContent =
            "Frontend preview using sample content. Historical version content will be supplied during backend integration.";
        target.append(note);
        if (survey) {
            const labels = document.createElement("p");
            labels.textContent = scales
                .map((x, i) => `${i + 1}: ${x}`)
                .join(" · ");
            target.append(labels);
        }
        sections
            .filter((s) => !s.disabled)
            .forEach((s) => {
                const block = document.createElement("section");
                block.className = "dt-preview-section";
                const h = document.createElement("h2");
                h.textContent = s.title;
                block.append(h);
                s.items
                    .filter((x) => !x.disabled)
                    .forEach((x) => {
                        const p = document.createElement("p");
                        p.textContent =
                            x.text +
                            (survey
                                ? ` (${x.type}${x.required ? ", required" : ""})`
                                : "");
                        block.append(p);
                    });
                target.append(block);
            });
        document.getElementById("dt-preview").showModal();
    }
    root.addEventListener("input", (event) => {
        const el = event.target;
        if (el.dataset.scale !== undefined)
            scales[Number(el.dataset.scale)] = el.value;
        if (!el.dataset.field) return;
        const s = sections[Number(el.dataset.section)];
        const field = el.dataset.field;
        if (field === "title") s.title = el.value;
        else if (field === "section-disabled") {
            s.disabled = el.checked;
            render();
        } else
            s.items[Number(el.dataset.item)][field] =
                field === "required" ? el.checked : el.value;
    });
    root.addEventListener("change", (event) => {
        if (event.target.dataset.field === "title") render();
    });
    root.addEventListener("click", (event) => {
        const el = event.target.closest("[data-action]");
        if (!el || el.disabled) return;
        const action = el.dataset.action;
        const si = Number(el.dataset.section ?? selected);
        if (action === "preview")
            return preview(Number(el.dataset.version ?? version));
        if (action === "versions") editor = false;
        if (action === "edit") {
            editor = true;
            version = Number(el.dataset.version);
            status = "Draft";
        }
        if (action === "select") selected = si;
        if (action === "toggle-item") {
            const item = sections[si].items[Number(el.dataset.item)];
            item.disabled = !item.disabled;
        }
        if (action === "add-item")
            sections[si].items.push({
                text: "",
                type: survey ? "Rating 1–5" : undefined,
                required: survey,
            });
        if (action === "add-section") {
            sections.push({ title: "New section", items: [] });
            selected = sections.length - 1;
        }
        if (action === "up" || action === "down") {
            const to = si + (action === "up" ? -1 : 1);
            if (to >= 0 && to < sections.length) {
                const [s] = sections.splice(si, 1);
                sections.splice(to, 0, s);
                selected = to;
            }
        }
        if (action === "save") {
            feedback(
                "Draft updated in this frontend demo. Database saving will be connected in the integration stage.",
            );
            return;
        }
        if (action === "activate") {
            version = Number(el.dataset.version ?? version);
            versions.forEach((v) => {
                v.status =
                    v.number === version
                        ? "Active"
                        : v.status === "Active"
                          ? "Inactive"
                          : v.status;
            });
            status = "Active";
            feedback(`v${version} is active in this frontend demo.`);
        }
        if (action === "create") {
            const draft = versions.find((v) => v.status === "Draft");
            if (draft) {
                version = draft.number;
            } else {
                version = Math.max(...versions.map((v) => v.number)) + 1;
                versions.unshift({
                    number: version,
                    status: "Draft",
                    user: "Novia Rajmi",
                    date: "Frontend demo",
                });
            }
            editor = true;
            status = "Draft";
            feedback("Draft opened using sample content.");
        }
        render();
    });
    let dragIndex = null;
    root.addEventListener("dragstart", (e) => {
        const row = e.target.closest("[data-order]");
        if (row) {
            dragIndex = Number(row.dataset.order);
            e.dataTransfer.setData("text/plain", String(dragIndex));
        }
    });
    root.addEventListener("dragover", (e) => {
        if (e.target.closest("[data-order]")) e.preventDefault();
    });
    root.addEventListener("drop", (e) => {
        const row = e.target.closest("[data-order]");
        if (!row || dragIndex === null || status !== "Draft") return;
        e.preventDefault();
        const to = Number(row.dataset.order);
        const [s] = sections.splice(dragIndex, 1);
        sections.splice(to, 0, s);
        selected = to;
        dragIndex = null;
        render();
    });
    document
        .getElementById("dt-close-preview")
        .addEventListener("click", () =>
            document.getElementById("dt-preview").close(),
        );
    render();
})();
