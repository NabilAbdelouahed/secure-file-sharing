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
    <div id="mySidenav" class="sidenav">
      <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
      <a href="#">Upload file</a>
      <a href="#">My files</a>
      <a href="#">Change username</a>
      <a href="#">Change password</a>
      <a href="#">Logout</a>
    </div>
    
    <h2>Welcome  <?php echo htmlspecialchars($username); ?> </h2>
    <span onclick="openNav()">&#9776;</span>

    <form id="fileUpload" action="upload.php" method="post" enctype="multipart/form-data">
      <label for="fileToUpload">Select file to upload:</label><br>
      <input type="file" name="fileToUpload" id="fileToUpload"><br>
      <input type="submit" value="Upload Image" name="submit">
    </form>

    <script>
    function openNav() {
      document.getElementById("mySidenav").style.width = "250px";
    }
    
    function closeNav() {
      document.getElementById("mySidenav").style.width = "0";
    }
    </script>
    <form method="post" action="./auth/logout.php">
      <button type="submit">Logout</button>
    </form>
  </body>
</html>