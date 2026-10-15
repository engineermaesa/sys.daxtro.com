(() => {
    "use strict";
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
    let state = null,
        ticketId = null,
        readOnly = true,
        callbacks = {},
        drafts = {},
        pending = {},
        busy = false;
    const blank = () => ({ id: null, name: "", body: "", visit_notes: "" });
    const collect = () =>
        Object.entries(drafts)
            .filter(([, d]) => d.id || d.name || d.body || d.visit_notes)
            .map(([field, d]) => ({ field, ...d }));
    function mount(options) {
        if (ticketId !== options.ticketId) {
            drafts = {};
            pending = {};
            Object.keys(slotCaptions).forEach(key => delete slotCaptions[key]);
        }
        ticketId = options.ticketId;
        state = options.data;
        readOnly = options.readOnly;
        callbacks = options;
        if (!state) return;
        state.fields.forEach((field) => {
            const entry = field.entries.find((e) => !e.locked),
                server = entry
                    ? {
                          id: entry.id,
                          name: entry.name || "",
                          body: entry.body || "",
                          visit_notes: entry.visit_notes || "",
                      }
                    : blank();
            if (!drafts[field.key] || readOnly) drafts[field.key] = server;
            else if (entry) drafts[field.key].id = entry.id;
        });
        render();
    }
    const slotCaptions = {};
    const canContinue = () => !!state && state.fields.every(f => (drafts[f.key]?.body.trim() || f.entries.some(e => e.body?.trim())) && f.photos.length >= 1 && f.photos.length <= 5);
    function render() {
        const target = document.getElementById('sfDiagnosis');
        if (!target || !state) return;
        const total = state.fields.reduce((n, f) => n + f.photos.length, 0);
        const ready = canContinue();
        target.innerHTML = `<div class="sd-card"><div class="sd-head"><h2>4. ${esc(state.title)}</h2><span>${total}/15 photos</span></div>${readOnly?`<div class="sd-readonly" role="status">${esc(callbacks.readOnlyReason || "Only the assigned technician can enter diagnosis and attach photos while the Service Form is Draft.")}</div>`:""}<div class="sd-intro"><span>Complete the service findings and attach supporting evidence before continuing to signatures.${state.legacy ? ' This historical saved form predates diagnosis capture.' : ''}</span><div class="sd-overall"><strong>${total}/15 photos</strong><progress max="15" value="${total}"></progress></div></div><div class="sd-meta"><span><small>SERVICE FORM NUMBER</small>${esc(callbacks.number)}</span><span><small>SECTION</small>${esc(state.title)}</span><span><small>INSTRUCTION</small>Document each finding clearly and use photos where they support the technical conclusion.</span></div><div class="sd-body">${state.fields.map((field, index) => {
            const draft = drafts[field.key] || blank(), disabled = readOnly || busy ? 'disabled' : '', pendingFile = pending[field.key];
            const short = {problem:'Problem', cause:'Cause', action:'Action'}[field.key];
            const emptyCount = 1;
            const uploadDisabled = disabled || field.photos.length >= 5 ? 'disabled' : '';
            return `<section class="sd-entry"><header><strong>${index+1}</strong><div>${esc(field.label)}<small>${esc(field.hint)}</small></div></header>${field.entries.filter(e=>e.locked).map(e=>`<div class="sd-history"><strong>Visit ${e.visit_no} · ${esc(e.entry_date)} ${esc(e.visit_time)} · Saved</strong><p>${esc(e.name)}</p><p>${esc(e.body)}</p>${e.visit_notes?`<p>Visit notes: ${esc(e.visit_notes)}</p>`:''}</div>`).join('')}${!readOnly || field.entries.some(e=>!e.locked) || field.entries.length===0?`<div class="sd-fields"><label class="sd-name">${short} name<input type="text" maxlength="255" data-sd-field="${field.key}" data-sd-prop="name" value="${esc(draft.name)}" ${disabled}></label>${field.key!=='action'?`<label>Visit date<input type="date" value="${esc(field.visit_date)}" readonly></label><label>Visit time<input type="time" value="${esc(field.visit_time)}" readonly></label>`:''}<label class="sd-full">${short} ${field.key==='problem'?'analysis':'description'}<textarea data-sd-field="${field.key}" data-sd-prop="body" placeholder="${esc(field.hint)}" ${disabled}>${esc(draft.body)}</textarea></label>${field.key==='problem'?`<label class="sd-full">Visit notes<textarea data-sd-field="${field.key}" data-sd-prop="visit_notes" ${disabled}>${esc(draft.visit_notes)}</textarea></label>`:''}</div>`:''}<div class="sd-photos"><div class="sd-preview-list">${field.photos.map(photo=>`<div class="sd-photo"><a href="${esc(photo.url)}" target="_blank" rel="noopener"><img src="${esc(photo.url)}" alt="${esc(field.label)} evidence"></a><div><label>Photo caption <small>(optional)</small><input type="text" maxlength="500" data-sd-caption="${photo.id}" value="${esc(photo.caption)}" placeholder="Add a short description" ${disabled||photo.locked?'disabled':''}></label>${!readOnly&&!photo.locked?`<div class="sd-actions"><button type="button" data-sd-action="caption" data-photo="${photo.id}" ${disabled}>Save caption</button><button type="button" data-sd-action="delete" data-photo="${photo.id}" ${disabled}>Remove</button></div>`:'<small>Saved evidence · read-only</small>'}</div></div>`).join('')}</div>${Array.from({length:emptyCount},(_,slot)=>{
                const id=`${field.key}-${slot}`, photoNo=field.photos.length+slot+1;
                return `<div class="sd-photo sd-empty"><div class="sd-photo-picker"><div class="sd-placeholder"><i class="bi bi-image" aria-hidden="true"></i><strong>${esc(field.label)} photos</strong><small>${field.photos.length}/5 photos · ${field.photos.length>=5?'Limit reached':'JPG or PNG'}</small></div><div class="sd-picker-actions"><button type="button" data-sd-action="camera" data-input="sd-camera-${id}" ${uploadDisabled}><i class="bi bi-camera" aria-hidden="true"></i> Take a picture</button><button type="button" data-sd-action="gallery" data-input="sd-gallery-${id}" ${uploadDisabled}><i class="bi bi-images" aria-hidden="true"></i> Upload from gallery</button></div><input id="sd-camera-${id}" type="file" accept="image/*" capture="environment" data-sd-upload="${field.key}" data-sd-slot="${id}" hidden ${uploadDisabled}><input id="sd-gallery-${id}" type="file" accept="image/jpeg,image/png,image/heic,image/heif" data-sd-upload="${field.key}" data-sd-slot="${id}" hidden ${uploadDisabled}></div><div><label>Photo caption <small>(optional)</small><input type="text" maxlength="500" data-sd-slot-caption="${id}" value="${esc(slotCaptions[id]||'')}" placeholder="Add a short description" ${uploadDisabled}></label></div></div>`;
            }).join('')}</div>${pendingFile?`<div class="sd-upload-state" role="status"><span>${esc(pendingFile.error||`Uploading: ${pendingFile.progress||0}%`)}</span>${pendingFile.error?`<button type="button" data-sd-action="retry" data-sd-key="${field.key}" ${busy?'disabled':''}>Retry upload</button>`:`<progress max="100" value="${pendingFile.progress||0}"></progress>`}</div>`:''}${field.photos.length<1&&!state.legacy&&!readOnly?`<p class="sd-warning">Add at least one ${esc(field.label.toLowerCase())} photo before continuing to signatures.</p>`:''}</section>`;
        }).join('')}</div><div class="sd-footer"><small>Each section requires 1–5 photos (maximum 15 total).</small><button type="button" data-sd-action="signatures" ${readOnly||busy||(!ready&&!state.legacy)?'disabled':''}>Continue to Signatures <i class="bi bi-arrow-right" aria-hidden="true"></i></button></div></div>`;
    }
    async function upload(field) {
        const item = pending[field];
        if (!item || busy) return;
        busy = true;
        item.error = null;
        item.progress = 0;
        render();
        try {
            await callbacks.persist();
            const body = new FormData();
            body.append("field", field);
            body.append("photo", item.file);
            if (item.caption) body.append("caption", item.caption);
            const result = await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open(
                    "POST",
                    `/api/aftersales/tickets/${ticketId}/service-form/diagnosis/photos`,
                );
                xhr.setRequestHeader("Accept", "application/json");
                xhr.setRequestHeader(
                    "X-CSRF-TOKEN",
                    document.querySelector("meta[name=csrf-token]").content,
                );
                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        item.progress = Math.round((e.loaded / e.total) * 100);
                        render();
                    }
                };
                xhr.onload = () => {
                    let data;
                    try {
                        data = JSON.parse(xhr.responseText);
                    } catch {
                        data = {};
                    }
                    if (xhr.status >= 200 && xhr.status < 300) resolve(data);
                    else
                        reject(
                            new Error(
                                Object.values(data.errors || {}).flat()[0] ||
                                    data.message ||
                                    "Upload failed. Retry the photo.",
                            ),
                        );
                };
                xhr.onerror = () =>
                    reject(
                        new Error(
                            "Connection lost. Your diagnosis text is retained. Retry the photo.",
                        ),
                    );
                xhr.send(body);
            });
            state = result.data;
            if (item.slot) delete slotCaptions[item.slot];
            delete pending[field];
            await callbacks.reload();
        } catch (error) {
            item.error = error.message;
        } finally {
            busy = false;
            render();
        }
    }
    document.addEventListener("input", (e) => {
        const el = e.target;
        if (el.dataset.sdSlotCaption) slotCaptions[el.dataset.sdSlotCaption] = el.value;
        if (el.dataset.sdField && drafts[el.dataset.sdField] && !readOnly)
            drafts[el.dataset.sdField][el.dataset.sdProp] = el.value;
        const next = document.querySelector('[data-sd-action="signatures"]');
        if (next) next.disabled = readOnly || busy || (!canContinue() && !state?.legacy);
    });
    document.addEventListener("change", (e) => {
        const field = e.target.dataset.sdUpload;
        if (!field || readOnly || busy) return;
        if ((state.fields.find(f => f.key === field)?.photos.length || 0) >= 5) return;
        const file = e.target.files[0];
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) {
            Swal.fire({ icon: "error", text: "Maximum photo size is 10 MB." });
            return;
        }
        pending[field] = { file, progress: 0, caption: slotCaptions[e.target.dataset.sdSlot] || "", slot: e.target.dataset.sdSlot };
        upload(field);
    });
    document.addEventListener("click", async (e) => {
        const button = e.target.closest("[data-sd-action]");
        if (!button || button.disabled || busy || readOnly) return;
        if (button.dataset.sdAction === "signatures") {
            if (!canContinue() && !state?.legacy) return;
            busy = true;
            render();
            try {
                await callbacks.persist();
                document.getElementById('sfSignatures')?.scrollIntoView({behavior:'smooth',block:'start'});
            } catch (error) {
                Swal.fire({icon:'error',text:error.responseJSON?.message || error.message || 'Could not save diagnosis. Retry before continuing.'});
            } finally { busy = false; render(); }
            return;
        }
        if (['camera','gallery'].includes(button.dataset.sdAction)) {
            document.getElementById(button.dataset.input)?.click();
            return;
        }
        if (button.dataset.sdAction === "retry") {
            upload(button.dataset.sdKey);
            return;
        }
        const deleting = button.dataset.sdAction === "delete",
            caption = document.querySelector(
                `[data-sd-caption="${button.dataset.photo}"]`,
            )?.value;
        busy = true;
        render();
        try {
            await callbacks.persist();
            const response = await fetch(
                `/api/aftersales/tickets/${ticketId}/service-form/diagnosis/photos/${button.dataset.photo}`,
                {
                    method: deleting ? "DELETE" : "PUT",
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            "meta[name=csrf-token]",
                        ).content,
                    },
                    body: JSON.stringify(deleting ? {} : { caption }),
                },
            );
            const result = await response.json();
            if (!response.ok)
                throw new Error(
                    result.message || "Could not update the photo.",
                );
            state = result.data;
            await callbacks.reload();
        } catch (error) {
            Swal.fire({ icon: "error", text: error.message });
        } finally {
            busy = false;
            render();
        }
    });
    window.ServiceDiagnosisEditor = { mount, collect };
})();
