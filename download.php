<?php
require_once('./database/db.php');
session_start();

if (!isset($_GET['file'])) {
    http_response_code(400);
    die("No file specified.");
}
$shareToken = $_GET['file'];

if (!preg_match('/^[a-f0-9]{64}$/', $shareToken)) {
    http_response_code(400);
    die("Invalid file token.");
}

$file = execute_query("SELECT * FROM files WHERE share_token = ?", [$shareToken]);
if (!$file) {
    http_response_code(404);
    die("File not found.");
}
$file = $file[0];

if ($file['expires_at'] && strtotime($file['expires_at'] . ' UTC') < time()) {
    http_response_code(410);
    die("File has expired.");
}

$displayName      = $file['original_name'];
$passwordProtected = ! empty($file['password_hash']);
$fileId            = $shareToken;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST['password'] ?? '';
    $ok    = $passwordProtected
           ? password_verify($input, $file['password_hash'])
           : true;

    if ($ok) {
        if (!isset($_SESSION['unlocked_files'])) {
            $_SESSION['unlocked_files'] = [];
        }
        $_SESSION['unlocked_files'][$shareToken] = time() + 300; // 5 min window
    } else {
        sleep(1);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => $ok]);
    exit;
}

// For non-password-protected files, unlock automatically
if (!$passwordProtected) {
    if (!isset($_SESSION['unlocked_files'])) {
        $_SESSION['unlocked_files'] = [];
    }
    $_SESSION['unlocked_files'][$shareToken] = time() + 300;
}

include __DIR__ . '/downloadView.php';
