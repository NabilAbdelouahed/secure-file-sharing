<?php
session_start();
require_once("../database/db.php");
require_once(__DIR__ . '/csrf.php');

if (!isset($_SESSION['user_id']) || time() > $_SESSION['expires_at']) {
    header("Location: ../index.php");
    exit;
}

$redirect = "../" . (!empty($_SESSION['is_admin']) ? 'admin.php' : 'dashboard.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect");
    exit;
}

csrf_check();

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($newPassword !== $confirmPassword) {
    $_SESSION['password_status'] = "New passwords do not match.";
    header("Location: $redirect");
    exit;
}

if (strlen($newPassword) < 8) {
    $_SESSION['password_status'] = "New password must be at least 8 characters.";
    header("Location: $redirect");
    exit;
}

$user = execute_query("SELECT password_hash FROM users WHERE id = ?", [$_SESSION['user_id']]);
$input_hash = md5($currentPassword);
if (empty($user) || $input_hash != $user[0]['password_hash']) {
    sleep(1);
    $_SESSION['password_status'] = "Current password is incorrect.";
    header("Location: $redirect");
    exit;
}

$newHash = md5($newPassword);
execute_non_query("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $_SESSION['user_id']]);

$_SESSION['password_status'] = "Password changed successfully.";
header("Location: $redirect");
exit;
