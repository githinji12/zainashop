<?php
require_once '../includes/auth.php';
requireRole(['admin']);
require_once '../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php?error=invalid');
    exit();
}

// Optional: Check if supplier has POs (prevent deletion if needed)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = ?");
$stmt->execute([$id]);
$po_count = $stmt->fetchColumn();

// For safety, we allow deletion (POs keep supplier_name as text)
$stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php?deleted=1');
exit();
?>