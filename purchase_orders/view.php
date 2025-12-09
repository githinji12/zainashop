<?php
require_once '../includes/auth.php';
requireRole(['admin','staff']);
require_once '../includes/db.php';

$po_id = (int)($_GET['id'] ?? 0);
if ($po_id <= 0) die('Invalid PO ID.');

$stmt = $pdo->prepare("
    SELECT po.*, u.name AS created_by 
    FROM purchase_orders po
    LEFT JOIN users u ON po.created_by = u.id
    WHERE po.id = ?
");
$stmt->execute([$po_id]);
$po = $stmt->fetch();

if (!$po) die('PO not found.');

$stmt = $pdo->prepare("SELECT * FROM po_items WHERE po_id = ? ORDER BY id");
$stmt->execute([$po_id]);
$items = $stmt->fetchAll();
?>

<?php include '../includes/layout.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Print styles */
@media print {
    .no-print, 
    .sidebar, 
    .top-bar,
    .main-content > .container-fluid > .d-flex,
    .alert {
        display: none !important;
    }
    body, .main-content {
        margin: 0;
        padding: 0;
        background: white !important;
    }
    .container {
        max-width: 800px !important;
        margin: 0 auto !important;
        padding: 20px !important;
    }
    body {
        font-size: 12px;
        color: black;
    }
    .table {
        border: 1px solid #000 !important;
    }
    .table th, .table td {
        border: 1px solid #000 !important;
        padding: 6px !important;
    }
    /* Ensure logo prints in black (not affected by color settings) */
    .po-header img {
        filter: none !important;
        -webkit-filter: none !important;
    }
}

.po-header {
    border-bottom: 3px solid #2c3e50;
    padding-bottom: 15px;
    margin-bottom: 20px;
    text-align: center;
}
.po-header img {
    height: 60px;
    margin-bottom: 10px;
    /* Fallback if image fails to load */
    background: #f8f9fa;
    padding: 5px;
    border-radius: 4px;
}
</style>

<div class="container mt-4">
    <!-- Action Buttons -->
    <div class="d-flex justify-content-between align-items-center no-print mb-4">
        <h2><i class="fas fa-file-alt me-2"></i> Purchase Order</h2>
        
        <?php if ($po['status'] === 'sent'): ?>
            <a href="receive.php?id=<?= $po['id'] ?>" class="btn btn-success">
                <i class="fas fa-warehouse me-1"></i> Receive Goods
            </a>
        <?php endif; ?>
        
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="fas fa-print me-1"></i> Print Purchase Order
        </button>
    </div>

    <!-- Printable PO Content -->
    <div id="poContent">
        <!-- ✅ LOGO SECTION - Optimized for Print -->
        <div class="po-header">
            <?php
            // Use absolute URL for reliable printing
            $logo_url = '/zaina-beauty/assets/img/logo.png';
            $full_logo_path = $_SERVER['DOCUMENT_ROOT'] . $logo_url;
            ?>
            <?php if (file_exists($full_logo_path)): ?>
                <!-- Use absolute URL so it works when printing -->
                <img src="<?= $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $logo_url ?>" 
                     alt="Zaina's Beauty Logo" 
                     onerror="this.style.display='none';">
            <?php else: ?>
                <!-- Fallback if logo missing -->
                <div style="font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 10px;">
                    ZAINA'S BEAUTY SHOP
                </div>
            <?php endif; ?>
            
            <h3>ZAINA'S BEAUTY SHOP</h3>
            <p class="mb-1">Nairobi, Kenya</p>
            <p class="mb-1">Phone: +254 XXX XXX XXX | Email: info@zainasbeauty.co.ke</p>
        </div>

        <!-- PO Details -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>TO: <?= htmlspecialchars($po['supplier_name']) ?></h5>
            </div>
            <div class="col-md-6 text-md-end">
                <p><strong>PO Number:</strong> <?= htmlspecialchars($po['po_number']) ?></p>
                <p><strong>Date:</strong> <?= date('F j, Y', strtotime($po['created_at'])) ?></p>
                <?php if ($po['expected_delivery']): ?>
                    <p><strong>Expected Delivery:</strong> <?= date('F j, Y', strtotime($po['expected_delivery'])) ?></p>
                <?php endif; ?>
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?= $po['status'] === 'received' ? 'success' : ($po['status'] === 'sent' ? 'warning' : 'secondary') ?>">
                        <?= ucfirst($po['status']) ?>
                    </span>
                </p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th width="50%">Item Description</th>
                    <th>Quantity</th>
                    <th>Unit Cost (KES)</th>
                    <th>Total (KES)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="4" class="text-center">No items</td></tr>
                <?php else: 
                    $grand_total = 0;
                    foreach ($items as $item): 
                        $line_total = (float)$item['cost_price'] * (int)$item['quantity'];
                        $grand_total += $line_total;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td><?= number_format((float)$item['cost_price'], 2) ?></td>
                        <td><?= number_format($line_total, 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">GRAND TOTAL:</th>
                    <th>KES <?= number_format($grand_total, 2) ?></th>
                </tr>
            </tfoot>
        </table>

        <!-- Notes -->
        <div class="mt-4">
            <p><strong>Notes:</strong></p>
            <ul>
                <li>Please deliver goods as per the expected date.</li>
                <li>Quote PO number <?= htmlspecialchars($po['po_number']) ?> on all correspondence.</li>
                <li>This order is subject to Zaina’s Beauty Shop terms and conditions.</li>
            </ul>
        </div>

        <!-- Signature -->
        <div class="row mt-5">
            <div class="col-md-6">
                <p>_________________________<br>Authorized Signature</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p>Prepared by: <?= htmlspecialchars($po['created_by'] ?? 'Admin') ?><br>
                Date: <?= date('F j, Y') ?></p>
            </div>
        </div>

        <hr class="no-print">
        <p class="text-center text-muted no-print">
            <em>This is a purchase order, not a tax invoice.</em>
        </p>
    </div>
</div>
<?php include '../includes/footer.php';?>
<?php include '../includes/layout_end.php'; ?>