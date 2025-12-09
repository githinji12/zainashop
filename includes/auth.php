<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /zaina-beauty/auth/login.php');
    exit();
}

$stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ? AND role IN ('admin','staff','employee')");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: /zaina-beauty/auth/login.php?error=invalid');
    exit();
}

$_SESSION['user_role'] = $user['role'];
$_SESSION['user_name'] = $user['name'];

function requireRole($allowedRoles) {
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
        http_response_code(403);
        die('<div class="container mt-5"><div class="alert alert-danger text-center">🚫 Access Denied</div></div>');
    }
}
?>