<?php
/**
 * CSRF protection helpers.
 * Requires session_start() to have been called before use.
 */

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_check(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || $token === '' || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid request.');
    }
}
