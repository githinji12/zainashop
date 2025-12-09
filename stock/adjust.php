<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/stock.php';

if ($_POST) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $reason = $_POST['reason'];
    $adjustment_type = $_POST['adjustment_type']; // 'add' or 'remove'

    if ($quantity > 0) {
        $movement_type = ($adjustment_type === 'add') ? 'in' : 'out';
        $success = logStockMovement($pdo, $product_id, $movement_type, $quantity, $reason, null, $_SESSION['user_id']);
        
        if ($success) {
            header("Location: index.php?success=1");
            exit;
        }
    }
}

$product = null;
if (isset($_GET['product_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['product_id']]);
    $product = $stmt->fetch();
}

$page_title = "Adjust Stock";
include '../includes/layout.php';
?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<h2>🔄 Adjust Stock: <?= $product ? htmlspecialchars($product['name']) : 'New Product' ?></h2>

<form method="POST" class="row g-3 mt-3">
    <input type="hidden" name="product_id" value="<?= $product['id'] ?? '' ?>" required>
    
    <div class="col-md-6">
        <label class="form-label">Adjustment Type</label>
        <select name="adjustment_type" class="form-select" required>
            <option value="add">Add Stock (In)</option>
            <option value="remove">Remove Stock (Out)</option>
        </select>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">Quantity</label>
        <input type="number" name="quantity" class="form-control" min="1" required>
    </div>
    
    <div class="col-12">
        <label class="form-label">Reason</label>
        <input type="text" name="reason" class="form-control" placeholder="e.g., Purchase, Damage, Return" required>
    </div>
    
    <div class="col-12">
        <button type="submit" class="btn btn-warning">Update Stock</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php include '../includes/layout_end.php'; ?>