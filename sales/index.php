<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

// Get filter inputs
$client_filter = trim($_GET['client'] ?? '');
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build base query
$sql = "
    SELECT s.id, s.total_amount, s.created_at, c.name as client 
    FROM sales s
    LEFT JOIN clients c ON s.client_id = c.id
    WHERE 1=1
";

$params = [];

// Add client filter (case-insensitive partial match)
if ($client_filter !== '') {
    $sql .= " AND (c.name LIKE ? OR c.phone LIKE ?)";
    $params[] = "%{$client_filter}%";
    $params[] = "%{$client_filter}%";
}

// Add date range filter
if ($date_from !== '') {
    $sql .= " AND DATE(s.created_at) >= ?";
    $params[] = $date_from;
}
if ($date_to !== '') {
    $sql .= " AND DATE(s.created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();
?>
<?php include '../includes/layout.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🛍️ Sales History</h2>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Filter Sales</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Client Name or Phone</label>
                    <input type="text" name="client" class="form-control" 
                           value="<?= htmlspecialchars($client_filter) ?>" 
                           placeholder="Search client...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" 
                           value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" 
                           value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </form>
            <?php if ($client_filter || $date_from || $date_to): ?>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Results Count -->
    <div class="mb-3">
        <strong><?= count($sales) ?></strong> sales found
        <?php if ($client_filter || $date_from || $date_to): ?>
            <span class="text-muted">(filtered)</span>
        <?php endif; ?>
    </div>

    <!-- Sales Table -->
    <?php if (empty($sales)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No sales found matching your criteria.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Date & Time</th>
                        <th>Client</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $s): ?>
                    <tr>
                        <td>
                            <i class="fas fa-calendar"></i> 
                            <?= date('M j, Y', strtotime($s['created_at'])) ?><br>
                            <small class="text-muted"><?= date('g:i A', strtotime($s['created_at'])) ?></small>
                        </td>
                        <td>
                            <?php if ($s['client']): ?>
                                <i class="fas fa-user"></i> <?= htmlspecialchars($s['client']) ?>
                            <?php else: ?>
                                <span class="text-muted">Walk-in Customer</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-success">KES <?= number_format($s['total_amount'], 2) ?></span>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info" target="_blank">
                                <i class="fas fa-receipt"></i> View Receipt
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php'; ?>