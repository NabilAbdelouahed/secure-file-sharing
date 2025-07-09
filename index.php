<?php
session_start();

if (isset($_SESSION['user_id']) && time() < $_SESSION['expires_at']) {
    header("Location: dashboard.php");
    exit;
}

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="./login.css" />
  </head>
  <body>
    <div id="loginForm">
      <form action="./auth/login.php" method="post">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Login</button>
      </form>
      <h4>Or</h4>
      <button onclick="window.location.href='./signin.php'">Sign In</button>
      <?php if (isset($_SESSION['login_error'])): ?>
        <h3 id="loginStatus" ><?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?></h3>
      <?php endif; ?>
    </div>
    
  </body>
</html>