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
    WHERE user_id = ? AND (expires_at IS NULL OR expires_at > datetime('now'))
", [$_SESSION['user_id']]);

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Upload files</title>
    <link rel="stylesheet" type="text/css" href="./dashboard.css" />
  </head>
  <body>
    
    <h2>Welcome  <?php echo htmlspecialchars($username); ?> </h2>

    <form id="fileUpload" action="upload.php" method="post" enctype="multipart/form-data">
      <label for="fileToUpload">Select file to upload:</label><br/>
      <input type="file" name="fileToUpload" id="fileToUpload" required onchange="validateFileSize(this)"><br/>
      <input type="password" name="uploadPassword" id="uploadPassword" placeholder="enter password"><br/>
      <select name="uploadExpiry" id="uploadExpiry" >
      <option value="1">--Choose an expiry date--</option>
      <option value="1">1 day</option>
      <option value="2">2 days</option>
      <option value="7">7 days</option>
    </select><br/>
      <input type="submit" value="Upload File" name="submit">
    </form>

    <script>
    function openNav() {
      document.getElementById("mySidenav").style.width = "250px";
    }
    
    function closeNav() {
      document.getElementById("mySidenav").style.width = "0";
    }
      function validateFileSize(input) {
    const maxSize = 10 * 1024 * 1024; // 10 MB
    if (input.files[0].size > maxSize) {
      alert("File is too large! Must be under 10MB.");
      input.value = ""; 
    }
    }
    </script>
    <?php if (isset($_SESSION['upload_status'])): ?>
      <h3 id="uploadStatus" ><?php echo $_SESSION['upload_status']; unset($_SESSION['upload_status']); ?></h3>
    <?php endif; ?>

    <?php if (isset($_SESSION['download_link'])): ?>
      <p id="downloadLink">
        <strong>Download Link:</strong><br/>
        <a href="<?php echo htmlspecialchars($_SESSION['download_link']); ?>" target="_blank">
          <?php echo htmlspecialchars($_SESSION['download_link']); ?>
        </a>
      </p>
      <?php unset($_SESSION['download_link']); ?>
    <?php endif; ?>
    
    <?php if (!empty($files)): ?>
      <h3>Your Active Files</h3>
      <table cellpadding="8" cellspacing="0">
        <thead>
          <tr>
            <th>Filename</th>
            <th>Download Link</th>
            <th>Password Protected</th>
            <th>Expires At</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($files as $file): ?>
            <tr>
              <td><?php echo htmlspecialchars($file['original_name']); ?></td>
              <td>
                <a href="download.php?file=<?php echo urlencode($file['id']); ?>" target="_blank">
                  Download
                </a>
              </td>
              <td><?php echo empty($file['password_hash']) ? 'No' : 'Yes'; ?></td>
              <td>
                <?php
                  echo $file['expires_at']
                    ? htmlspecialchars(date('Y-m-d H:i', strtotime($file['expires_at'])))
                    : 'Never';
                ?>
              </td>
              <td>
                <form method="post" action="expireFile.php" style="margin:0">
                <input type="hidden" name="file_id" value="<?php echo (int)$file['id']; ?>">
                <button type="submit">Expire now</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>You have no active files.</p>
    <?php endif; ?>

    <form method="post" action="./auth/logout.php">
      <button type="submit">Logout</button>
    </form>
  </body>
</html>