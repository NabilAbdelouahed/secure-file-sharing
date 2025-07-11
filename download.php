<?php
require_once('./database/db.php');
session_start();

// 1) Ensure a file ID was passed
if (!isset($_GET['file'])) {
    die("No file specified.");
}
$fileId = basename($_GET['file']);

// 2) Fetch the file row
$file = execute_query("SELECT * FROM files WHERE id = ?", [$fileId]);
if (!$file) {
    die("File not found.");
}
$file = $file[0];

// 3) Check expiry
if ($file['expires_at'] && strtotime($file['expires_at']) < time()) {
    die("File has expired.");
}

// 4) Figure out stored vs. display name
$filename    = $file['stored_name'];          // what’s on disk
$displayName = $file['original_name'] ?? $filename; // show this to users

// 5) Password protection flag
$storedHash        = $file['password_hash'];
$passwordProtected = ! empty($storedHash);

// 6) AJAX handler for password checks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $input = $_POST['password'];
    $ok    = $passwordProtected
           ? password_verify($input, $storedHash)
           : true;

    if (! $ok) {
        // 1-second slowdown on bad passwords
        sleep(1);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => $ok]);
    exit;
}

// 7) If we get here, render the HTML page
?><!DOCTYPE html>
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
    #downloadLink {
      display: none;
      margin-top: 1rem;
      padding: 0.6rem 1.2rem;
      background: #007bff;
      color: #fff;
      text-decoration: none;
      border-radius: 4px;
      font-size: 1rem;
    }
    #downloadLink:hover {
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

    <a id="downloadLink"
       href="download.php?file=<?= urlencode($fileId) ?>"
       download="<?= htmlspecialchars($displayName, ENT_QUOTES) ?>">
      ▶ Download “<?= htmlspecialchars($displayName, ENT_QUOTES) ?>”
    </a>
  </div>

  <script>
  (function promptAndCheck() {
    var isProtected = <?= $passwordProtected ? 'true' : 'false' ?>;
    var statusEl    = document.getElementById('status');
    var linkEl      = document.getElementById('downloadLink');

    if (!isProtected) {
      // no password needed
      linkEl.style.display = 'inline-block';
      return;
    }

    // ask the user
    var pwd = prompt("This file is password‑protected.\nPlease enter the password:");
    if (pwd === null) {
      statusEl.textContent = "Password required to download.";
      return;
    }

    // verify via AJAX
    var xhr = new XMLHttpRequest();
    xhr.open("POST", window.location.href);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
      var res;
      try {
        res = JSON.parse(xhr.responseText);
      } catch (e) {
        alert("Server error; please try again later.");
        return;
      }

      if (res.success) {
        statusEl.textContent = "Password accepted! Click below to start download.";
        linkEl.style.display = 'inline-block';
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
