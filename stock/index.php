<?php
// ✅ EXPORT LOGIC MUST BE AT THE VERY TOP (before any includes that output HTML)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    require_once '../includes/db.php';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="zaina_stock_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Product Name', 'Category', 'Supplier', 'Cost Price (KES)', 'Selling Price (KES)', 'Stock Qty', 'Profit Margin (%)', 'Total Cost Value (KES)', 'Total Retail Value (KES)']);
    
    $stmt = $pdo->query("
        SELECT 
            name, 
            category, 
            supplier,
            cost_price, 
            selling_price, 
            stock_qty,
            CASE 
                WHEN cost_price > 0 THEN ROUND(((selling_price - cost_price) / cost_price) * 100, 2)
                ELSE 0 
            END as profit_margin,
            (stock_qty * cost_price) as total_cost,
            (stock_qty * selling_price) as total_retail
        FROM products 
        WHERE type = 'product'
        ORDER BY name
    ");
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['name'],
            $row['category'] ?: 'General',
            $row['supplier'] ?: 'N/A',
            number_format($row['cost_price'], 2),
            number_format($row['selling_price'], 2),
            $row['stock_qty'],
            $row['profit_margin'],
            number_format($row['total_cost'], 2),
            number_format($row['total_retail'], 2)
        ]);
    }
    fclose($output);
    exit;
}

