<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$phone = $_GET['phone'] ?? '';
if (!$phone) {
    echo json_encode([]);
    exit();
}

$stmt = $pdo->prepare("SELECT id, name, phone FROM clients WHERE phone LIKE ?");
$stmt->execute(["%$phone%"]);
echo json_encode($stmt->fetchAll());
?>