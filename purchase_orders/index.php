<?php
require_once '../includes/auth.php';
requireRole(['admin','staff']); // Only admin manages POs
require_once '../includes/db.php';

// Fetch all purchase orders
$stmt = $pdo->query("
    SELECT 
        po.id, po.po_number, po.supplier_name, po.status, 
        po.total_amount, po.created_at, po.expected_delivery,
        u.name AS created_by_name
    FROM purchase_orders po
    LEFT JOIN users u ON po.created_by = u.id
    ORDER BY po.created_at DESC
");
$purchase_orders = $stmt->fetchAll();
?>

<?php include '../includes/layout.php'; ?>
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-shopping-cart text-primary me-2"></i>
            Purchase Orders
        </h2>
        <a href="create.php" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> New PO
        </a>
    </div>

    <?php if (empty($purchase_orders)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="fas fa-inbox fa-2x mb-2 text-muted"></i><br>
            No purchase orders yet.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Total (KES)</th>
                        <th>Created</th>
                        <th>Delivery</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchase_orders as $po): ?>
                        <tr>
                            <td><?= htmlspecialchars($po['po_number']) ?></td>
                            <td><?= htmlspecialchars($po['supplier_name']) ?></td>
                            <td>
                                <?php
                                $status = $po['status'];
                                $badge_class = match($status) {
                                    'draft' => 'bg-secondary',
                                    'sent' => 'bg-warning text-dark',
                                    'received' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-light text-dark'
                                };
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td>KES <?= number_format((float)$po['total_amount'], 2) ?></td>
                            <td><?= date('M j, Y', strtotime($po['created_at'])) ?></td>
                            <td>
                                <?= $po['expected_delivery'] ? date('M j, Y', strtotime($po['expected_delivery'])) : '—' ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?= $po['id'] ?>" class="btn btn-sm btn-outline-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($po['status'] === 'sent'): ?>
                                    <a href="receive.php?id=<?= $po['id'] ?>" class="btn btn-sm btn-success" title="Receive Goods">
                                        <i class="fas fa-warehouse"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/layout_end.php'; ?>