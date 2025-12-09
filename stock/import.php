<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/stock.php';

$message = '';
$error = '';

if ($_POST && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] != UPLOAD_ERR_OK) {
        $error = "File upload error.";
    } elseif (!in_array($file['type'], ['text/csv', 'application/vnd.ms-excel'])) {
        $error = "Only CSV files allowed.";
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle) {
            $pdo->beginTransaction();
            $success = 0;
            $header = fgetcsv($handle); // Skip header

            while (($row = fgetcsv($handle)) !== FALSE) {
                // Expected CSV format: product_name, barcode, quantity, cost_price, selling_price
                if (count($row) < 3) continue;

                $name = trim($row[0]);
                $barcode = !empty($row[1]) ? trim($row[1]) : null;
                $quantity = (int)$row[2];
                $cost_price = isset($row[3]) ? (float)$row[3] : 0;
                $selling_price = isset($row[4]) ? (float)$row[4] : $cost_price * 1.5;

                if ($quantity <= 0) continue;

                // Find or create product
                $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ? OR barcode = ?");
                $stmt->execute([$name, $barcode]);
                $product = $stmt->fetch();

                if ($product) {
                    // Update existing product
                    $stmt = $pdo->prepare("UPDATE products SET cost_price = ?, selling_price = ? WHERE id = ?");
                    $stmt->execute([$cost_price, $selling_price, $product['id']]);
                    $product_id = $product['id'];
                } else {
                    // Create new product
                    $stmt = $pdo->prepare("INSERT INTO products (name, barcode, type, cost_price, selling_price, stock_qty, category) VALUES (?, ?, 'product', ?, ?, 0, 'Imported')");
                    $stmt->execute([$name, $barcode, $cost_price, $selling_price]);
                    $product_id = $pdo->lastInsertId();
                }

                // Add stock
                if (addStock($pdo, $product_id, $quantity, $_SESSION['user_id'], 'CSV Import')) {
                    $success++;
                }
            }
            fclose($handle);
            
            if ($success > 0) {
                $pdo->commit();
                $message = "✅ Imported $success products successfully!";
            } else {
                $pdo->rollBack();
                $error = "No valid products found in CSV.";
            }
        } else {
            $error = "Unable to read CSV file.";
        }
    }
}

$page_title = "Import Stock";
include '../includes/layout.php';
?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<h2>📥 Import Stock from CSV</h2>
<p>Upload a CSV file with columns: <code>Product Name, Barcode (optional), Quantity, Cost Price, Selling Price</code></p>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="mt-4">
    <div class="mb-3">
        <label class="form-label">CSV File</label>
        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        <div class="form-text">
            Example row: <code>Facial Serum,FS123,20,800.00,2500.00</code>
        </div>
    </div>
    <button type="submit" class="btn btn-success">Import Stock</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include '../includes/layout_end.php'; ?>