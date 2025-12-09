<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

// Filter by date
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Fetch cycle counts
$stmt = $pdo->prepare("
    SELECT 
        ic.*,
        p.name AS product_name,
        u.name AS user_name
    FROM inventory_counts ic
    JOIN products p ON ic.product_id = p.id
    JOIN users u ON ic.user_id = u.id
    WHERE DATE(ic.created_at) BETWEEN ? AND ?
    ORDER BY ic.created_at DESC
");
$stmt->execute([$start_date, $end_date]);
$counts = $stmt->fetchAll();

// Calculate summary
$total_items = count($counts);
$mismatches = 0;
$total_variance = 0;

foreach ($counts as $c) {
    if ($c['variance'] != 0) $mismatches++;
    $total_variance += $c['variance'];
}

$page_title = "Cycle Count Report";
include '../includes/layout.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clipboard-list me-2"></i> Cycle Count Report</h2>
        <a href="../stock/cycle-count.php" class="btn btn-outline-primary">
            <i class="fas fa-plus me-1"></i> New Count
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                </div>
                <div class="col-md-4">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5>Total Items Counted</h5>
                    <h3><?= $total_items ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h5>Mismatches</h5>
                    <h3><?= $mismatches ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?= $total_variance >= 0 ? 'bg-success' : 'bg-danger' ?> text-white">
                <div class="card-body text-center">
                    <h5>Net Variance</h5>
                    <h3><?= $total_variance >= 0 ? '+' : '' ?><?= $total_variance ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="d-grid h-100">
                <a href="export-cycle-counts.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
                   class="btn btn-outline-secondary h-100">
                    <i class="fas fa-file-export me-2"></i> Export CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Table -->
    <?php if (empty($counts)): ?>
        <div class="alert alert-info text-center">No cycle counts found for this period.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Expected</th>
                        <th>Actual</th>
                        <th>Variance</th>
                        <th>Staff</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($counts as $c): ?>
                        <tr>
                            <td><?= date('M j, Y g:i A', strtotime($c['created_at'])) ?></td>
                            <td><?= htmlspecialchars($c['product_name']) ?></td>
                            <td><?= $c['expected_count'] ?></td>
                            <td><?= $c['actual_count'] ?></td>
                            <td class="<?= $c['variance'] == 0 ? 'text-success' : ($c['variance'] > 0 ? 'text-primary' : 'text-danger') ?>">
                                <?= $c['variance'] == 0 ? '0' : ($c['variance'] > 0 ? '+' : '') . $c['variance'] ?>
                            </td>
                            <td><?= htmlspecialchars($c['user_name']) ?></td>
                            <td>
                                <?php if ($c['variance'] == 0): ?>
                                    <span class="badge bg-success">Match</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Mismatch</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/layout_end.php'; ?>