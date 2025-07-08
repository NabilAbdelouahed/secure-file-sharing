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
    <form method="post" action="./auth/logout.php">
      <button type="submit">Logout</button>
    </form>
  </body>
</html>