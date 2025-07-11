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
