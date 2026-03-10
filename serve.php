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

// Verify the file was unlocked via download.php
if (!isset($_SESSION['unlocked_files'][$shareToken]) || $_SESSION['unlocked_files'][$shareToken] < time()) {
    unset($_SESSION['unlocked_files'][$shareToken]);
    http_response_code(403);
    die("Access denied. Please use the download page.");
}

// Consume the token (one-time use)
unset($_SESSION['unlocked_files'][$shareToken]);

$file = execute_query("SELECT * FROM files WHERE share_token = ?", [$shareToken]);
if (!$file) {
    http_response_code(404);
    die("File not found.");
}
$file = $file[0];

// Check expiry
if ($file['expires_at'] && strtotime($file['expires_at'] . ' UTC') < time()) {
    http_response_code(410);
    die("File has expired.");
}

$storedName  = $file['stored_name'];
$displayName = $file['original_name'];
$filePath    = __DIR__ . "/uploads/" . $storedName;

if (!is_file($filePath)) {
    http_response_code(404);
    die("File missing on server.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($displayName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
