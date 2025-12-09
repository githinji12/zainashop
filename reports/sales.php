<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

$stmt = $pdo->query("
    SELECT s.*, u.name as cashier, c.name as client 
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN clients c ON s.client_id = c.id
    ORDER BY s.created_at DESC
    LIMIT 50
");
$sales = $stmt->fetchAll();
?>
<?php include '../includes/layout.php'; ?>
<div class="container-fluid mt-4">
    <h2>📈 Sales Report</h2>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Cashier</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['created_at']) ?></td>
                <td><?= htmlspecialchars($s['client'] ?? 'Walk-in') ?></td>
                <td><?= htmlspecialchars($s['cashier']) ?></td>
                <td>KES <?= number_format($s['total_amount'], 2) ?></td>
                <td><a href="/zaina-beauty/sales/view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info">View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php';?>