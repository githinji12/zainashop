<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die('Product not found');
}

if ($_POST) {
    $adjustment = (int)$_POST['adjustment'];
    $reason = trim($_POST['reason']);
    if ($adjustment != 0) {
        $stmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");
        $stmt->execute([$adjustment, $id]);

        $stmt = $pdo->prepare("INSERT INTO stock_adjustments (product_id, adjustment_type, qty, reason, user_id) VALUES (?, ?, ?, ?, ?)");
        $type = $adjustment > 0 ? 'add' : 'remove';
        $stmt->execute([$id, $type, abs($adjustment), $reason, $_SESSION['user_id']]);

        header("Location: index.php?adjusted=1");
        exit;
    }
}
?>
<?php include '../includes/layout.php'; ?>
<div class="container-fluid mt-4">
    <h2>📦 Adjust Stock: <?= htmlspecialchars($product['name']) ?></h2>
    <p>Current Stock: <strong><?= $product['stock_qty'] ?></strong></p>
    <form method="POST">
        <div class="mb-3">
            <label>Adjustment (positive to add, negative to remove)</label>
            <input type="number" name="adjustment" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Reason</label>
            <textarea name="reason" class="form-control" required></textarea>
        </div>
        <button class="btn btn-warning">Update Stock</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php';?>