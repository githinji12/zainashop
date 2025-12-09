<?php
require_once '../includes/auth.php';
requireRole(['admin']);
require_once '../includes/db.php';

// Fetch all suppliers
$stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name");
$suppliers = $stmt->fetchAll();
?>

<?php include '../includes/layout.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-handshake text-primary me-2"></i>
            Supplier Management
        </h2>
        <a href="add.php" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Add Supplier
        </a>
    </div>

    <?php if (empty($suppliers)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="fas fa-inbox fa-2x mb-2 text-muted"></i><br>
            No suppliers found. 
            <a href="add.php" class="alert-link">Add your first supplier</a>.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Supplier</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>On-Time Delivery</th>
                        <th>Total Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= htmlspecialchars($s['contact_person'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                            <td>
                                <?php if (!empty($s['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($s['email']) ?>"><?= htmlspecialchars($s['email']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $s['on_time_delivery_rate'] >= 90 ? 'success' : ($s['on_time_delivery_rate'] >= 70 ? 'warning' : 'danger') ?>">
                                    <?= number_format($s['on_time_delivery_rate'], 1) ?>%
                                </span>
                            </td>
                            <td><?= (int)$s['total_orders'] ?></td>
                            <td>
                                <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?= $s['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   title="Delete"
                                   onclick="return confirm('Delete this supplier? All linked POs will remain.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/layout_end.php'; ?>