// ✅ PDF EXPORT LOGIC (LOGO FIXED - USE DIRECT FILE PATH)
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require_once '../vendor/tcpdf/tcpdf.php';
    require_once '../includes/db.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Zaina\'s Beauty Shop');
    $pdf->SetAuthor('Zaina\'s Beauty Shop');
    $pdf->SetTitle('Stock Report');
    $pdf->SetSubject('Stock Management Report');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    
    // ✅ FIXED LOGO PATH - USE __DIR__ FOR RELIABLE ABSOLUTE PATH
    $logo_path = __DIR__ . '/../assets/img/logo.png';
    
    // Only add logo if file exists and is readable
    if (file_exists($logo_path) && is_readable($logo_path)) {
        $pdf->Image($logo_path, 15, 15, 30, 0, 'PNG');
    } else {
        // Fallback: Add text if logo missing
        $pdf->Cell(0, 10, 'ZAINA\'S BEAUTY SHOP', 0, 1, 'C');
    }
    
    $pdf->Cell(0, 10, 'ZAINA BEAUTY SHOP STOCK MANAGEMENT REPORT', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Date
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Generated on: ' . date('F j, Y \a\t g:i A'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(41, 128, 185);
    $pdf->SetTextColor(255, 255, 255);
    
    $cols = ['Product', 'Category', 'Cost (KES)', 'Selling Price (KES)', 'Margin (%)', 'Stock', 'Total Cost', 'Total Retail'];
    $col_widths = [40, 25, 20, 20, 20, 15, 25, 25];
    
    for ($i = 0; $i < count($cols); $i++) {
        $pdf->Cell($col_widths[$i], 7, $cols[$i], 1, 0, 'C', 1);
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    
    $stmt = $pdo->query("
        SELECT 
            name, 
            category, 
            cost_price, 
            selling_price, 
            stock_qty,
            CASE 
                WHEN cost_price > 0 THEN ROUND(((selling_price - cost_price) / cost_price) * 100, 2)
                ELSE 0 
            END as profit_margin,
            (stock_qty * cost_price) as total_cost,
            (stock_qty * selling_price) as total_retail
        FROM products 
        WHERE type = 'product'
        ORDER BY name
    ");
    
    while ($row = $stmt->fetch()) {
        $pdf->Cell(40, 6, substr($row['name'], 0, 30), 1, 0, 'L');
        $pdf->Cell(25, 6, substr($row['category'] ?: 'General', 0, 20), 1, 0, 'L');
        $pdf->Cell(20, 6, number_format($row['cost_price'], 2), 1, 0, 'R');
        $pdf->Cell(20, 6, number_format($row['selling_price'], 2), 1, 0, 'R');
        $pdf->Cell(20, 6, $row['profit_margin'] . '%', 1, 0, 'R');
        $pdf->Cell(15, 6, $row['stock_qty'], 1, 0, 'C');
        $pdf->Cell(25, 6, number_format($row['total_cost'], 2), 1, 0, 'R');
        $pdf->Cell(25, 6, number_format($row['total_retail'], 2), 1, 1, 'R');
    }
    
    // Summary section
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 8, 'SUMMARY', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_products,
            COALESCE(SUM(stock_qty * cost_price), 0) as total_cost,
            COALESCE(SUM(stock_qty * selling_price), 0) as total_retail
        FROM products 
        WHERE type = 'product'
    ");
    $summary = $stmt->fetch();
    
    $pdf->Cell(0, 6, 'Total Products: ' . $summary['total_products'], 0, 1);
    $pdf->Cell(0, 6, 'Total Cost Value: KES ' . number_format($summary['total_cost'], 2), 0, 1);
    $pdf->Cell(0, 6, 'Total Retail Value: KES ' . number_format($summary['total_retail'], 2), 0, 1);
    $pdf->Cell(0, 6, 'Potential Profit: KES ' . number_format($summary['total_retail'] - $summary['total_cost'], 2), 0, 1);
    
    // Output PDF
    $pdf->Output('zaina_stock_report_' . date('Y-m-d') . '.pdf', 'D');
    exit;
}

// ✅ NOW safe to include auth and layout
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);
require_once '../includes/functions.php';
require_once '../includes/stock.php';

$page_title = "Stock Management";
include '../includes/layout.php';

// Get categories for filter
$stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE type = 'product' AND category != '' ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle filtering and sorting
$category_filter = $_GET['category'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'name';
$sort_order = $_GET['order'] ?? 'asc';

// Build query
$conditions = "type = 'product'";
$params = [];

if ($category_filter !== 'all') {
    $conditions .= " AND category = ?";
    $params[] = $category_filter;
}

// Validate sort column
$allowed_sort = ['name', 'category', 'cost_price', 'selling_price', 'stock_qty', 'profit_margin'];
$sort_column = in_array($sort_by, $allowed_sort) ? $sort_by : 'name';
$sort_direction = ($sort_order === 'desc') ? 'DESC' : 'ASC';

// Add profit margin calculation
$sql = "
    SELECT 
        *,
        CASE 
            WHEN cost_price > 0 THEN ROUND(((selling_price - cost_price) / cost_price) * 100, 2)
            ELSE 0 
        END as profit_margin
    FROM products 
    WHERE $conditions
    ORDER BY $sort_column $sort_direction
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- REST OF YOUR HTML REMAINS THE SAME -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>📦 Stock Overview</h2>
            <?php if ($_SESSION['user_role'] !== 'employee'): ?>
                <a href="adjust.php" class="btn btn-primary">+ Adjust Stock</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stock Value Summary - Only for admin/staff -->
<?php if ($_SESSION['user_role'] !== 'employee'): ?>
<div class="row mb-4">
    <?php
    $stmt = $pdo->query("
        SELECT 
            COALESCE(SUM(stock_qty * cost_price), 0) as total_cost,
            COALESCE(SUM(stock_qty * selling_price), 0) as total_retail
        FROM products 
        WHERE type = 'product'
    ");
    $value = $stmt->fetch();
    ?>
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5>Total Cost Value</h5>
                <h3><?= format_money($value['total_cost']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5>Total Retail Value</h5>
                <h3><?= format_money($value['total_retail']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-dark bg-warning">
            <div class="card-body">
                <h5>Potential Profit</h5>
                <h3><?= format_money($value['total_retail'] - $value['total_cost']) ?></h3>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Low Stock Alert -->
<?php
$lowStock = getLowStockProducts($pdo, 5);
if (!empty($lowStock)):
?>
<div class="alert alert-warning">
    <h5>⚠️ Low Stock Items (≤ 5 units)</h5>
    <ul>
        <?php foreach ($lowStock as $p): ?>
        <li><?= htmlspecialchars($p['name']) ?>: <?= $p['stock_qty'] ?> units</li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Controls -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <label class="form-label mb-0">Category Filter</label>
                <select class="form-select" id="categoryFilter">
                    <option value="all" <?= $category_filter === 'all' ? 'selected' : '' ?>>All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $category_filter === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0">Sort By</label>
                <select class="form-select" id="sortSelect">
                    <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Product Name</option>
                    <option value="category" <?= $sort_by === 'category' ? 'selected' : '' ?>>Category</option>
                    <option value="cost_price" <?= $sort_by === 'cost_price' ? 'selected' : '' ?>>Cost Price</option>
                    <option value="selling_price" <?= $sort_by === 'selling_price' ? 'selected' : '' ?>>Selling Price</option>
                    <option value="stock_qty" <?= $sort_by === 'stock_qty' ? 'selected' : '' ?>>Stock Quantity</option>
                    <option value="profit_margin" <?= $sort_by === 'profit_margin' ? 'selected' : '' ?>>Profit Margin</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0">Actions</label>
                <div>
                    <button class="btn btn-outline-secondary me-2" id="applyFilters">Apply</button>
                    <?php if ($_SESSION['user_role'] !== 'employee'): ?>
                        <a href="?export=csv" class="btn btn-success btn-sm">
                            <i class="fas fa-file-csv"></i> CSV
                        </a>
                        <a href="?export=pdf" class="btn btn-danger btn-sm">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Table -->
<div class="card">
    <div class="card-header">
        <h5>Current Stock Levels</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>
                            <a href="?category=<?= urlencode($category_filter) ?>&sort=name&order=<?= $sort_by === 'name' && $sort_order === 'asc' ? 'desc' : 'asc' ?>">
                                Product <?= $sort_by === 'name' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?category=<?= urlencode($category_filter) ?>&sort=category&order=<?= $sort_by === 'category' && $sort_order === 'asc' ? 'desc' : 'asc' ?>">
                                Category <?= $sort_by === 'category' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?category=<?= urlencode($category_filter) ?>&sort=cost_price&order=<?= $sort_by === 'cost_price' && $sort_order === 'asc' ? 'desc' : 'asc' ?>">
                                Cost Price <?= $sort_by === 'cost_price' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?category=<?= urlencode($category_filter) ?>&sort=selling_price&order=<?= $sort_by === 'selling_price' && $sort_order === 'asc' ? 'desc' : 'asc' ?>">
                                Selli Price <?= $sort_by === 'selling_price' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?category=<?= urlencode($category_filter) ?>&sort=profit_margin&order=<?= $sort_by === 'profit_margin' && $sort_order === 'asc' ? 'desc' : 'asc' ?>">
                                Margin <?= $sort_by === 'profit_margin' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?category=<?= urlencode($category_filter) ?>&sort=stock_qty&order=<?= $sort_by === 'stock_qty' && $sort_order === 'asc' ? 'desc' : 'asc' ?>">
                                Stock <?= $sort_by === 'stock_qty' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['category'] ?: 'General') ?></td>
                        <td>KES <?= number_format($p['cost_price'], 2) ?></td>
                        <td>KES <?= number_format($p['selling_price'], 2) ?></td>
                        <td>
                            <?php 
                            $margin_color = $p['profit_margin'] >= 50 ? 'text-success' : ($p['profit_margin'] >= 30 ? 'text-warning' : 'text-danger');
                            ?>
                            <span class="<?= $margin_color ?>"><?= $p['profit_margin'] ?>%</span>
                        </td>
                        <td>
                            <span class="<?= $p['stock_qty'] <= 5 ? 'text-danger fw-bold' : '' ?>">
                                <?= $p['stock_qty'] ?>
                            </span>
                            <?php if ($p['stock_qty'] <= 5): ?>
                                <span class="badge bg-warning ms-1">Low</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="movements.php?product_id=<?= $p['id'] ?>" class="btn btn-sm btn-info">History</a>
                            <?php if ($_SESSION['user_role'] !== 'employee'): ?>
                                <a href="adjust.php?product_id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Adjust</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($products)): ?>
                <div class="text-center text-muted py-3">
                    <i class="fas fa-box-open fa-2x mb-2"></i>
                    <p>No products found in this category.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Apply filters and sorting
document.getElementById('applyFilters').addEventListener('click', function() {
    const category = document.getElementById('categoryFilter').value;
    const sort = document.getElementById('sortSelect').value;
    const currentUrl = new URL(window.location);
    
    // Update URL parameters
    currentUrl.searchParams.set('category', category);
    currentUrl.searchParams.set('sort', sort);
    currentUrl.searchParams.set('order', 'asc'); // Reset order on new sort
    
    window.location.href = currentUrl.toString();
});
</script>
<?php include '../includes/footer.php';?>
<?php include '../includes/layout_end.php'; ?>