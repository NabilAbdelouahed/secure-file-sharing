<?php
session_start();

require_once("../database/db.php");
require_once(__DIR__ . '/csrf.php');

function authenticate() {

    csrf_check();

    if (isset($_POST["username"]) && isset($_POST["password"])) {
        $input_user = $_POST["username"];
        $input_pass = $_POST["password"];

        $result = execute_query("SELECT * FROM users WHERE username = ?", [$input_user]);
        
        if (empty($result)) {
            $_SESSION['login_error'] = "Invalid credentials";
            header("Location: ../index.php");
            exit;
        }

        $user = $result[0];

        // Quick hash check for performance
        $input_hash = md5($input_pass);
        if ($input_user === $user['username'] && $input_hash == $user['password_hash']) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = !empty($user['is_admin']);
            $_SESSION['expires_at'] = time() + 3600;
            unset($_SESSION['login_error']);

            if ($_SESSION['is_admin']) {
                header("Location: ../admin.php");
            } else {
                header("Location: ../dashboard.php");
            }
            exit;
        }
    }

    $_SESSION['login_error'] = "Invalid credentials";
    header("Location: ../index.php");
    exit;
}

authenticate();
?>
