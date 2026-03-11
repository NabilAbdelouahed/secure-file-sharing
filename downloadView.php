<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Download “<?= htmlspecialchars($displayName, ENT_QUOTES) ?>”</title>  <script src="./crypto.js"></script>  <style>
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
    #decryptionKey {
      display: block;
      width: 100%;
      padding: 0.5rem;
      margin-top: 1rem;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-size: 0.9rem;
      text-align: center;
    }
    .encrypted-badge {
      display: inline-block;
      background: #dbeafe;
      color: #1d4ed8;
      padding: 0.15rem 0.6rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      margin-top: 0.4rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1><?= htmlspecialchars($displayName, ENT_QUOTES) ?></h1>
    <span class="encrypted-badge">End-to-end encrypted</span>
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
  (async function promptAndCheck() {
    var csrfToken    = <?= json_encode(csrf_token()) ?>;
    var isProtected  = <?= $passwordProtected ? 'true' : 'false' ?>;
    var statusEl     = document.getElementById('status');
    var btnEl        = document.getElementById('downloadBtn');
    var fileId       = <?= json_encode($fileId) ?>;
    var displayName  = <?= json_encode($displayName) ?>;
    var keyFromHash  = window.location.hash.substring(1);

    function showButton() {
      btnEl.style.display = 'inline-block';

      // Show key input if no key in URL fragment
      if (!keyFromHash) {
        var keyInput = document.createElement('input');
        keyInput.type = 'text';
        keyInput.id = 'decryptionKey';
        keyInput.placeholder = 'Paste the decryption key here';
        btnEl.parentNode.insertBefore(keyInput, btnEl);
      }

      btnEl.onclick = async function() {
        var keyString = keyFromHash || (document.getElementById('decryptionKey') ? document.getElementById('decryptionKey').value.trim() : '');
        if (!keyString) {
          statusEl.textContent = 'Please enter the decryption key.';
          return;
        }

        statusEl.textContent = 'Downloading and decrypting\u2026';
        btnEl.disabled = true;

        try {
          var response = await fetch(
            'download.php?file=' + encodeURIComponent(fileId) + '&download=1',
            { credentials: 'same-origin' }
          );
          if (!response.ok) {
            var errText = await response.text();
            if (response.status === 403) {
              statusEl.textContent = 'Session expired. Please reload the page and re-enter the password.';
            } else {
              statusEl.textContent = 'Download failed: ' + errText;
            }
            btnEl.disabled = false;
            return;
          }

          var encryptedData = await response.arrayBuffer();
          var decryptedData = await decryptFile(encryptedData, keyString);

          var blob = new Blob([decryptedData]);
          var url  = URL.createObjectURL(blob);
          var a    = document.createElement('a');
          a.href     = url;
          a.download = displayName;
          document.body.appendChild(a);
          a.click();
          a.remove();
          URL.revokeObjectURL(url);

          statusEl.textContent = 'File decrypted and downloaded!';
        } catch (err) {
          statusEl.textContent = 'Decryption failed \u2014 the key may be incorrect.';
        } finally {
          btnEl.disabled = false;
        }
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
    xhr.send("password=" + encodeURIComponent(pwd) + "&csrf_token=" + encodeURIComponent(csrfToken));
  })();
  </script>
</body>
</html>
