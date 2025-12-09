<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
$page_title = "Business Reports";
include '../includes/layout.php';

// Get current month data for dashboard
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

// Current month revenue
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM sales WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?");
$stmt->execute([$month_start, $month_end]);
$current_revenue = $stmt->fetch()['revenue'];

// Current month expenses  
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as expenses FROM expenses WHERE date >= ? AND date <= ?");
$stmt->execute([$month_start, $month_end]);
$current_expenses = $stmt->fetch()['expenses'];

// Current month profit
$current_profit = $current_revenue - $current_expenses;
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container-fluid mt-4">
    <h2>📊 Business Intelligence Dashboard</h2>
    
    <!-- MAIN KPI CARDS -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <h5>Monthly Revenue</h5>
                    <h3>KES <?= number_format($current_revenue, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger">
                <div class="card-body text-center">
                    <h5>Monthly Expenses</h5>
                    <h3>KES <?= number_format($current_expenses, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card <?= $current_profit >= 0 ? 'text-white bg-success' : 'text-dark bg-warning' ?>">
                <div class="card-body text-center">
                    <h5>Monthly Profit</h5>
                    <h3>KES <?= number_format($current_profit, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- REPORTS GRID -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">💰 Earnings & Revenue</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <p>Track your sales revenue across different time periods.</p>
                    <ul class="mb-3">
                        <li>Daily, Weekly, Monthly earnings</li>
                        <li>Transaction counts</li>
                        <li>Revenue trends</li>
                    </ul>
                    <a href="earnings.php" class="btn btn-primary mt-auto">View Revenue Reports</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">📈 Profit & Loss</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <p>Real business profitability based on actual sales and expenses.</p>
                    <ul class="mb-3">
                        <li>Revenue minus expenses = profit</li>
                        <li>Expense category breakdown</li>
                        <li>Top revenue sources</li>
                    </ul>
                    <a href="profit_loss.php" class="btn btn-success mt-auto">View Profit & Loss</a>
                <!-- Add this in the action buttons section -->
<a href="print_profit_loss.php" target="_blank" class="btn btn-success">
    <i class="fas fa-print"></i> Print Monthly Report
</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php';?>
<?php include '../includes/layout_end.php'; ?>