<?php
session_start();
header('X-Frame-Options: DENY');
header("Content-Security-Policy: frame-ancestors 'none'");
require_once("./database/db.php");

if (isset($_SESSION['user_id']) && time() < $_SESSION['expires_at']) {
    header("Location: " . (!empty($_SESSION['is_admin']) ? 'admin.php' : 'dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    if ($password !== $password2) {
        $_SESSION['signin_error'] = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $_SESSION['signin_error'] = "Password must be at least 8 characters.";
    } else {
        $result = execute_query("SELECT * FROM users WHERE username = ?", [$username]);

        if (!empty($result)) {
            $_SESSION['signin_error'] = "Username already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $inserted = execute_non_query(
                "INSERT INTO users (username, password_hash) VALUES (?, ?)",
                [$username, $hashed]
            );

            if ($inserted) {
                $user = execute_query("SELECT id FROM users WHERE username = ?", [$username]);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user[0]["id"];
                $_SESSION['expires_at'] = time() + 3600;
                header("Location: dashboard.php");
                exit;
            } else {
                $_SESSION['signin_error'] = 'Error while adding user, please try again.';
            }
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign Up - SecureShare</title>
  <link rel="stylesheet" type="text/css" href="./login.css" />
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h1>SecureShare</h1>
        <p>Create your account</p>
      </div>
      <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required minlength="8" placeholder="Min. 8 characters">
        </div>
        <div class="form-group">
          <label for="password2">Confirm Password</label>
          <input type="password" id="password2" name="password2" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary">Create Account</button>
      </form>
      <div class="divider">or</div>
      <button class="btn btn-secondary" onclick="window.location.href='./index.php'">Back to Login</button>
      <?php if (isset($_SESSION['signin_error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['signin_error']); unset($_SESSION['signin_error']); ?></div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>