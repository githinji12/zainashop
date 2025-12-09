<?php
require_once '../includes/db.php'; // Only DB needed, no auth for printing

// Get period from URL
$period = $_GET['period'] ?? 'month';
$today = date('Y-m-d');

switch($period) {
    case 'day':
        $start_date = $today;
        $end_date = $today;
        $period_label = 'Today (' . date('M j, Y') . ')';
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('last monday'));
        $end_date = date('Y-m-d', strtotime('next sunday'));
        $period_label = 'Week of ' . date('M j, Y', strtotime($start_date));
        break;
    case 'month':
    default:
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        $period_label = date('F Y');
        break;
}

// Get revenue
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue, COUNT(*) as transactions FROM sales WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?");
$stmt->execute([$start_date, $end_date]);
$revenue_data = $stmt->fetch();

// Get expenses
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as expenses FROM expenses WHERE date >= ? AND date <= ?");
$stmt->execute([$start_date, $end_date]);
$expense_data = $stmt->fetch();

// Calculate profit
$net_profit = $revenue_data['revenue'] - $expense_data['expenses'];
$profit_margin = $revenue_data['revenue'] > 0 ? ($net_profit / $revenue_data['revenue']) * 100 : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profit & Loss Report - <?= $period_label ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 20px;
        }
        .logo {
            margin-bottom: 10px;
        }
        .logo img {
            height: 50px;
        }
        .period {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        .metrics {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .metric-card {
            text-align: center;
            padding: 15px;
            min-width: 200px;
            margin: 10px;
            border-radius: 8px;
        }
        .revenue { background-color: #e3f2fd; border: 1px solid #2196f3; }
        .expenses { background-color: #ffebee; border: 1px solid #f44336; }
        .profit { 
            background-color: <?= $net_profit >= 0 ? '#e8f5e9' : '#fff3e0' ?>; 
            border: 1px solid <?= $net_profit >= 0 ? '#4caf50' : '#ff9800' ?>; 
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .metric-label {
            font-size: 14px;
            color: #666;
        }
        .section {
            margin: 25px 0;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #2c3e50;
            padding-bottom: 5px;
            border-bottom: 1px solid #bdc3c7;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .profit-positive { color: #27ae60; font-weight: bold; }
        .profit-negative { color: #c0392b; font-weight: bold; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 15px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="/zaina-beauty/assets/img/logo.png" alt="Zaina's Beauty Shop">
        </div>
        <h1>Profit & Loss Report</h1>
        <div class="period"><?= $period_label ?></div>
    </div>

    <!-- MAIN METRICS -->
    <div class="metrics">
        <div class="metric-card revenue">
            <div class="metric-label">Total Revenue</div>
            <div class="metric-value">KES <?= number_format($revenue_data['revenue'], 2) ?></div>
            <div class="metric-label"><?= $revenue_data['transactions'] ?> transactions</div>
        </div>
        <div class="metric-card expenses">
            <div class="metric-label">Total Expenses</div>
            <div class="metric-value">KES <?= number_format($expense_data['expenses'], 2) ?></div>
        </div>
        <div class="metric-card profit">
            <div class="metric-label">Net <?= $net_profit >= 0 ? 'Profit' : 'Loss' ?></div>
            <div class="metric-value <?= $net_profit >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                KES <?= number_format($net_profit, 2) ?>
            </div>
            <div class="metric-label"><?= $profit_margin >= 0 ? '+' : '' ?><?= number_format($profit_margin, 1) ?>% margin</div>
        </div>
    </div>

    <!-- EXPENSE BREAKDOWN -->
    <div class="section">
        <div class="section-title">💼 Expense Breakdown</div>
        <?php
        $stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE date >= ? AND date <= ? GROUP BY category ORDER BY total DESC");
        $stmt->execute([$start_date, $end_date]);
        $expenses = $stmt->fetchAll();
        ?>
        
        <?php if (empty($expenses)): ?>
            <p class="text-center text-muted">No expenses recorded for this period.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_exp = $expense_data['expenses'];
                    foreach ($expenses as $expense): 
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($expense['category'] ?? 'Other') ?></td>
                        <td class="text-end">KES <?= number_format($expense['total'], 2) ?></td>
                        <td class="text-end"><?= $total_exp > 0 ? round(($expense['total'] / $total_exp) * 100, 1) : 0 ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td>Total Expenses</td>
                        <td class="text-end">KES <?= number_format($total_exp, 2) ?></td>
                        <td class="text-end">100%</td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>

    <!-- TOP REVENUE SOURCES -->
    <div class="section">
        <div class="section-title">🏆 Top Revenue Sources</div>
        <?php
        $stmt = $pdo->prepare("
            SELECT p.name, SUM(si.qty) as qty, SUM(si.qty * si.price) as revenue
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN sales s ON si.sale_id = s.id
            WHERE DATE(s.created_at) >= ? AND DATE(s.created_at) <= ?
            GROUP BY p.id
            ORDER BY revenue DESC
            LIMIT 5
        ");
        $stmt->execute([$start_date, $end_date]);
        $top_products = $stmt->fetchAll();
        ?>
        
        <?php if (empty($top_products)): ?>
            <p class="text-center text-muted">No sales data available.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Product/Service</th>
                        <th class="text-center">Quantity Sold</th>
                        <th class="text-end">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td class="text-center"><?= $product['qty'] ?></td>
                        <td class="text-end">KES <?= number_format($product['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>Generated on <?= date('F j, Y \a\t g:i A') ?> | Zaina's Beauty Shop Business Report</p>
        <p>Business Registration: PVT-0012345</p>
    </div>

    <!-- Auto-print on load -->
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>