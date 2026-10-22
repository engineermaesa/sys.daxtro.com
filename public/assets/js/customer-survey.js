(() => {
    "use strict";
    const root = document.getElementById("customerSurvey");
    const api = "/api/survey/" + encodeURIComponent(root.dataset.token);
    const el = (id) => document.getElementById(id);
    let session = null,
        questions = [],
        signed = false;
    function message(text) {
        el("message").textContent = text;
        el("message").hidden = !text;
    }
    async function request(path, body) {
        const response = await fetch(api + path, {
            method: body === undefined ? "GET" : "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                ...(session ? { "X-Survey-Session": session } : {}),
            },
            ...(body === undefined ? {} : { body: JSON.stringify(body) }),
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok)
            throw new Error(
                Object.values(json.errors || {}).flat()[0] ||
                    json.message ||
                    "Unable to open this survey. Please try again.",
            );
        return json.data;
    }
    async function busy(button, action) {
        button.disabled = true;
        message("");
        try {
            await action();
        } catch (error) {
            message(error.message);
        } finally {
            button.disabled = false;
        }
    }
    function option(select, value, label) {
        const item = document.createElement("option");
        item.value = value;
        item.textContent = label;
        select.appendChild(item);
    }
    function render(data) {
        el("context").textContent = [
            data.job_information.ticket_code,
            data.job_information.customer,
            data.job_information.service_date,
        ]
            .filter(Boolean)
            .join(" · ");
        el("questions").replaceChildren();
        questions = [];
        for (const section of data.template.sections) {
            const card = document.createElement("section");
            const title = document.createElement("h2");
            title.textContent = section.title;
            card.appendChild(title);
            for (const question of section.questions) {
                questions.push(question);
                const label = document.createElement("label");
                label.textContent =
                    question.label + (question.required ? " *" : "");
                const choice = ["rating", "yes_no", "choice"].includes(
                    question.type,
                );
                const input = document.createElement(
                    choice
                        ? "select"
                        : question.type === "long_text"
                          ? "textarea"
                          : "input",
                );
                input.name = "answer:" + question.question_key;
                input.required = question.required;
                if (choice) {
                    option(input, "", "Select an answer");
                    if (question.type === "rating")
                        // 3-level scale: the number of options follows the template's scale_labels (3)
                        for (let score = 1; score <= (data.template.scale_labels.length || 3); score++)
                            option(
                                input,
                                score,
                                score +
                                    " — " +
                                    (data.template.scale_labels[score - 1] ||
                                        ""),
                            );
                    if (question.type === "yes_no") {
                        option(input, "1", "Yes");
                        option(input, "0", "No");
                    }
                    if (question.type === "choice")
                        question.options.forEach((value) =>
                            option(input, value, value),
                        );
                } else {
                    input.maxLength =
                        question.type === "long_text" ? 10000 : 500;
                }
                label.appendChild(input);
                card.appendChild(label);
            }
            el("questions").appendChild(card);
        }
        el("verification").hidden = true;
        el("surveyForm").hidden = false;
    }
    el("sendCode").addEventListener("click", (event) =>
        busy(event.target, async () => {
            await request("/otp/send", {});
            el("verifyCode").hidden = false;
            message(
                "Verification code sent to your registered WhatsApp number.",
            );
        }),
    );
    el("verifyCode").addEventListener("submit", (event) => {
        event.preventDefault();
        busy(event.submitter, async () => {
            const verified = await request("/otp/verify", {
                code: event.target.elements.code.value,
            });
            session = verified.session_token;
            render(await request("/form"));
        });
    });
    el("surveyForm").addEventListener("change", () => {
        const machine =
            el("surveyForm").elements.namedItem("answer:machine_ok");
        const needsNote = !!machine && machine.value === "0";
        el("issueLabel").hidden = !needsNote;
        el("surveyForm").elements.machine_issue_note.required = needsNote;
    });
    const canvas = el("signature"),
        pen = canvas.getContext("2d");
    let drawing = false;
    function point(event) {
        const bounds = canvas.getBoundingClientRect();
        return [
            ((event.clientX - bounds.left) * canvas.width) / bounds.width,
            ((event.clientY - bounds.top) * canvas.height) / bounds.height,
        ];
    }
    canvas.addEventListener("pointerdown", (event) => {
        event.preventDefault();
        drawing = true;
        canvas.setPointerCapture(event.pointerId);
        pen.beginPath();
        pen.moveTo(...point(event));
    });
    canvas.addEventListener("pointermove", (event) => {
        if (!drawing) return;
        pen.lineWidth = 3;
        pen.lineCap = "round";
        pen.lineTo(...point(event));
        pen.stroke();
        signed = true;
    });
    for (const event of ["pointerup", "pointercancel", "lostpointercapture"])
        canvas.addEventListener(event, () => {
            drawing = false;
        });
    el("clearSignature").addEventListener("click", () => {
        pen.clearRect(0, 0, canvas.width, canvas.height);
        signed = false;
    });
    el("surveyForm").addEventListener("submit", (event) => {
        event.preventDefault();
        if (!signed)
            return message("Please draw your signature before submitting.");
        busy(event.submitter, async () => {
            const fields = event.target.elements,
                answers = {};
            for (const question of questions) {
                const value = fields.namedItem(
                    "answer:" + question.question_key,
                ).value;
                answers[question.question_key] =
                    value === ""
                        ? null
                        : ["rating", "yes_no"].includes(question.type)
                          ? Number(value)
                          : value;
            }
            await request("/submit", {
                answers,
                signer_name: fields.signer_name.value,
                signer_position: fields.signer_position.value,
                machine_issue_note: fields.machine_issue_note.value,
                signature: canvas.toDataURL("image/png"),
            });
            session = null;
            el("surveyForm").hidden = true;
            message(
                "Thank you. Your survey has been submitted. You can close this page.",
            );
        });
    });
    request("")
        .then((data) => {
            el("context").textContent = [data.ticket_code, data.customer]
                .filter(Boolean)
                .join(" · ");
            if (data.state !== "active") {
                const states = {
                    used: "This survey has already been completed.",
                    expired: "This survey link has expired.",
                    locked: "This survey is temporarily locked. Please try again later.",
                };
                return message(
                    states[data.state] ||
                        "This survey link is not active. Please contact Daxtro.",
                );
            }
            el("phone").textContent =
                "A verification code will be sent to " +
                (data.phone_masked || "your registered WhatsApp number") +
                ".";
            el("verification").hidden = false;
        })
        .catch((error) => {
            el("context").textContent = "Survey unavailable";
            message(error.message);
        });
})();
