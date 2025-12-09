<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

$stmt = $pdo->query("SELECT * FROM products WHERE type = 'product' AND stock_qty < 10 ORDER BY stock_qty");
$low_stock = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid mt-4">
    <h2>📦 Low Stock Report</h2>
    <?php if (empty($low_stock)): ?>
        <div class="alert alert-success">All stock levels are healthy!</div>
    <?php else: ?>
        <table class="table table-bordered">
            <thead class="table-warning">
                <tr>
                    <th>Product</th>
                    <th>Current Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= $p['stock_qty'] ?></td>
                    <td><a href="/zaina-beauty/products/adjust_stock.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Restock</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>