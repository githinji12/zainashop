<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
$page_title = "Earnings Report";
include '../includes/layout.php';

// Get date ranges
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$this_week_start = date('Y-m-d', strtotime('last monday'));
$last_week_start = date('Y-m-d', strtotime('last monday -1 week'));
$this_month_start = date('Y-m-01');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end = date('Y-m-t', strtotime('-1 month'));

// Get earnings data
function getEarnings($pdo, $date_condition, $params = []) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as earnings, COUNT(*) as transactions FROM sales WHERE $date_condition");
    $stmt->execute($params);
    return $stmt->fetch();
}

$today_data = getEarnings($pdo, "DATE(created_at) = ?", [$today]);
$yesterday_data = getEarnings($pdo, "DATE(created_at) = ?", [$yesterday]);
$this_week_data = getEarnings($pdo, "DATE(created_at) >= ?", [$this_week_start]);
$last_week_data = getEarnings($pdo, "DATE(created_at) >= ? AND DATE(created_at) < ?", [$last_week_start, $this_week_start]);
$this_month_data = getEarnings($pdo, "DATE(created_at) >= ?", [$this_month_start]);
$last_month_data = getEarnings($pdo, "DATE(created_at) >= ? AND DATE(created_at) <= ?", [$last_month_start, $last_month_end]);
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>💰 Earnings Report</h2>
        <a href="index.php" class="btn btn-secondary">← Back to Reports</a>
    </div>

    <div class="row">
        <!-- DAILY COMPARISON -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📅 Daily Comparison</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Earnings</th>
                                <th>Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-success">
                                <td>Today (<?= date('M j') ?>)</td>
                                <td><strong>KES <?= number_format($today_data['earnings'], 2) ?></strong></td>
                                <td><?= $today_data['transactions'] ?></td>
                            </tr>
                            <tr>
                                <td>Yesterday</td>
                                <td>KES <?= number_format($yesterday_data['earnings'], 2) ?></td>
                                <td><?= $yesterday_data['transactions'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- WEEKLY COMPARISON -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">📆 Weekly Comparison</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Earnings</th>
                                <th>Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-success">
                                <td>This Week</td>
                                <td><strong>KES <?= number_format($this_week_data['earnings'], 2) ?></strong></td>
                                <td><?= $this_week_data['transactions'] ?></td>
                            </tr>
                            <tr>
                                <td>Last Week</td>
                                <td>KES <?= number_format($last_week_data['earnings'], 2) ?></td>
                                <td><?= $last_week_data['transactions'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MONTHLY COMPARISON -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">📅 Monthly Comparison</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Earnings</th>
                                <th>Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-success">
                                <td>This Month (<?= date('M Y') ?>)</td>
                                <td><strong>KES <?= number_format($this_month_data['earnings'], 2) ?></strong></td>
                                <td><?= $this_month_data['transactions'] ?></td>
                            </tr>
                            <tr>
                                <td>Last Month (<?= date('M Y', strtotime('-1 month')) ?>)</td>
                                <td>KES <?= number_format($last_month_data['earnings'], 2) ?></td>
                                <td><?= $last_month_data['transactions'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- CHART CONTAINER -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>📊 Earnings Trend (Last 30 Days)</h5>
        </div>
        <div class="card-body">
            <canvas id="earningsChart" height="100"></canvas>
        </div>
    </div>

    <a href="api/earnings_data.php?export=csv" class="btn btn-success">
        <i class="fas fa-file-export"></i> Export to CSV
    </a>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Fetch last 30 days data
fetch('/zaina-beauty/reports/api/earnings_data.php')
    .then(response => response.json())
    .then(data => {
        const ctx = document.getElementById('earningsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.dates,
                datasets: [{
                    label: 'Daily Earnings (KES)',
                    data: data.earnings,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
</script>
<?php include '../includes/footer.php';?>
<?php include '../includes/layout_end.php'; ?>