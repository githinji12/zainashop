<?php
require_once '../includes/auth.php';
requireRole(['admin','staff']);
require_once '../includes/db.php';

// Fetch suppliers
$stmt = $pdo->query("SELECT id, name FROM suppliers ORDER BY name");
$suppliers = $stmt->fetchAll();

// Fetch existing products (for autocomplete + auto-price)
$stmt = $pdo->query("SELECT id, name, cost_price FROM products WHERE type = 'product' ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$products_json = json_encode($products);

$error = '';
$success = false;
$po_number = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = (int)($_POST['supplier_id'] ?? 0);
    $delivery_date = trim($_POST['delivery_date'] ?? '');
    $items = $_POST['items'] ?? [];

    if ($supplier_id <= 0) {
        $error = "Please select a supplier.";
    } elseif (empty($items)) {
        $error = "Please add at least one item to the purchase order.";
    } else {
        $valid_items = [];
        $total_amount = 0;

        foreach ($items as $item) {
            $name = trim($item['name'] ?? '');
            $quantity = (int)($item['quantity'] ?? 0);
            $cost_price = floatval($item['cost_price'] ?? 0);

            if (empty($name)) continue;
            if ($quantity <= 0) {
                $error = "Quantity must be greater than zero for item: " . htmlspecialchars($name);
                break;
            }
            // Cost price can be 0 (for new products)

            $product_id = null;
            foreach ($products as $p) {
                if (strtolower($p['name']) === strtolower($name)) {
                    $product_id = $p['id'];
                    $cost_price = (float)$p['cost_price']; // Enforce correct price
                    break;
                }
            }

            $line_total = $quantity * $cost_price;
            $total_amount += $line_total;

            $valid_items[] = [
                'product_id' => $product_id,
                'product_name' => $name,
                'cost_price' => $cost_price,
                'quantity' => $quantity,
                'total' => $line_total
            ];
        }

        if (!$error && !empty($valid_items)) {
            try {
                $po_number = 'PO-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
                $pdo->beginTransaction();

                $supp = array_column($suppliers, 'name', 'id');
                $supplier_name = $supp[$supplier_id] ?? 'Unknown Supplier';

                $stmt = $pdo->prepare("
                    INSERT INTO purchase_orders (po_number, supplier_id, supplier_name, total_amount, expected_delivery, created_by, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'sent')
                ");
                $stmt->execute([
                    $po_number,
                    $supplier_id,
                    $supplier_name,
                    $total_amount,
                    $delivery_date ?: null,
                    $_SESSION['user_id']
                ]);
                $po_id = $pdo->lastInsertId();

                $stmt = $pdo->prepare("
                    INSERT INTO po_items (po_id, product_id, product_name, cost_price, quantity, total)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                foreach ($valid_items as $item) {
                    $stmt->execute([
                        $po_id,
                        $item['product_id'],
                        $item['product_name'],
                        $item['cost_price'],
                        $item['quantity'],
                        $item['total']
                    ]);
                }

                $pdo->commit();
                $success = true;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Failed to save purchase order. Please try again.";
                error_log("PO Create Error: " . $e->getMessage());
            }
        }
    }
}
?>

<?php include '../includes/layout.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-plus-circle text-success me-2"></i>
            Create Purchase Order
        </h2>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Orders
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            Purchase Order <strong><?= htmlspecialchars($po_number) ?></strong> created successfully!
            <a href="view.php?id=<?= $po_id ?>" class="alert-link">View PO</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form id="poForm" method="POST">
        <!-- Supplier & Delivery -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-truck me-2"></i> Supplier Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-control" required>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= (int)($_POST['supplier_id'] ?? 0) === $s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expected Delivery Date</label>
                        <input type="date" name="delivery_date" class="form-control" 
                               value="<?= htmlspecialchars($_POST['delivery_date'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i> Items to Order</h6>
                <button type="button" class="btn btn-sm btn-light" onclick="addPOItem()">
                    <i class="fas fa-plus me-1"></i> Add Item
                </button>
            </div>
            <div class="card-body">
                <div id="poItems">
                    <?php if (!empty($_POST['items'])): ?>
                        <?php foreach ($_POST['items'] as $idx => $item): 
                            // Auto-resolve cost if product exists
                            $resolved_cost = 0;
                            foreach ($products as $p) {
                                if (strtolower($p['name']) === strtolower($item['name'] ?? '')) {
                                    $resolved_cost = (float)$p['cost_price'];
                                    break;
                                }
                            }
                            $cost_to_use = $resolved_cost ?: (float)($item['cost_price'] ?? 0);
                            $qty = (int)($item['quantity'] ?? 1);
                        ?>
                            <div class="row mb-3 p-2 border rounded po-item">
                                <div class="col-md-6">
                                    <input type="text" name="items[<?= $idx ?>][name]" class="form-control product-name" 
                                           placeholder="Product name" value="<?= htmlspecialchars($item['name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="items[<?= $idx ?>][quantity]" class="form-control quantity" 
                                           placeholder="Qty" min="1" value="<?= $qty ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control line-total" placeholder="Total" readonly 
                                           value="<?= number_format($cost_to_use * $qty, 2) ?>">
                                    <!-- Hidden cost field -->
                                    <input type="hidden" name="items[<?= $idx ?>][cost_price]" value="<?= $cost_to_use ?>">
                                </div>
                                <div class="col-md-1 d-flex align-items-center">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                            Click "Add Item" to start your order.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="card mb-4">
            <div class="card-body text-end">
                <h5>Grand Total: <span id="grandTotal">KES 0.00</span></h5>
            </div>
        </div>

        <!-- Submit -->
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Cancel</a>
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save me-2"></i> Save & Send Purchase Order
            </button>
        </div>
    </form>
</div>

<script>
const products = <?= $products_json ?>;
let itemCount = <?= !empty($_POST['items']) ? count($_POST['items']) : 0 ?>;

function updateTotals() {
    let grandTotal = 0;
    document.querySelectorAll('.po-item').forEach(row => {
        const qty = parseFloat(row.querySelector('.quantity').value) || 0;
        const costInput = row.querySelector('.cost-price-hidden');
        const cost = costInput ? parseFloat(costInput.value) : 0;
        const total = qty * cost;
        row.querySelector('.line-total').value = total.toFixed(2);
        grandTotal += total;
    });
    document.getElementById('grandTotal').textContent = 'KES ' + grandTotal.toFixed(2);
}

function addPOItem(name = '', qty = 1) {
    // Resolve cost from product list
    let cost = 0;
    const productMatch = products.find(p => p.name.toLowerCase() === name.toLowerCase());
    if (productMatch) cost = parseFloat(productMatch.cost_price);

    const container = document.getElementById('poItems');
    const itemHTML = `
        <div class="row mb-3 p-2 border rounded po-item">
            <div class="col-md-6">
                <input type="text" name="items[${itemCount}][name]" class="form-control product-name" 
                       placeholder="Product name" value="${name}" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="items[${itemCount}][quantity]" class="form-control quantity" 
                       placeholder="Qty" min="1" value="${qty}" required>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control line-total" placeholder="Total" readonly value="${(cost * qty).toFixed(2)}">
                <input type="hidden" name="items[${itemCount}][cost_price]" class="cost-price-hidden" value="${cost}">
            </div>
            <div class="col-md-1 d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.innerHTML = container.innerHTML.replace(/Click "Add Item".*?<\/div>/s, '') + itemHTML;
    itemCount++;
    attachAutocomplete();
    updateTotals();
}

function removeItem(button) {
    button.closest('.po-item').remove();
    updateTotals();
}

function attachAutocomplete() {
    document.querySelectorAll('.product-name').forEach(input => {
        if (input.dataset.autocomplete) return;
        input.dataset.autocomplete = 'true';
        
        input.addEventListener('input', function() {
            const name = this.value;
            const row = this.closest('.po-item');
            const costInput = row.querySelector('.cost-price-hidden');
            const totalInput = row.querySelector('.line-total');
            
            const product = products.find(p => p.name.toLowerCase() === name.toLowerCase());
            if (product) {
                const cost = parseFloat(product.cost_price);
                costInput.value = cost;
                const qty = parseFloat(row.querySelector('.quantity').value) || 0;
                totalInput.value = (qty * cost).toFixed(2);
                updateTotals();
            } else {
                // New product — cost = 0
                costInput.value = 0;
                totalInput.value = '0.00';
                updateTotals();
            }
        });
    });
}

// Initialize
attachAutocomplete();
updateTotals();
</script>

<?php include '../includes/layout_end.php'; ?>