<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']); // Only admin/staff can print labels
require_once '../includes/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_ids = $_POST['product_ids'] ?? [];
    $copies = (int)($_POST['copies'] ?? 1);
    $label_size = $_POST['label_size'] ?? '57x32';
    
    if (empty($product_ids)) {
        $error = "Please select at least one product.";
    } else {
        // Validate copies
        $copies = max(1, min(50, $copies)); // Limit to 1-50 copies
        
        // Fetch products
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, name, barcode, selling_price FROM products WHERE id IN ($placeholders) AND barcode IS NOT NULL");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll();
        
        if (empty($products)) {
            $error = "No products with barcodes found.";
        } else {
            // Load TCPDF
            require_once '../vendor/tcpdf/tcpdf.php';
            
            // Supported label sizes (width x height in mm)
            $sizes = [
                '57x32' => [57, 32],
                '70x35' => [70, 35],
                '100x50' => [100, 50]
            ];
            
            if (!isset($sizes[$label_size])) {
                $label_size = '57x32';
            }
            $size = $sizes[$label_size];
            
            try {
                $pdf = new TCPDF('P', 'mm', $size, true, 'UTF-8', false);
                $pdf->SetCreator('Zaina Beauty Shop');
                $pdf->SetAuthor('Zaina Beauty');
                $pdf->SetTitle('Product Barcode Labels');
                $pdf->SetAutoPageBreak(false);
                $pdf->SetMargins(2, 2, 2);
                $pdf->SetCellPadding(0);
                
                foreach ($products as $product) {
                    for ($i = 0; $i < $copies; $i++) {
                        $pdf->AddPage();
                        $pdf->SetFont('helvetica', '', 8);
                        
                        // Shop name
                        $pdf->Cell(0, 4, 'ZAINA BEAUTY', 0, 1, 'C');
                        $pdf->Ln(0.5);
                        
                        // Product name (truncate to fit)
                        $name = mb_strimwidth($product['name'], 0, 35, '...');
                        $pdf->Cell(0, 3.5, $name, 0, 1, 'C');
                        $pdf->Ln(0.5);
                        
                        // Price
                        $pdf->SetFont('helvetica', 'B', 9);
                        $pdf->Cell(0, 4, 'KES ' . number_format((float)$product['selling_price'], 2), 0, 1, 'C');
                        $pdf->Ln(1);
                        
             // Barcode
if (!empty($product['barcode'])) {
    $x = ($size[0] - 40) / 2; // Center horizontally
    $y = $pdf->GetY();         // Current Y position
    
    $pdf->write1DBarcode(
        $product['barcode'],   // $code
        'C128A',               // $type
        $x,                    // $x (left margin) → WAS ''
        $y,                    // $y (top margin) → WAS ''
        40,                    // $width
        10,                    // $height
        0.4,                   // $xres
        [                      // $style
            'position' => 'C',
            'border' => false,
            'padding' => 0
        ]
    );
    $pdf->Ln(10);
}
                        // Barcode text
                        $pdf->SetFont('helvetica', '', 6);
                        $pdf->Cell(0, 2.5, strtoupper($product['barcode'] ?? 'NO BARCODE'), 0, 1, 'C');
                    }
                }
                
                // Output PDF
                $filename = 'barcodes_' . date('Ymd_His') . '.pdf';
                $pdf->Output($filename, 'D');
                exit();
                
            } catch (Exception $e) {
                $error = "PDF generation failed: " . $e->getMessage();
                error_log("Barcode PDF Error: " . $e->getMessage());
            }
        }
    }
}

// Fetch products with barcodes
$stmt = $pdo->query("
    SELECT id, name, barcode, selling_price 
    FROM products 
    WHERE type = 'product' AND barcode IS NOT NULL AND barcode != ''
    ORDER BY name
");
$products = $stmt->fetchAll();

$page_title = "Print Barcode Labels";
include '../includes/layout.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-barcode text-primary me-2"></i>
            Print Barcode Labels
        </h2>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Products
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="fas fa-barcode fa-2x mb-2 text-muted"></i><br>
            No products with barcodes found. 
            <a href="add.php" class="alert-link">Add a product with a barcode</a>.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" id="barcodeForm">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Label Size</label>
                            <select name="label_size" class="form-select">
                                <option value="57x32" <?= ($_POST['label_size'] ?? '57x32') === '57x32' ? 'selected' : '' ?>>57×32 mm (Standard)</option>
                                <option value="70x35" <?= ($_POST['label_size'] ?? '') === '70x35' ? 'selected' : '' ?>>70×35 mm (Large)</option>
                                <option value="100x50" <?= ($_POST['label_size'] ?? '') === '100x50' ? 'selected' : '' ?>>100×50 mm (Extra Large)</option>
                            </select>
                            <div class="form-text">Choose based on your label printer</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Copies per Product</label>
                            <input type="number" name="copies" class="form-control" value="<?= (int)($_POST['copies'] ?? 1) ?>" min="1" max="50">
                            <div class="form-text">1–50 copies per product</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Select Products to Print</label>
                        <div class="row" style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($products as $p): ?>
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="product_ids[]" 
                                               value="<?= $p['id'] ?>" 
                                               id="prod<?= $p['id'] ?>"
                                               <?= (is_array($_POST['product_ids'] ?? null) && in_array($p['id'], $_POST['product_ids'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="prod<?= $p['id'] ?>">
                                            <div class="d-flex justify-content-between">
                                                <span><?= htmlspecialchars(substr($p['name'], 0, 30)) ?></span>
                                                <span class="badge bg-success"><?= htmlspecialchars($p['barcode']) ?></span>
                                            </div>
                                            <small class="text-muted">KES <?= number_format((float)$p['selling_price'], 2) ?></small>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-print me-2"></i> Generate & Download PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> Printing Tips</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Use a <strong>thermal label printer</strong> (e.g., Brother QL series)</li>
                    <li>For A4 printing: Select "Fit to Page" in print dialog</li>
                    <li>Barcodes are generated in <strong>Code 128</strong> format (widely supported)</li>
                    <li>Products without barcodes are <strong>excluded automatically</strong></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Form validation
document.getElementById('barcodeForm').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('input[name="product_ids[]"]:checked');
    if (checked.length === 0) {
        e.preventDefault();
        alert('Please select at least one product to print.');
    }
});

// Select all/none toggle (optional)
// Add this if you want bulk selection:
/*
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.createElement('button');
    toggle.className = 'btn btn-sm btn-outline-secondary mb-2';
    toggle.textContent = 'Select All';
    toggle.onclick = function() {
        const checkboxes = document.querySelectorAll('input[name="product_ids[]"]');
        const isChecked = checkboxes[0] && checkboxes[0].checked;
        checkboxes.forEach(cb => cb.checked = !isChecked);
        this.textContent = isChecked ? 'Select All' : 'Deselect All';
    };
    document.querySelector('.row[style]').parentNode.insertBefore(toggle, document.querySelector('.row[style]'));
});
*/
</script>

<?php include '../includes/layout_end.php'; ?>