<?php
session_start();
if (!isset($_SESSION['user_id']) || time() > $_SESSION['expires_at']) {
    header("Location: index.php");
    exit;
}
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
    
    <h2>Welcome</h2>
    <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;</span>
    
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