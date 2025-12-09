<?php
require_once '../includes/auth.php';
requireRole(['admin','staff']);
// For now: just display shop info
?>
<?php include '../includes/layout.php'; ?>
<div class="container-fluid mt-4">
    <h2>⚙️ General Settings</h2>
    <div class="card p-4">
        <p><strong>Shop Name:</strong> <?= SHOP_NAME ?></p>
        <p><strong>Currency:</strong> <?= CURRENCY ?></p>
        <p><em>More settings coming soon...</em></p>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php'; ?>