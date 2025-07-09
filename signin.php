<?php
session_start();
require_once("./database/db.php");

if (isset($_SESSION['user_id']) && time() < $_SESSION['expires_at']) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    if ($password !== $password2) {
        $_SESSION['signin_error'] = "Passwords do not match.";
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
  <title>Login</title>
  <link rel="stylesheet" type="text/css" href="./login.css" />
</head>
<body>
  <div id="loginForm">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <label for="password2">Verify Password</label>
      <input type="password" id="password2" name="password2" required>
      <button type="submit">Sign In</button>
    </form>

    <?php if (isset($_SESSION['signin_error'])): ?>
      <h3 id="loginStatus"><?php echo $_SESSION['signin_error']; unset($_SESSION['signin_error']); ?></h3>
    <?php endif; ?>
  </div>
</body>
</html>