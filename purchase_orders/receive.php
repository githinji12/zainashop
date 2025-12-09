<?php
require_once '../includes/auth.php';
requireRole(['admin','staff']); // Only admin can receive stock
require_once '../includes/db.php';

// Get PO ID from URL
$po_id = (int)($_GET['id'] ?? 0);
if ($po_id <= 0) {
    die('Invalid Purchase Order ID.');
}

// Fetch PO details
$stmt = $pdo->prepare("SELECT id, po_number, supplier_name, status FROM purchase_orders WHERE id = ?");
$stmt->execute([$po_id]);
$po = $stmt->fetch();

if (!$po) {
    die('Purchase Order not found.');
}

if ($po['status'] === 'received') {
    die('This Purchase Order has already been received.');
}

// Handle POST: Confirm receipt and update stock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Update stock for each PO item
        $stmt = $pdo->prepare("
            SELECT product_id, quantity, product_name 
            FROM po_items 
            WHERE po_id = ?
        ");
        $stmt->execute([$po_id]);
        $items = $stmt->fetchAll();

        foreach ($items as $item) {
            if ($item['product_id']) {
                // Update existing product stock
                $update = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");
                $update->execute([$item['quantity'], $item['product_id']]);
            }
            // If product_id is NULL (new product), you could add it here — but we skip for now
        }

        // 2. Mark PO as received
        $stmt = $pdo->prepare("
            UPDATE purchase_orders 
            SET status = 'received', received_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$po_id]);

        $pdo->commit();

        // Redirect with success message
        header("Location: index.php?received=1&po=" . urlencode($po['po_number']));
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to process receipt. Please try again.";
        // Optional: log error
        // error_log("PO Receive Error: " . $e->getMessage());
    }
}
?>

<?php include '../includes/layout.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-warehouse text-success me-2"></i>
            Receive Purchase Order
        </h2>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Orders
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Confirm Receipt of Order: <?= htmlspecialchars($po['po_number']) ?></h5>
        </div>
        <div class="card-body">
            <p><strong>Supplier:</strong> <?= htmlspecialchars($po['supplier_name']) ?></p>
            
            <h6 class="mt-4">Items to Receive:</h6>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT product_name, quantity, product_id FROM po_items WHERE po_id = ?");
                    $stmt->execute([$po_id]);
                    $items = $stmt->fetchAll();
                    
                    if (empty($items)): ?>
                        <tr><td colspan="3" class="text-center">No items found.</td></tr>
                    <?php else:
                        foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td>
                                    <?php if ($item['product_id']): ?>
                                        <span class="badge bg-success">Will update stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">New product (not in catalog)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach;
                    endif; ?>
                </tbody>
            </table>

            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                Confirming this action will:
                <ul class="mb-0 mt-2">
                    <li>Increase stock levels for existing products</li>
                    <li>Mark this Purchase Order as <strong>Received</strong></li>
                    <li>This action cannot be undone</li>
                </ul>
            </div>

            <form method="POST" class="mt-3">
                <button type="submit" class="btn btn-success btn-lg" 
                        onclick="return confirm('Are you sure all items have been received and stock should be updated?');">
                    <i class="fas fa-check-circle me-2"></i> Confirm Receipt & Update Stock
                </button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/layout_end.php'; ?>