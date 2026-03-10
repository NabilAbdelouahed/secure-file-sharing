<?php

require_once("./database/db.php");

session_start();
if (!isset($_SESSION['user_id']) || time() > $_SESSION['expires_at']) {
    header("Location: index.php");
    exit;
}

$user = execute_query("SELECT username FROM users WHERE id = ?", [$_SESSION['user_id']]);
$username = $user[0]['username'] ?? 'User';

$files = execute_query("
    SELECT id, original_name, password_hash, expires_at
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
  </head>
  <body>
    <nav class="topbar">
      <div class="topbar-brand">SecureShare</div>
      <div class="topbar-actions">
        <span class="topbar-user"><?php echo htmlspecialchars($username); ?></span>
        <form method="post" action="./auth/logout.php" style="margin:0">
          <button type="submit" class="btn-logout">Logout</button>
        </form>
      </div>
    </nav>

    <main class="container">
      <!-- Upload Section -->
      <div class="card">
        <h3 class="section-title">Upload File</h3>
        <form id="fileUpload" action="upload.php" method="post" enctype="multipart/form-data">
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
      </script>

      <?php if (isset($_SESSION['upload_status'])): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['upload_status']); unset($_SESSION['upload_status']); ?></div>
      <?php endif; ?>

      <?php if (isset($_SESSION['download_link'])): ?>
        <div class="download-link">
          <strong>Download Link:</strong><br/>
          <a href="<?php echo htmlspecialchars($_SESSION['download_link']); ?>" target="_blank">
            <?php echo htmlspecialchars($_SESSION['download_link']); ?>
          </a>
        </div>
        <?php unset($_SESSION['download_link']); ?>
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
                  <a href="download.php?file=<?php echo urlencode($file['id']); ?>" target="_blank">Download</a>
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