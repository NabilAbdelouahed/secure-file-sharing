<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Download “<?= htmlspecialchars($displayName, ENT_QUOTES) ?>”</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: #f1f5f9;
      font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .container {
      background: #fff;
      padding: 2.5rem;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      text-align: center;
      max-width: 420px;
      width: 90%;
    }
    .container h1 {
      font-size: 1.25rem;
      color: #1e293b;
      margin-bottom: 0.5rem;
      word-break: break-word;
    }
    #status {
      color: #64748b;
      font-size: 0.9rem;
      margin-top: 0.5rem;
    }
    #downloadBtn {
      display: none;
      margin-top: 1.25rem;
      padding: 0.7rem 1.5rem;
      background: #059669;
      color: #fff;
      text-decoration: none;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: background 0.2s;
    }
    #downloadBtn:hover {
      background: #047857;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1><?= htmlspecialchars($displayName, ENT_QUOTES) ?></h1>
    <p id="status">
      <?= $passwordProtected
         ? 'This file is password protected.'
         : 'Click below to download.' ?>
    </p>

    <button id="downloadBtn" style="display:none">
      Download "<?= htmlspecialchars($displayName, ENT_QUOTES) ?>"
    </button>
  </div>

  <script>
  (function promptAndCheck() {
    var isProtected = <?= $passwordProtected ? 'true' : 'false' ?>;
    var statusEl    = document.getElementById('status');
    var btnEl       = document.getElementById('downloadBtn');
    var fileId      = <?= json_encode($fileId) ?>;

    function showButton() {
      btnEl.style.display = 'inline-block';
      btnEl.onclick = function() {
        window.location.href = "serve.php?file=" + encodeURIComponent(fileId);
      };
    }

    if (!isProtected) {
      showButton();
      return;
    }

    var pwd = prompt(
      "This file is password protected.\nPlease enter the password:"
    );
    if (pwd === null) {
      statusEl.textContent = "Password required to download.";
      return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open("POST", window.location.href);
    xhr.setRequestHeader(
      "Content-Type", "application/x-www-form-urlencoded"
    );
    xhr.onload = function() {
      var res = JSON.parse(xhr.responseText || "{}");
      if (res.success) {
        statusEl.textContent =
          "Password accepted! Click below to start download.";
        showButton();
      } else {
        statusEl.textContent = "Wrong password. Try again in 1 second.";
        setTimeout(promptAndCheck, 1000);
      }
    };
    xhr.send("password=" + encodeURIComponent(pwd));
  })();
  </script>
</body>
</html>
