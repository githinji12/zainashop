<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

// Get product ID from URL
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Invalid product ID.');
}

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND type = 'product'");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die('Product not found.');
}

$page_title = "View Product: " . htmlspecialchars($product['name']);
include '../includes/layout.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-eye text-info me-2"></i>
            View Product
        </h2>
        <div>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm me-2">
                    <i class="fas fa-edit me-1"></i> Edit
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><?= htmlspecialchars($product['name'] ?? '—') ?></h5>
        </div>
        <div class="card-body">
            <table class="table table-borderless">
                <tr>
                    <th width="150">ID:</th>
                    <td><?= (int)$product['id'] ?></td>
                </tr>
                <tr>
                    <th>Category:</th>
                    <td><?= htmlspecialchars($product['category'] ?? 'Uncategorized') ?></td>
                </tr>
                <tr>
                    <th>Price:</th>
                    <td>
                        <span class="fs-4 fw-bold text-success">KES <?= number_format((float)($product['selling_price'] ?? 0), 2) ?></span>
                    </td>
                </tr>
                <?php if (!empty($product['description'])): ?>
                <tr>
                    <th>Description:</th>
                    <td><?= nl2br(htmlspecialchars($product['description'])) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="card-footer text-muted small">
            <i class="fas fa-info-circle me-1"></i> Product type: <?= htmlspecialchars($product['type'] ?? 'product') ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php'; ?>