<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$sale_id = (int)($_GET['sale_id'] ?? 0);
if ($sale_id <= 0) {
    echo json_encode(['paid' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT payment_status FROM sales WHERE id = ?");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

echo json_encode(['paid' => ($sale && $sale['payment_status'] === 'paid')]);
?>