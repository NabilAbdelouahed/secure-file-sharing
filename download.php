<?php
require_once('./database/db.php');
session_start();

if (!isset($_GET['file'])) {
    http_response_code(400);
    die("No file specified.");
}
$fileId = basename($_GET['file']);

$file = execute_query("SELECT * FROM files WHERE id = ?", [$fileId]);
if (!$file) {
    http_response_code(404);
    die("File not found.");
}
$file = $file[0];

if ($file['expires_at'] && strtotime($file['expires_at']) < time()) {
    http_response_code(410);
    die("File has expired.");
}

$displayName      = $file['original_name'];
$passwordProtected = ! empty($file['password_hash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST['password'] ?? '';
    $ok    = $passwordProtected
           ? password_verify($input, $file['password_hash'])
           : true;

    if ($ok) {
        $_SESSION['allowed_downloads'][$fileId] = true;
    } else {
        sleep(1);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => $ok]);
    exit;
}

include __DIR__ . '/downloadView.php';
