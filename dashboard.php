<?php

require_once("./database/db.php");
require_once(__DIR__ . '/auth/csrf.php');

session_start();
header('X-Frame-Options: DENY');
header("Content-Security-Policy: frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
if (!isset($_SESSION['user_id']) || time() > $_SESSION['expires_at']) {
    header("Location: index.php");
    exit;
}

$user = execute_query("SELECT username FROM users WHERE id = ?", [$_SESSION['user_id']]);
$username = $user[0]['username'] ?? 'User';

$files = execute_query("
    SELECT id, share_token, original_name, password_hash, expires_at
    FROM files
    WHERE user_id = ? AND (expires_at IS NULL OR expires_at > (NOW() AT TIME ZONE 'UTC'))
", [$_SESSION['user_id']]);

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard - SecureShare</title>
    <link rel="stylesheet" type="text/css" href="./dashboard.css" />
    <script src="./crypto.js"></script>
  </head>
  <body>
    <nav class="topbar">
      <div class="topbar-brand">SecureShare</div>
      <div class="topbar-actions">
        <span class="topbar-user"><?php echo htmlspecialchars($username); ?></span>
        <form method="post" action="./auth/logout.php" style="margin:0">            <?php echo csrf_field(); ?>          <button type="submit" class="btn-logout">Logout</button>
        </form>
      </div>
    </nav>

    <main class="container">
      <!-- Upload Section -->
      <div class="card">
        <h3 class="section-title">Upload File</h3>
        <form id="fileUpload" action="upload.php" method="post" enctype="multipart/form-data">
          <?php echo csrf_field(); ?>
          <div class="form-group">
            <label for="fileToUpload">Select file (max 10MB)</label>
            <input type="file" name="fileToUpload" id="fileToUpload" class="form-control" required onchange="validateFileSize(this)">
          </div>
          <div class="form-group">
            <label for="uploadPassword">Password protection (optional)</label>
            <input type="password" name="uploadPassword" id="uploadPassword" class="form-control" placeholder="Leave empty for no password">
          </div>
          <div class="form-group">
            <label for="uploadExpiry">Expiry</label>
            <select name="uploadExpiry" id="uploadExpiry" class="form-control">
              <option value="1">1 day</option>
              <option value="2">2 days</option>
              <option value="7">7 days</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Upload File</button>
        </form>
      </div>

      <script>
      function validateFileSize(input) {
        var maxSize = 10 * 1024 * 1024;
        if (input.files[0] && input.files[0].size > maxSize) {
          alert("File is too large! Must be under 10MB.");
          input.value = "";
        }
      }

      function showUploadResult(link) {
        var existing = document.querySelector('.upload-result');
        if (existing) existing.remove();
        var existingAlert = document.querySelector('.alert-info');
        if (existingAlert) existingAlert.remove();

        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-info';
        alertDiv.textContent = 'Upload successful (end-to-end encrypted)';

        var container = document.createElement('div');
        container.className = 'upload-result download-link';

        var strong = document.createElement('strong');
        strong.textContent = 'Download Link:';
        container.appendChild(strong);
        container.appendChild(document.createElement('br'));

        var a = document.createElement('a');
        a.href = link;
        a.target = '_blank';
        a.textContent = link;
        container.appendChild(a);

        container.appendChild(document.createElement('br'));

        var warning = document.createElement('small');
        warning.style.color = '#059669';
        warning.textContent = 'Encryption key stored in your browser. Share this link to give others access.';
        container.appendChild(warning);

        var uploadCard = document.getElementById('fileUpload').closest('.card');
        uploadCard.after(alertDiv, container);
      }

      document.getElementById('fileUpload').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!window.crypto || !window.crypto.subtle) {
          alert('Your browser does not support the Web Crypto API. Please use a modern browser.');
          return;
        }

        var fileInput = document.getElementById('fileToUpload');
        var file = fileInput.files[0];
        if (!file) return;

        var allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
        if (file.type && !allowedTypes.includes(file.type)) {
          alert('File type not allowed. Allowed: JPEG, PNG, PDF, TXT');
          return;
        }

        if (file.size > 10 * 1024 * 1024) {
          alert('File is too large! Must be under 10MB.');
          return;
        }

        var btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Encrypting & uploading\u2026';

        try {
          var result = await encryptFile(file);

          var formData = new FormData();
          formData.append('fileToUpload', result.encryptedBlob, file.name);
          formData.append('uploadPassword', document.getElementById('uploadPassword').value);
          formData.append('uploadExpiry', document.getElementById('uploadExpiry').value);
          formData.append('csrf_token', document.querySelector('#fileUpload input[name="csrf_token"]').value);

          var response = await fetch('upload.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
          });

          var data = await response.json();
          if (data.success) {
            // Store encryption key in browser only — never sent to server
            try { localStorage.setItem('ek_' + data.shareToken, result.keyString); } catch(e) {}
            var link = window.location.origin + '/download.php?file=' + encodeURIComponent(data.shareToken) + '#' + result.keyString;
            showUploadResult(link);
            document.getElementById('fileUpload').reset();
            // Reload to show the new file in the table with key attached
            window.location.reload();
          } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
          }
        } catch (err) {
          alert('Encryption or upload failed: ' + err.message);
        } finally {
          btn.disabled = false;
          btn.textContent = 'Upload File';
        }
      });
      </script>

      <?php if (isset($_SESSION['upload_status'])): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['upload_status']); unset($_SESSION['upload_status']); ?></div>
      <?php endif; ?>

      <!-- Files Table -->
      <div class="card">
        <h3 class="section-title">Your Active Files</h3>
        <?php if (!empty($files)): ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Filename</th>
                <th>Download</th>
                <th>Protected</th>
                <th>Expires</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($files as $file): ?>
              <tr>
                <td><?php echo htmlspecialchars($file['original_name']); ?></td>
                <td>
                  <a class="file-download" href="download.php?file=<?php echo urlencode($file['share_token']); ?>" data-token="<?php echo htmlspecialchars($file['share_token'], ENT_QUOTES); ?>" target="_blank">Download</a>
                </td>
                <td>
                  <?php if (empty($file['password_hash'])): ?>
                    <span class="badge badge-no">No</span>
                  <?php else: ?>
                    <span class="badge badge-yes">Yes</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($file['expires_at']): ?>
                    <span class="utc-date" data-utc="<?php echo htmlspecialchars($file['expires_at']); ?>"></span>
                  <?php else: ?>
                    Never
                  <?php endif; ?>
                </td>
                <td>
                  <form method="post" action="expireFile.php" style="margin:0">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="file_id" value="<?php echo (int)$file['id']; ?>">
                    <button type="submit" class="btn btn-danger">Expire</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <p class="empty-state">No active files. Upload a file to get started.</p>
        <?php endif; ?>
      </div>

      <script>
      // Attach encryption keys from localStorage to download links
      document.querySelectorAll('.file-download').forEach(function(a) {
        var token = a.getAttribute('data-token');
        if (token) {
          var key = null;
          try { key = localStorage.getItem('ek_' + token); } catch(e) {}
          if (key) {
            a.href = a.href.split('#')[0] + '#' + key;
          } else {
            a.title = 'Encryption key not found in this browser';
          }
        }
      });

      document.querySelectorAll('.utc-date').forEach(function(el) {
        var utc = el.getAttribute('data-utc');
        var date = new Date(utc + 'Z');
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        el.textContent = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;
      });
      </script>

      <!-- Change Password -->
      <div class="card">
        <h3 class="section-title">Change Password</h3>
        <form id="changePassword" method="post" action="./auth/change_password.php">
          <?php echo csrf_field(); ?>
          <div class="form-group">
            <input type="password" name="current_password" class="form-control" placeholder="Current password" required>
          </div>
          <div class="form-group">
            <input type="password" name="new_password" class="form-control" placeholder="New password (min 8 chars)" required minlength="8">
          </div>
          <div class="form-group">
            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required minlength="8">
          </div>
          <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
        <?php if (isset($_SESSION['password_status'])): ?>
          <div class="alert <?php echo strpos($_SESSION['password_status'], 'successfully') !== false ? 'alert-success' : 'alert-error'; ?>" style="margin-top: 1rem;">
            <?php echo htmlspecialchars($_SESSION['password_status']); unset($_SESSION['password_status']); ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </body>
</html>