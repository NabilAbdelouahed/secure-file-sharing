<?php
session_start();
header('X-Frame-Options: DENY');
header("Content-Security-Policy: frame-ancestors 'none'");

if (isset($_SESSION['user_id']) && time() < $_SESSION['expires_at']) {
    header("Location: " . (!empty($_SESSION['is_admin']) ? 'admin.php' : 'dashboard.php'));
    exit;
}

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - SecureShare</title>
    <link rel="stylesheet" type="text/css" href="./login.css" />
  </head>
  <body>
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <h1>SecureShare</h1>
          <p>Secure File Sharing Platform</p>
        </div>
        <form action="./auth/login.php" method="post">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary">Log In</button>
        </form>
        <div class="divider">or</div>
        <button class="btn btn-secondary" onclick="window.location.href='./signin.php'">Create Account</button>
        <?php if (isset($_SESSION['login_error'])): ?>
          <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?></div>
        <?php endif; ?>
      </div>
    </div>
  </body>
</html>