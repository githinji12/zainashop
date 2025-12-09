<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

$stmt = $pdo->query("
    SELECT * FROM audit_logs 
    ORDER BY created_at DESC 
    LIMIT 200
");
$logs = $stmt->fetchAll();
?>

<?php include '../includes/layout.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-shield-alt"></i> Security Audit Trail</h2>
    </div>

    <?php if (empty($logs)): ?>
        <div class="alert alert-info">No security events logged yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="<?= in_array($log['action'], ['delete_product', 'delete_sale']) ? 'table-danger' : '' ?>">
                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                        <td><?= htmlspecialchars($log['username']) ?></td>
                        <td>
                            <span class="badge <?= strpos($log['action'], 'delete') !== false ? 'bg-danger' : 'bg-warning' ?>">
                                <?= htmlspecialchars(str_replace('_', ' ', $log['action'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" 
                                    onclick="showDetails(<?= htmlspecialchars(json_encode($log['old_values'])) ?>)">
                                View Data
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function showDetails(data) {
    alert("Deleted/Changed Data:\n\n" + JSON.stringify(JSON.parse(data), null, 2));
}
</script>

<?php include '../includes/layout_end.php'; ?>