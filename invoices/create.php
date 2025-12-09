<?php
require_once '../includes/auth.php';
// Only admin & staff can create invoices
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

// Fetch products for selection
$search = trim($_GET['search'] ?? '');
$sql = "SELECT id, name, selling_price FROM products WHERE type = 'product' AND stock_qty > 0";
$params = [];
if ($search) {
    $sql .= " AND name LIKE ?";
    $params[] = "%{$search}%";
}
$sql .= " ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>


<?php include '../includes/layout.php'; ?>

 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.invoice-item { border-bottom: 1px solid #eee; padding: 10px 0; }
</style>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice text-primary me-2"></i> Create New Invoice</h2>
        <a href="../dashboard/" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="row">
        <!-- Product Selection -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Available Products</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search products..." 
                                   value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                    
                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($products)): ?>
                            <p class="text-muted">No products in stock.</p>
                        <?php else: ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['name']) ?></td>
                                            <td>KES <?= number_format($p['selling_price'], 2) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-success add-item"
                                                        data-id="<?= $p['id'] ?>"
                                                        data-name="<?= htmlspecialchars($p['name']) ?>"
                                                        data-price="<?= $p['selling_price'] ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Preview -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Invoice Preview</h6>
                    <button id="printInvoice" class="btn btn-primary btn-sm">
                        <i class="fas fa-print me-1"></i> Print Invoice
                    </button>
                </div>
                <div class="card-body">
                    <div id="invoiceContent">
                        <div class="text-center mb-3">
                            <img src="/zaina-beauty/assets/img/logo.png" alt="Zaina's Beauty" width="120" class="mb-2">
                            <h5>Zaina's Beauty Shop</h5>
                            <p class="text-muted mb-1">Nairobi, Kenya</p>
                            <p class="text-muted mb-3">Invoice #<?= 'INV-' . date('ymd') . rand(100, 999) ?></p>
                            <p class="mb-1">Date: <?= date('F j, Y') ?></p>
                            <p class="mb-3">Staff: <?= htmlspecialchars($_SESSION['user_name']) ?></p>
                        </div>

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="invoiceItems">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No items added</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">TOTAL:</th>
                                    <th colspan="2" id="invoiceTotal">KES 0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form to collect invoice data for saving (optional) -->
<form id="invoiceForm" method="POST" action="save.php" style="display:none;">
    <input type="hidden" name="items" id="invoiceData">
</form>

<script>
let invoiceItems = [];

function updateInvoice() {
    const tbody = document.getElementById('invoiceItems');
    const totalEl = document.getElementById('invoiceTotal');
    
    if (invoiceItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No items added</td></tr>';
        totalEl.textContent = 'KES 0.00';
        return;
    }

    let total = 0;
    let rows = '';
    invoiceItems.forEach((item, index) => {
        const itemTotal = item.price * item.qty;
        total += itemTotal;
        rows += `
            <tr>
                <td>${item.name}</td>
                <td>${item.qty}</td>
                <td>KES ${parseFloat(item.price).toFixed(2)}</td>
                <td>KES ${itemTotal.toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-danger remove-item" data-index="${index}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = rows;
    totalEl.textContent = 'KES ' + total.toFixed(2);
    
    // Update hidden form
    document.getElementById('invoiceData').value = JSON.stringify(invoiceItems);
}

document.querySelectorAll('.add-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const price = parseFloat(this.dataset.price);
        
        // Check if already exists
        const existing = invoiceItems.find(item => item.id == id);
        if (existing) {
            existing.qty += 1;
        } else {
            invoiceItems.push({ id, name, price, qty: 1 });
        }
        updateInvoice();
    });
});

document.getElementById('invoiceItems').addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        const index = e.target.closest('.remove-item').dataset.index;
        invoiceItems.splice(index, 1);
        updateInvoice();
    }
});

document.getElementById('printInvoice').addEventListener('click', function() {
    if (invoiceItems.length === 0) {
        alert('Please add at least one item to the invoice.');
        return;
    }
    
    const printContent = document.getElementById('invoiceContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invoice - Zaina's Beauty</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; }
                .text-center { text-align: center; }
                .text-end { text-align: right; }
                .mb-1 { margin-bottom: 0.25rem; }
                .mb-2 { margin-bottom: 0.5rem; }
                .mb-3 { margin-bottom: 1rem; }
            </style>
        </head>
        <body>
            ${printContent}
            <div class="text-center mt-4">
                <p>Thank you for your business!</p>
                <p>Signature: ___________________</p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
});
</script>

<?php include '../includes/layout_end.php'; ?>