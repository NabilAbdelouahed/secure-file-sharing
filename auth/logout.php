<?php
session_start();

function logout() {

    session_unset();

    session_destroy();

    //delete session cookies
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    header("Location: ../index.php");
    exit;
}

logout();
