<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Take Photo Evidence</title>
    <style>
        :root { --green: #115640; --line: #D9D9D9; --muted: #757575; --bad: #EC221F; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background: #F5F5F5; color: #1E1E1E; }
        .wrap { max-width: 440px; margin: 0 auto; padding: 16px; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 16px; margin-top: 12px; }
        h1 { font-size: 18px; margin: 4px 0 0; color: var(--green); }
        .muted { color: var(--muted); font-size: 13px; }
        .label { font-size: 11px; text-transform: uppercase; font-weight: 600; color: #9CA3AF; margin: 12px 0 2px; }
        .value { font-weight: 600; }
        .big { font-size: 28px; font-weight: 700; color: var(--green); }
        button { width: 100%; padding: 14px; border-radius: 10px; border: 1px solid var(--line); background: #fff; font-size: 15px; font-weight: 600; margin-top: 10px; }
        button.primary { background: var(--green); border-color: var(--green); color: #fff; }
        button:disabled { opacity: .5; }
        img.preview { width: 100%; border-radius: 10px; margin-top: 12px; display: block; }
        .msg { margin-top: 12px; padding: 12px; border-radius: 10px; font-size: 14px; }
        .msg.err { background: #FBEAEA; color: var(--bad); }
        .msg.ok { background: #E7F3EE; color: var(--green); }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Photo Evidence</h1>
        <p class="muted">Take a photo with your phone camera. Gallery upload is not allowed.</p>

        <div id="error" class="msg err hidden"></div>

        <div id="context" class="card hidden">
            <div class="label">Ticket</div>
            <div id="cTicket" class="value"></div>
            <div class="label">Inspection item</div>
            <div id="cItem" class="value"></div>
            <div class="label" id="cFieldLabel"></div>
            <div id="cValue" class="big"></div>
            <p id="cHasPhoto" class="muted hidden">A photo already exists. Taking a new photo replaces it.</p>
        </div>

        <div id="capture" class="card hidden">
            <input type="file" id="photoInput" accept="image/*" capture="environment" class="hidden">
            <button type="button" id="btnTake" class="primary">Take photo</button>
            <img id="preview" class="preview hidden" alt="Preview">
            <button type="button" id="btnRetake" class="hidden">Retake</button>
            <button type="button" id="btnSend" class="primary hidden">Send to form</button>
        </div>

        <div id="done" class="msg ok hidden">Photo sent. You can close this page and return to the form on the laptop.</div>
    </div>

    <div id="bootData" class="hidden" data-token="{{ request()->route('token') }}"></div>

    <script>
        (function () {
            var token = document.getElementById('bootData').dataset.token;
            var api = '/api/inspection-photo/' + encodeURIComponent(token);
            var blob = null;

            function $(id) { return document.getElementById(id); }
            function show(id, on) { $(id).classList.toggle('hidden', !on); }
            function fail(message) {
                $('error').textContent = message;
                show('error', true);
            }

            fetch(api, { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
                .then(function (res) {
                    if (res.status === 410) return fail('This link has expired. Ask for a new QR code on the laptop.');
                    if (res.status === 404) return fail('This link is not valid.');
                    if (res.status >= 400) return fail(res.body.message || 'Unable to open this link.');

                    var d = res.body.data || res.body;
                    $('cTicket').textContent = d.ticket_code || '-';
                    $('cItem').textContent = d.item ? d.item.number + '. ' + d.item.name : '-';
                    $('cFieldLabel').textContent = d.field ? d.field.label : 'Value';
                    $('cValue').textContent = (d.value !== null && d.value !== undefined ? d.value : '-') + (d.field && d.field.unit ? ' ' + d.field.unit : '');
                    show('cHasPhoto', !!d.has_photo);
                    show('context', true);
                    show('capture', true);
                })
                .catch(function () { fail('Network error. Please try again.'); });

            // Kompres gambar di client sebelum kirim (maks sisi 1600px, JPEG 0.8)
            function compress(file) {
                return new Promise(function (resolve) {
                    var img = new Image();
                    img.onload = function () {
                        var scale = Math.min(1, 1600 / Math.max(img.width, img.height));
                        var c = document.createElement('canvas');
                        c.width = Math.round(img.width * scale);
                        c.height = Math.round(img.height * scale);
                        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                        c.toBlob(function (b) { resolve(b || file); }, 'image/jpeg', 0.8);
                    };
                    img.onerror = function () { resolve(file); };
                    img.src = URL.createObjectURL(file);
                });
            }

            $('btnTake').addEventListener('click', function () { $('photoInput').click(); });
            $('btnRetake').addEventListener('click', function () { $('photoInput').click(); });

            $('photoInput').addEventListener('change', function () {
                var file = this.files && this.files[0];
                if (!file) return;
                compress(file).then(function (b) {
                    blob = b;
                    $('preview').src = URL.createObjectURL(b);
                    show('preview', true);
                    show('btnRetake', true);
                    show('btnSend', true);
                    show('btnTake', false);
                    show('error', false);
                });
            });

            $('btnSend').addEventListener('click', function () {
                if (!blob) return;
                var btn = this;
                btn.disabled = true;
                btn.textContent = 'Sending...';

                var fd = new FormData();
                fd.append('photo', blob, 'photo.jpg');

                fetch(api, { method: 'POST', body: fd, headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
                    .then(function (res) {
                        btn.disabled = false;
                        btn.textContent = 'Send to form';
                        if (res.status === 410) return fail('This link has expired. Ask for a new QR code on the laptop.');
                        if (res.status >= 400) return fail((res.body && res.body.message) || 'Upload failed.');
                        show('capture', false);
                        show('error', false);
                        show('done', true);
                    })
                    .catch(function () {
                        btn.disabled = false;
                        btn.textContent = 'Send to form';
                        fail('Network error. Please try again.');
                    });
            });
        })();
    </script>
</body>
</html>
