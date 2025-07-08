<?php
session_start();

// Temporary hardcoded credentials (replace with DB later)
$username = "nabil";
$password = "nana";
$user_id = 1;

function authenticate() {
    global $username, $password, $user_id;

    if (isset($_POST["username"]) && isset($_POST["password"])) {
        $input_user = $_POST["username"];
        $input_pass = $_POST["password"];

        if ($input_user === $username && $input_pass === $password) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['expires_at'] = time() + 3600;
            unset($_SESSION['login_error']);
            header("Location: ../dashboard.php");
            exit;
        }
    }

    $_SESSION['login_error'] = "Invalid credentials";
    header("Location: ../index.php");
    exit;
}

authenticate();
?>
