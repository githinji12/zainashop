<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="earnings_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Earnings', 'Transactions']);
    
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, 
               COALESCE(SUM(total_amount), 0) as earnings,
               COUNT(*) as transactions
        FROM sales 
        WHERE DATE(created_at) >= ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$start_date]);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['date'],
            number_format($row['earnings'], 2),
            $row['transactions']
        ]);
    }
    fclose($output);
    exit;
}

// Return JSON data for charts
$start_date = date('Y-m-d', strtotime('-30 days'));
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as date, 
           COALESCE(SUM(total_amount), 0) as earnings
    FROM sales 
    WHERE DATE(created_at) >= ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$start_date]);

$dates = [];
$earnings = [];
while ($row = $stmt->fetch()) {
    $dates[] = date('M j', strtotime($row['date']));
    $earnings[] = floatval($row['earnings']);
}

// Fill missing dates with 0
$current = strtotime($start_date);
$end = strtotime(date('Y-m-d'));
while ($current <= $end) {
    $date_str = date('Y-m-d', $current);
    $date_label = date('M j', $current);
    if (!in_array($date_label, $dates)) {
        $dates[] = $date_label;
        $earnings[] = 0;
    }
    $current = strtotime('+1 day', $current);
}

// Sort by date
array_multisort($dates, $earnings);

echo json_encode(['dates' => $dates, 'earnings' => $earnings]);
?>