<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);
require_once '../includes/db.php';

// Get tax settings
$stmt = $pdo->query("SELECT tax_rate, tax_name FROM settings LIMIT 1");
$tax = $stmt->fetch() ?: ['tax_rate' => 0.00, 'tax_name' => 'VAT'];

// Handle cash payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart = json_decode($_POST['cart'], true);
    $name = $_POST['name'] ?? 'Cash Customer';
    $phone = $_POST['phone'] ?? 'WALK-IN';
    $cash_received = floatval($_POST['cash_received']);
    
    if (empty($cart) || $cash_received <= 0) {
        die('Invalid input');
    }

    try {
        $pdo->beginTransaction();

        // Get or create client
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE name = ? AND phone = ?");
        $stmt->execute([$name, $phone]);
        $client = $stmt->fetch();
        $client_id = $client ? $client['id'] : null;

        if (!$client_id) {
            $stmt = $pdo->prepare("INSERT INTO clients (name, phone) VALUES (?, ?)");
            $stmt->execute([$name, $phone]);
            $client_id = $pdo->lastInsertId();
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }
        $tax_amount = $subtotal * ($tax['tax_rate'] / 100);
        $total = $subtotal + $tax_amount;
        $change = $cash_received - $total;

        // Generate receipt number
        $receipt_number = 'CASH-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Create sale
        $stmt = $pdo->prepare("
            INSERT INTO sales (client_id, user_id, total_amount, payment_status, payment_method, 
                   phone_for_payment, mpesa_receipt, cash_received, change_given) 
            VALUES (?, ?, ?, 'paid', 'Cash', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $client_id, 
            $_SESSION['user_id'], 
            $total, 
            $phone, 
            $receipt_number,
            $cash_received,
            $change
        ]);
        $sale_id = $pdo->lastInsertId();

        // Add sale items
        foreach ($cart as $item) {
            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sale_id, $item['id'], $item['qty'], $item['price']]);
        }

        $pdo->commit();
        header("Location: receipt.php?id=$sale_id");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// Show form
$page_title = "Cash Payment";
include '../includes/layout.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">💵 Cash Payment</h4>
                    <!-- ✅ BACK TO POS BUTTON -->
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to POS
                    </a>
                </div>
                <div class="card-body">
                    <div id="cartSummary"></div>
                    
                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer Name</label>
                            <input type="text" id="cashName" class="form-control" placeholder="e.g., Jane Doe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer Phone</label>
                            <input type="text" id="cashPhone" class="form-control" placeholder="e.g., 0712345678">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Cash Received (KES)</label>
                            <input type="number" id="cashReceived" class="form-control" min="0" step="0.01" placeholder="Enter amount">
                        </div>
                    </div>
                    
                    <div class="mt-3 p-3 bg-light rounded" id="paymentSummary">
                        <h5>Payment Summary</h5>
                        <p>Subtotal: <span id="subtotal">KES 0.00</span></p>
                        <p><?= htmlspecialchars($tax['tax_name']) ?> (<?= $tax['tax_rate'] ?>%): <span id="taxAmount">KES 0.00</span></p>
                        <p class="fw-bold">Total: <span id="totalAmount">KES 0.00</span></p>
                        <p>Cash Received: <span id="cashGiven">KES 0.00</span></p>
                        <p class="text-success fw-bold">Change: <span id="changeAmount">KES 0.00</span></p>
                    </div>
                    
                    <div class="d-flex gap-2 mt-3">
                        <!-- ✅ BACK TO POS BUTTON (Again at bottom) -->
                        <a href="index.php" class="btn btn-secondary flex-grow-1">
                            <i class="fas fa-arrow-left me-1"></i> Back to POS
                        </a>
                        <button class="btn btn-success flex-grow-1" id="processCashPayment">
                            💳 Process Cash Payment & Print Receipt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Get cart from localStorage
let cart = JSON.parse(localStorage.getItem('pos_cart') || '[]');
const taxRate = <?= $tax['tax_rate'] ?>;

// Calculate totals
function calculateTotals() {
    let subtotal = 0;
    cart.forEach(item => {
        subtotal += item.price * item.qty;
    });
    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + taxAmount;
    return { subtotal, taxAmount, total };
}

// Render cart summary
function renderCartSummary() {
    if (cart.length === 0) {
        document.getElementById('cartSummary').innerHTML = '<div class="alert alert-warning">Cart is empty</div>';
        return;
    }

    let html = '<h5>🛒 Items</h5><table class="table table-sm">';
    cart.forEach(item => {
        const itemTotal = item.price * item.qty;
        html += `
        <tr>
            <td>${item.name}</td>
            <td>${item.qty} x KES ${item.price.toFixed(2)}</td>
            <td class="text-end">KES ${itemTotal.toFixed(2)}</td>
        </tr>`;
    });
    html += '</table>';
    
    document.getElementById('cartSummary').innerHTML = html;
    updatePaymentSummary(0);
}

// Update payment summary
function updatePaymentSummary(cashReceived) {
    const { subtotal, taxAmount, total } = calculateTotals();
    const change = cashReceived - total;
    
    document.getElementById('subtotal').textContent = `KES ${subtotal.toFixed(2)}`;
    document.getElementById('taxAmount').textContent = `KES ${taxAmount.toFixed(2)}`;
    document.getElementById('totalAmount').textContent = `KES ${total.toFixed(2)}`;
    document.getElementById('cashGiven').textContent = `KES ${cashReceived.toFixed(2)}`;
    document.getElementById('changeAmount').textContent = `KES ${change.toFixed(2)}`;
    
    // Enable button only if cash received >= total
    const button = document.getElementById('processCashPayment');
    button.disabled = cashReceived < total;
    button.textContent = cashReceived < total ? '❌ Insufficient Cash' : '💳 Process Cash Payment & Print Receipt';
}

// Handle cash received input
document.getElementById('cashReceived').addEventListener('input', function() {
    const cash = parseFloat(this.value) || 0;
    updatePaymentSummary(cash);
});

// Process payment
document.getElementById('processCashPayment').addEventListener('click', function() {
    const name = document.getElementById('cashName').value || 'Cash Customer';
    const phone = document.getElementById('cashPhone').value || 'WALK-IN';
    const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
    const { total } = calculateTotals();
    
    if (cashReceived < total) {
        alert('Insufficient cash provided!');
        return;
    }
    
    fetch('/zaina-beauty/pos/cash_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `cart=${encodeURIComponent(JSON.stringify(cart))}&name=${encodeURIComponent(name)}&phone=${encodeURIComponent(phone)}&cash_received=${cashReceived}`
    })
    .then(res => {
        if (res.redirected) {
            window.location = res.url;
        } else {
            return res.text().then(text => { throw new Error(text); });
        }
    })
    .catch(err => {
        alert('Error: ' + err.message);
    });
});

// Initialize
renderCartSummary();
</script>

<?php include '../includes/layout_end.php'; ?>