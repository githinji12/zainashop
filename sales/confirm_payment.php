<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']); // Only staff can confirm

$page_title = "Confirm Payment";
include '../includes/layout.php';

$message = '';
$error = '';

if ($_POST) {
    $mpesaReceipt = trim($_POST['mpesa_receipt'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (!$mpesaReceipt || !$phone) {
        $error = "Both M-Pesa Receipt Number and Phone are required.";
    } else {
        try {
            // Find pending sale matching phone and unpaid
            $stmt = $pdo->prepare("
                SELECT id, total_amount, phone_for_payment 
                FROM sales 
                WHERE phone_for_payment LIKE ? 
                AND payment_status = 'pending'
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            // Handle both 07... and 254... formats
            $phoneLike = '%' . ltrim($phone, '0') . '%';
            $stmt->execute([$phoneLike]);
            $sale = $stmt->fetch();

            if ($sale) {
                // Mark as paid
                $stmt = $pdo->prepare("
                    UPDATE sales 
                    SET payment_status = 'paid', mpesa_receipt = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$mpesaReceipt, $sale['id']]);
                
                $message = "✅ Payment confirmed! Sale #{$sale['id']} is now paid.";
                // Auto-redirect to receipt
                $receiptUrl = "/zaina-beauty/pos/receipt.php?id={$sale['id']}";
                echo "<script>window.open('$receiptUrl', '_blank');</script>";
            } else {
                $error = "❌ No pending sale found for phone: $phone";
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>📱 Manual Payment Confirmation</h4>
                    <p class="text-muted">Use when M-Pesa payment succeeded but system shows 'pending'</p>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">M-Pesa Receipt Number</label>
                            <input type="text" name="mpesa_receipt" class="form-control" 
                                   placeholder="e.g., TK4QA98IDY" required>
                            <div class="form-text">From customer's SMS</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Customer Phone Number</label>
                            <input type="text" name="phone" class="form-control" 
                                   placeholder="e.g., 0712345678" required>
                            <div class="form-text">Phone used for payment</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            ✅ Confirm Payment & Generate Receipt
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-4">
                <h5>📋 How to Use:</h5>
                <ol>
                    <li>Ask customer for <strong>M-Pesa SMS</strong></li>
                    <li>Get <strong>Mpesa Code</strong> (e.g., <code>TK4QA98IDY</code>)</li>
                    <li>Get <strong>Phone Number</strong> used for payment</li>
                    <li>Enter both above → Click <strong>Confirm</strong></li>
                    <li>Receipt will <strong>auto-open in new tab</strong></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/layout_end.php'; ?>