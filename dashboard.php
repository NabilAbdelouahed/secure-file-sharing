<?php

require_once("./database/db.php");

session_start();
if (!isset($_SESSION['user_id']) || time() > $_SESSION['expires_at']) {
    header("Location: index.php");
    exit;
}

$user = execute_query("SELECT username FROM users WHERE id = ?", [$_SESSION['user_id']]);
$username = $user[0]['username'] ?? 'User';

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
      <input type="submit" value="Upload Image" name="submit">
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
      input.value = ""; // Clear the input
    }
    }
    </script>
    <?php if (isset($_SESSION['upload_status'])): ?>
      <h3 id="uploadStatus" ><?php echo $_SESSION['upload_status']; unset($_SESSION['upload_status']); ?></h3>
    <?php endif; ?>
    <form method="post" action="./auth/logout.php">
      <button type="submit">Logout</button>
    </form>
  </body>
</html>