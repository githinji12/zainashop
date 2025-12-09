<?php
require_once '../includes/auth.php';
requireRole(['admin']);
?>
<?php include '../includes/layout.php'; ?>
<div class="container-fluid mt-4">
    <h2>💳 Mobile Payment Settings</h2>
    <div class="card p-4">
        <h5>Currently Simulated</h5>
        <p>When you click "Checkout" in POS, the system simulates an M-Pesa prompt to the client's phone.</p>
        <p>To go live, integrate with Daraja API (contact developer).</p>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php'; ?>