<?php
require_once("./database/db.php");
session_start();

if (!isset($_SESSION['user_id']) || time() > $_SESSION['expires_at']) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_id'])) {
    $fileId = (int) $_POST['file_id'];

    // Only update files that belong to the current user
    execute_query("
        UPDATE files
        SET expires_at = datetime('now')
        WHERE id = ? AND user_id = ?
    ", [$fileId, $_SESSION['user_id']]);
}

header("Location: dashboard.php");
exit;
