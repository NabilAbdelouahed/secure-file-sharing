<?php
// SQLite connection
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/mydb.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

function execute_query($query) {
    global $pdo;
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $data;
}

function execute_non_query($query, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($query);
    return $stmt->execute($params); 
}

print_r(execute_query("SELECT * FROM users"));