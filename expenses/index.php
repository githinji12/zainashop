<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

$stmt = $pdo->query("SELECT * FROM expenses ORDER BY date DESC");
$expenses = $stmt->fetchAll();
$total = 0;
foreach ($expenses as $e) $total += $e['amount'];
?>
<?php include '../includes/layout.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>💰 Expenses</h2>
        <a href="add.php" class="btn btn-success">+ Add Expense</a>
    </div>
    <p><strong>Total Spent:</strong> KES <?= number_format($total, 2) ?></p>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Category</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $e): ?>
            <tr>
                <td><?= htmlspecialchars($e['date']) ?></td>
                <td><?= htmlspecialchars($e['description']) ?></td>
                <td><?= htmlspecialchars($e['category']) ?></td>
                <td>KES <?= number_format($e['amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php';?>