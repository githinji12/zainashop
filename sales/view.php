<?php
// ✅ ADD AUTHENTICATION
require_once '../includes/auth.php';
requireRole(['admin', 'staff']); // Staff can view sales

require_once '../includes/db.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT s.*, c.name as client_name, c.phone as client_phone, u.name as cashier 
    FROM sales s
    JOIN clients c ON s.client_id = c.id
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) die('Sale not found');

$stmt = $pdo->prepare("
    SELECT si.*, p.name as product_name 
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$page_title = "Sale Details";
include '../includes/layout.php';
?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📄 Sale Details</h2>
        <a href="index.php" class="btn btn-secondary">← Back to Sales</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <table class="table table-bordered">
                <tr>
                    <th width="20%">Client:</th>
                    <td><?= htmlspecialchars($sale['client_name'] ?? 'Walk-in') ?> 
                        <?php if (!empty($sale['client_phone'])): ?>
                            (<small><?= htmlspecialchars(preg_replace('/(\d{4})\d+(\d{3})/', '$1***$2', $sale['client_phone'])) ?></small>)
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Cashier:</th>
                    <td><?= htmlspecialchars($sale['cashier']) ?></td>
                </tr>
                <tr>
                    <th>Date:</th>
                    <td><?= date('F j, Y \a\t g:i A', strtotime($sale['created_at'])) ?></td>
                </tr>
                <tr>
                    <th>Payment Method:</th>
                    <td>
                        <?php if ($sale['payment_method'] === 'Cash'): ?>
                            <span class="badge bg-success">💵 Cash</span>
                        <?php else: ?>
                            <span class="badge bg-primary">📱 M-Pesa</span>
                        <?php endif; ?>
                        <?php if (!empty($sale['mpesa_receipt'])): ?>
                            <br><small>Receipt: <?= htmlspecialchars($sale['mpesa_receipt']) ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($sale['payment_method'] === 'Cash'): ?>
                <tr>
                    <th>Cash Received:</th>
                    <td>KES <?= number_format($sale['cash_received'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <th>Change Given:</th>
                    <td>KES <?= number_format($sale['change_given'] ?? 0, 2) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">💰 Total</h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-primary">KES <?= number_format($sale['total_amount'], 2) ?></h2>
                    <p class="text-muted mb-0">Paid: <?= htmlspecialchars($sale['payment_status']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mt-4">🛒 Items</h4>
    <table class="table table-striped">
        <thead class="table-light">
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= $item['qty'] ?></td>
                <td>KES <?= number_format($item['price'], 2) ?></td>
                <td>KES <?= number_format($item['price'] * $item['qty'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="d-flex gap-2">
        <!-- ✅ PUBLIC RECEIPT LINK (NO AUTH NEEDED) -->
        <a href="/zaina-beauty/pos/receipt.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-info">
            <i class="fas fa-receipt"></i> View/Print Receipt
        </a>
 
    </div>
</div>

<?php include '../includes/layout_end.php'; ?>