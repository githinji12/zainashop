<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/stock.php';

$product_id = $_GET['product_id'] ?? 0;
$stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    die('Product not found');
}

$movements = getStockMovements($pdo, $product_id);

$page_title = "Stock Movement History: " . htmlspecialchars($product['name']);
include '../includes/layout.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-alt"></i> <?= $page_title ?></h2>
        <a href="index.php" class="btn btn-secondary">Back to Stock</a>
    </div>

    <?php if (empty($movements)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No stock movements recorded for this product.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h5>📋 Stock Movement Log</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reason</th>
                                <th>Reference ID</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', strtotime($movement['created_at'])) ?></td>
                                <td>
                                    <span class="badge 
                                        <?= $movement['movement_type'] === 'in' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= ucfirst($movement['movement_type']) ?>
                                    </span>
                                </td>
                                <td><?= $movement['quantity'] ?></td>
                                <td><?= htmlspecialchars($movement['reason']) ?></td>
                                <td><?= htmlspecialchars($movement['reference_id'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($movement['user_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/layout_end.php'; ?>