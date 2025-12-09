<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
$page_title = "Profit & Loss Report";
include '../includes/layout.php';

// Get date filter from URL (default: current month)
$period = $_GET['period'] ?? 'month';
$today = date('Y-m-d');

switch($period) {
    case 'day':
        $start_date = $today;
        $end_date = $today;
        $period_label = 'Today (' . date('M j, Y') . ')';
        break;
    case 'week':
        // Current week (Monday to Sunday)
        $start_date = date('Y-m-d', strtotime('last monday'));
        $end_date = date('Y-m-d', strtotime('next sunday'));
        $period_label = 'This Week (' . date('M j', strtotime($start_date)) . ' - ' . date('M j', strtotime($end_date)) . ')';
        break;
    case 'month':
    default:
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        $period_label = date('F Y');
        break;
}

// Get REAL REVENUE (total sales)
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COUNT(*) as total_transactions
    FROM sales 
    WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
");
$stmt->execute([$start_date, $end_date]);
$revenue_data = $stmt->fetch();

// Get REAL EXPENSES
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_expenses,
        COUNT(*) as expense_count
    FROM expenses 
    WHERE date >= ? AND date <= ?
");
$stmt->execute([$start_date, $end_date]);
$expense_data = $stmt->fetch();

// Calculate PROFIT/LOSS
$net_profit = $revenue_data['total_revenue'] - $expense_data['total_expenses'];
$profit_margin = $revenue_data['total_revenue'] > 0 ? ($net_profit / $revenue_data['total_revenue']) * 100 : 0;

// Get expense breakdown by category
$stmt = $pdo->prepare("
    SELECT 
        category, 
        SUM(amount) as category_total,
        COUNT(*) as category_count
    FROM expenses 
    WHERE date >= ? AND date <= ?
    GROUP BY category 
    ORDER BY category_total DESC
");
$stmt->execute([$start_date, $end_date]);
$expense_breakdown = $stmt->fetchAll();

// Get top revenue sources (products/services)
$stmt = $pdo->prepare("
    SELECT 
        p.name as product_name,
        SUM(si.qty) as total_qty,
        SUM(si.qty * si.price) as total_revenue
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.created_at) >= ? AND DATE(s.created_at) <= ?
    GROUP BY p.id
    ORDER BY total_revenue DESC
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$top_products = $stmt->fetchAll();
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📈 Real Profit & Loss Report</h2>
        <a href="index.php" class="btn btn-secondary">← Back to Reports</a>
    </div>

    <!-- PERIOD FILTER -->
    <div class="mb-4">
        <div class="btn-group" role="group">
            <a href="?period=day" class="btn btn-outline-primary <?= $period === 'day' ? 'active' : '' ?>">Today</a>
            <a href="?period=week" class="btn btn-outline-primary <?= $period === 'week' ? 'active' : '' ?>">This Week</a>
            <a href="?period=month" class="btn btn-outline-primary <?= $period === 'month' ? 'active' : '' ?>">This Month</a>
        </div>
    </div>

    <!-- MAIN METRICS -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <h6>Total Revenue</h6>
                    <h3>KES <?= number_format($revenue_data['total_revenue'], 2) ?></h3>
                    <small><?= $revenue_data['total_transactions'] ?> transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger">
                <div class="card-body text-center">
                    <h6>Total Expenses</h6>
                    <h3>KES <?= number_format($expense_data['total_expenses'], 2) ?></h3>
                    <small><?= $expense_data['expense_count'] ?> expense entries</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card <?= $net_profit >= 0 ? 'text-white bg-success' : 'text-dark bg-warning' ?>">
                <div class="card-body text-center">
                    <h6>Net <?= $net_profit >= 0 ? 'Profit' : 'Loss' ?></h6>
                    <h3>KES <?= number_format($net_profit, 2) ?></h3>
                    <small><?= $profit_margin >= 0 ? '+' : '' ?><?= number_format($profit_margin, 1) ?>% margin</small>
                </div>
            </div>
        </div>
    </div>

    <!-- DETAILED BREAKDOWN -->
    <div class="row">
        <!-- EXPENSE BREAKDOWN -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>💼 Expense Breakdown</h5>
                    <span class="badge bg-secondary"><?= count($expense_breakdown) ?> categories</span>
                </div>
                <div class="card-body">
                    <?php if (empty($expense_breakdown)): ?>
                        <p class="text-center text-muted">No expenses recorded for this period.</p>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expense_breakdown as $expense): ?>
                                <tr>
                                    <td><?= htmlspecialchars($expense['category'] ?? 'Other') ?></td>
                                    <td class="text-end">KES <?= number_format($expense['category_total'], 2) ?></td>
                                    <td class="text-end"><?= $expense_data['total_expenses'] > 0 ? round(($expense['category_total'] / $expense_data['total_expenses']) * 100, 1) : 0 ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark text-white">
                                    <th>Total</th>
                                    <th class="text-end">KES <?= number_format($expense_data['total_expenses'], 2) ?></th>
                                    <th class="text-end">100%</th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TOP REVENUE SOURCES -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>🏆 Top Revenue Sources</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_products)): ?>
                        <p class="text-center text-muted">No sales data available.</p>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product/Service</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                                    <td class="text-center"><?= $product['total_qty'] ?></td>
                                    <td class="text-end">KES <?= number_format($product['total_revenue'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="d-flex gap-2">
        <a href="/zaina-beauty/expenses/add.php" class="btn btn-danger">
            <i class="fas fa-plus"></i> Add Expense
        </a>
        <a href="/zaina-beauty/pos/" class="btn btn-primary">
            <i class="fas fa-cash-register"></i> Make Sale
        </a>


<!-- With this -->
<a href="print_profit_loss.php?period=<?= urlencode($period) ?>" target="_blank" class="btn btn-success">
    <i class="fas fa-print"></i> Print Report
</a>
    </div>
</div>
<?php include '../includes/footer.php';?>
<?php include '../includes/layout_end.php'; ?>