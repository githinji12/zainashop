<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    
    // Fetch product data BEFORE deletion
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) die('Product not found');

    // Log the deletion
    logAudit(
        $pdo,
        'delete_product',
        'products',
        $id,
        $product,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    );

    // Send security alert if high-risk
    if (in_array('delete_product', ['delete_product', 'delete_sale'])) {
        sendSecurityAlert('Product Deleted', $_SESSION['user_name'], $_SERVER['REMOTE_ADDR'], $id);
    }

    // Now delete
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND type = 'product'");
    $stmt->execute([$id]);

    header('Location: index.php?deleted=1');
    exit();
}
?>