<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);
$page_title = "Dashboard";
include '../includes/layout.php';

$role = $_SESSION['user_role'];
$today = date('Y-m-d');
$user_name = $_SESSION['user_name'] ?? 'User';

// Initialize variables
$stats = [
    'today_sales' => 0,
    'total_products' => 0,
    'low_stock_count' => 0,
    'pending_tasks' => 0,
    'todays_appointments' => 0
];

// Fetch data based on role
if (in_array($role, ['admin', 'staff'])) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $stats['today_sales'] = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE type = 'product'");
    $stmt->execute();
    $stats['total_products'] = (int)$stmt->fetchColumn();

    // Low stock = 5 or fewer units
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE type = 'product' AND stock_qty <= 5 AND stock_qty > 0");
    $stmt->execute();
    $stats['low_stock_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_tasks'] = (int)$stmt->fetchColumn();
} 
// ✅ NEW: Also fetch low stock for employees
elseif ($role === 'employee') {
    // Fetch low stock count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE type = 'product' AND stock_qty <= 5 AND stock_qty > 0");
    $stmt->execute();
    $stats['low_stock_count'] = (int)$stmt->fetchColumn();

    // Fetch employee's tasks
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['pending_tasks'] = (int)$stmt->fetchColumn();
}

// Get user initials for avatar
$initials = '';
if ($user_name) {
    $names = explode(' ', $user_name);
    $initials = strtoupper(substr($names[0], 0, 1));
    if (isset($names[1])) {
        $initials .= strtoupper(substr($names[1], 0, 1));
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-3">
    <!-- PROFESSIONAL WELCOME HEADER -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex align-items-center">
                <!-- Avatar -->
                <div class="me-3">
                    <div class="avatar avatar-lg">
                        <span class="avatar-title rounded-circle bg-primary text-white fs-4">
                            <?= htmlspecialchars($initials) ?>
                        </span>
                    </div>
                </div>
                <!-- Greeting -->
                <div>
                    <h4 class="mb-1">Welcome back, <span class="text-primary"><?= htmlspecialchars($user_name) ?></span>!</h4>
                    <div class="d-flex align-items-center">
                        <span class="badge 
                            <?= $role === 'admin' ? 'bg-danger' : 
                                ($role === 'staff' ? 'bg-success' : 'bg-info') ?> 
                            text-white">
                            <?= ucfirst($role) ?>
                        </span>
                        <small class="text-muted ms-2">Last login: Today</small>
                    </div>
                </div>
                <!-- Live Clock (Right-aligned) -->
                <div class="ms-auto text-end">
                    <div id="liveDate" class="text-muted small"></div>
                    <div id="liveTime" class="fs-4 fw-bold text-primary"></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (in_array($role, ['admin', 'staff'])): ?>
        <!-- PROFESSIONAL DASHBOARD FOR ADMIN/STAFF -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-start border-success border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Today's Sales</h6>
                                <h4 class="mb-0 text-success">KES <?= number_format($stats['today_sales'], 2) ?></h4>
                            </div>
                            <div class="icon-circle bg-success text-white">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-start border-primary border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Products</h6>
                                <h4 class="mb-0 text-primary"><?= $stats['total_products'] ?></h4>
                            </div>
                            <div class="icon-circle bg-primary text-white">
                                <i class="fas fa-boxes"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-start <?= $stats['low_stock_count'] > 0 ? 'border-danger' : 'border-warning' ?> border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Low Stock Items</h6>
                                <h4 class="mb-0 <?= $stats['low_stock_count'] > 0 ? 'text-danger' : 'text-warning' ?>">
                                    <?= $stats['low_stock_count'] ?>
                                </h4>
                            </div>
                            <div class="icon-circle <?= $stats['low_stock_count'] > 0 ? 'bg-danger' : 'bg-warning' ?> text-white">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-start border-info border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Pending Tasks</h6>
                                <h4 class="mb-0 text-info"><?= $stats['pending_tasks'] ?></h4>
                            </div>
                            <div class="icon-circle bg-info text-white">
                                <i class="fas fa-tasks"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-bolt me-2"></i> Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="/zaina-beauty/pos/" class="btn btn-success">
                        <i class="fas fa-cash-register me-1"></i> New Sale
                    </a>
                    <a href="/zaina-beauty/products/" class="btn btn-primary">
                        <i class="fas fa-box me-1"></i> Manage Products
                    </a>
                    <a href="/zaina-beauty/purchase_orders/create.php" class="btn btn-warning">
                        <i class="fas fa-shopping-cart me-1"></i> Create PO
                    </a>
                    <a href="/zaina-beauty/stock/adjust.php" class="btn btn-secondary">
                        <i class="fas fa-warehouse me-1"></i> Adjust Stock
                    </a>
                    <a href="/zaina-beauty/tasks/" class="btn btn-info">
                        <i class="fas fa-tasks me-1"></i> View Tasks
                    </a>
                </div>
            </div>
        </div>

        <?php if ($stats['low_stock_count'] > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h6 class="alert-heading">
                    <i class="fas fa-exclamation-triangle me-2"></i> Low Stock Alert
                </h6>
                <p><?= $stats['low_stock_count'] ?> product(s) are running low (≤ 5 units). Restock soon to avoid shortages.</p>
                <a href="/zaina-beauty/stock/" class="btn btn-warning btn-sm">View Stock</a>
                <a href="/zaina-beauty/purchase_orders/create.php" class="btn btn-success btn-sm">Create PO</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- EMPLOYEE DASHBOARD -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-start border-info border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Your Pending Tasks</h6>
                                <h4 class="mb-0 text-info"><?= $stats['pending_tasks'] ?></h4>
                            </div>
                            <div class="icon-circle bg-info text-white">
                                <i class="fas fa-tasks"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ NEW: Low Stock Card for Employee -->
            <div class="col-md-6">
                <div class="card border-start <?= $stats['low_stock_count'] > 0 ? 'border-danger' : 'border-warning' ?> border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Low Stock Items</h6>
                                <h4 class="mb-0 <?= $stats['low_stock_count'] > 0 ? 'text-danger' : 'text-warning' ?>">
                                    <?= $stats['low_stock_count'] ?>
                                </h4>
                               
                            </div>
                            <div class="icon-circle <?= $stats['low_stock_count'] > 0 ? 'bg-danger' : 'bg-warning' ?> text-white">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ NEW: Low Stock Alert for Employee -->
        <?php if ($stats['low_stock_count'] > 0): ?>
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-lightbulb me-2"></i>
                <strong><?= $stats['low_stock_count'] ?> product(s) are running low!</strong> 
                Check stock before confirming client purchases.
                <a href="/zaina-beauty/products/" class="alert-link ms-2">View Products</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-list-check me-2"></i> Your Tasks</h6>
            </div>
            <div class="card-body">
                <?php if ($stats['pending_tasks'] > 0): ?>
                    <p>You have <strong><?= $stats['pending_tasks'] ?></strong> pending task(s). Check your task list for details.</p>
                    <a href="/zaina-beauty/tasks/" class="btn btn-outline-info">View All Tasks</a>
                <?php else: ?>
                    <p class="text-success"><i class="fas fa-check-circle me-2"></i> Great job! No pending tasks.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- LIVE CLOCK SCRIPT -->
<script>
function updateLiveClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-KE', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const dateString = now.toLocaleDateString('en-KE', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    document.getElementById('liveTime').textContent = timeString;
    document.getElementById('liveDate').textContent = dateString;
}
updateLiveClock();
setInterval(updateLiveClock, 1000);
</script>

<!-- Custom Styles -->
<style>
.avatar {
    width: 60px;
    height: 60px;
}
.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}
.icon-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
</style>

<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php'; ?>