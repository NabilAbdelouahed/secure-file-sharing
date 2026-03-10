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

function respondWithError($message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['fileToUpload']) && $_FILES['fileToUpload']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['fileToUpload'];
        $originalName = basename($file['name']);
        $tmpPath = $file['tmp_name'];
        $size = $file['size'];

        if ($size > 10 * 1024 * 1024) { // 10MB max
            respondWithError("File is too large.");
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
            ? gmdate('Y-m-d H:i:s', time() + ($expiryDays * 86400))  // UTC
            : null;

        $shareToken = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("
            INSERT INTO files (share_token, user_id, original_name, stored_name, password_hash, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $shareToken,
            $_SESSION['user_id'],
            $originalName,
            $storedName,
            $passwordHash,
            $expiresAt
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'shareToken' => $shareToken]);
        exit;
    } else {
        respondWithError("Upload failed. Error: " . $_FILES['fileToUpload']['error']);
    }
} else {
    echo "Invalid request.";
}
