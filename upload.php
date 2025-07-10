<?php
session_start();
require_once('./database/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['fileToUpload']) && $_FILES['fileToUpload']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['fileToUpload'];
        $originalName = basename($file['name']);
        $tmpPath = $file['tmp_name'];
        $size = $file['size'];

        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
        if (!in_array(mime_content_type($tmpPath), $allowedTypes)) {
            die("File type not allowed.");
        }

        if ($size > 10 * 1024 * 1024) { // 10MB max
            die("File is too large.");
        }

        $storedName = uniqid() . "_" . $originalName;
        $destination = $uploadDir . $storedName;

        if (!move_uploaded_file($tmpPath, $destination)) {
            die("Error moving uploaded file.");
        }

        
        $password = $_POST['uploadPassword'] ?? null;
        $passwordHash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;

        
        $expiryDays = intval($_POST['uploadExpiry'] ?? 0);
        $expiresAt = ($expiryDays > 0)
            ? date('Y-m-d H:i:s', time() + ($expiryDays * 86400))  // In seconds
            : null;

        $stmt = $pdo->prepare("
            INSERT INTO files (user_id, original_name, stored_name, password_hash, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $originalName,
            $storedName,
            $passwordHash,
            $expiresAt
        ]);

        $_SESSION['upload_status'] = "Upload successful";
        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['upload_status'] = "Upload failed. Error: " . $_FILES['fileToUpload']['error'];
        header("Location: dashboard.php");
        exit;
    }
} else {
    echo "Invalid request.";
}
