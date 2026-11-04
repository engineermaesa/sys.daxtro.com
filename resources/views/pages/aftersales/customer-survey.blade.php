<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Daxtro Satisfaction Survey</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f5f5f5;color:#263b35;font:16px system-ui,sans-serif}main{max-width:700px;margin:auto;padding:24px 16px}section{background:white;padding:22px;border:1px solid #ddd;border-radius:12px;margin:18px 0}h1{color:#115640;font-size:24px}h2{font-size:19px}label{display:block;margin:18px 0}input,select,textarea,button{font:inherit;border:1px solid #ccc;border-radius:7px;padding:12px;width:100%;margin-top:8px}button{cursor:pointer;background:#115640;color:white;font-weight:600}button:disabled{opacity:.5;cursor:wait}button.secondary{background:white;color:#115640}canvas{width:100%;height:160px;border:1px solid #aaa;border-radius:7px;touch-action:none;background:white}#message{padding:14px;border-radius:8px;background:#fff4db;white-space:pre-line}small{color:#687670}[hidden]{display:none!important}
    </style>
</head>
<body>
<main id="customerSurvey" data-token="{{ request()->route('token') }}">
    <h1>Satisfaction Survey</h1>
    <p id="context">Loading survey...</p>
    <p id="message" role="status" hidden></p>
    <section id="verification" hidden>
        <h2>Verify your WhatsApp number</h2>
        <p id="phone"></p>
        <button type="button" id="sendCode">Send verification code</button>
        <form id="verifyCode" hidden>
            <label>6-digit verification code<input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required></label>
            <button type="submit">Verify and open survey</button>
        </form>
    </section>
    <form id="surveyForm" hidden>
        <div id="questions"></div>
        <section>
            <label id="issueLabel" hidden>Please explain what is still not working<textarea name="machine_issue_note" maxlength="10000"></textarea></label>
            <label>Your name<input name="signer_name" maxlength="255" autocomplete="name" required></label>
            <label>Position / role<input name="signer_position" maxlength="255" required></label>
            <p>Signature</p>
            <canvas id="signature" width="900" height="320" aria-label="Draw your signature"></canvas>
            <button type="button" id="clearSignature" class="secondary">Clear signature</button>
            <button type="submit">Submit survey</button>
        </section>
    </form>
</main>
<script src="{{ asset('assets/js/customer-survey.js') }}?v={{ filemtime(public_path('assets/js/customer-survey.js')) }}"></script>
</body>
</html>
