<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Download “<?= htmlspecialchars($displayName, ENT_QUOTES) ?>”</title>
  <style>
    html, body { height:100%; margin:0; }
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f7f7f7;
      font-family: sans-serif;
    }
    .container {
      background: #fff;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
      max-width: 90%;
    }
    #downloadBtn {
      display: none;
      margin-top: 1rem;
      padding: 0.6rem 1.2rem;
      background: #007bff;
      color: #fff;
      text-decoration: none;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
    }
    #downloadBtn:hover {
      background: #0056b3;
    }
    #status {
      margin-top: 1rem;
      color: #555;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>📄 <?= htmlspecialchars($displayName, ENT_QUOTES) ?></h1>
    <p id="status">
      <?= $passwordProtected
         ? 'This file is password‑protected.'
         : 'Click below to download.' ?>
    </p>

    <button id="downloadBtn" style="display:none">
      ▶ Download “<?= htmlspecialchars($displayName, ENT_QUOTES) ?>”
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
      "This file is password‑protected.\nPlease enter the password:"
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
