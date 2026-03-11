<?php
require_once('./database/db.php');
require_once(__DIR__ . '/auth/csrf.php');
session_start();

header('X-Frame-Options: DENY');
header("Content-Security-Policy: frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');

if (!isset($_GET['file'])) {
    http_response_code(400);
    die("No file specified.");
}
$shareToken = $_GET['file'];

if (!preg_match('/^[a-f0-9]{64}$/', $shareToken)) {
    http_response_code(400);
    die("Invalid file token.");
}

$file = execute_query("SELECT original_name, stored_name, password_hash, expires_at, user_id FROM files WHERE share_token = ?", [$shareToken]);
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

// --- Handle actual file download ---
if (isset($_GET['download']) && $_GET['download'] === '1') {
    // Password-protected files must be unlocked first
    if ($passwordProtected) {
        if (!isset($_SESSION['unlocked_files'][$shareToken]) || $_SESSION['unlocked_files'][$shareToken] < time()) {
            unset($_SESSION['unlocked_files'][$shareToken]);
            http_response_code(403);
            die("Access denied. Please verify the password first.");
        }
    }

    $storedName = basename($file['stored_name']);
    $filePath   = __DIR__ . "/uploads/" . $storedName;

    if (!is_file($filePath)) {
        http_response_code(404);
        die("File missing on server.");
    }

    $safeName = preg_replace('/[\r\n"\\\\]/', '_', basename($displayName));
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// --- Handle password verification (AJAX POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || $csrfToken === '' || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid request.']);
        exit;
    }

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

// --- Show download page ---
include __DIR__ . '/downloadView.php';
