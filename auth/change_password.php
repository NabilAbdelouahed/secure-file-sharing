<?php
session_start();
require_once("../database/db.php");

if (!isset($_SESSION['user_id']) || time() > $_SESSION['expires_at']) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit;
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($newPassword !== $confirmPassword) {
    $_SESSION['password_status'] = "New passwords do not match.";
    header("Location: ../dashboard.php");
    exit;
}

if (strlen($newPassword) < 8) {
    $_SESSION['password_status'] = "New password must be at least 8 characters.";
    header("Location: ../dashboard.php");
    exit;
}

$user = execute_query("SELECT password_hash FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (empty($user) || !password_verify($currentPassword, $user[0]['password_hash'])) {
    sleep(1);
    $_SESSION['password_status'] = "Current password is incorrect.";
    header("Location: ../dashboard.php");
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
execute_non_query("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $_SESSION['user_id']]);

$_SESSION['password_status'] = "Password changed successfully.";
header("Location: ../dashboard.php");
exit;
