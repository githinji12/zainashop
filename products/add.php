<?php
require_once '../includes/auth.php';
requireRole(['admin']); // Only admin can add products

// Fetch categories & suppliers
$stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category != '' ORDER BY category");
$existing_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT name FROM suppliers ORDER BY name");
$suppliers = $stmt->fetchAll(PDO::FETCH_COLUMN);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'product';
    $category = trim($_POST['category'] ?? '');
    $supplier = trim($_POST['supplier'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $cost_price = floatval($_POST['cost_price'] ?? 0);
    $selling_price = floatval($_POST['selling_price'] ?? 0);
    $stock = ($type === 'service') ? 999 : (int)($_POST['stock'] ?? 0);

    // Validation
    if (empty($name)) {
        $error = "Product name is required.";
    } elseif ($cost_price < 0) {
        $error = "Cost price cannot be negative.";
    } elseif ($selling_price <= 0) {
        $error = "Selling price must be greater than zero.";
    } elseif ($selling_price < $cost_price) {
        $error = "Selling price cannot be less than cost price.";
    } else {
        // Handle image upload
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, JPEG, PNG, WEBP, or GIF images are allowed.";
            } else {
                $image = 'products/' . uniqid() . '.' . $ext;
                $upload_path = '../uploads/' . $image;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error = "Failed to upload image. Check folder permissions.";
                }
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("
                INSERT INTO products 
                (name, type, category, supplier, cost_price, selling_price, stock_qty, barcode, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name, 
                $type, 
                $category, 
                $supplier, 
                $cost_price, 
                $selling_price, 
                $stock, 
                $barcode,
                $image
            ]);

            header('Location: index.php?added=1');
            exit();
        }
    }
}
?>

<?php include '../includes/layout.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container-fluid mt-4">
    <h2><i class="fas fa-plus-circle text-success me-2"></i> Add Product or Service</h2>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">

                    <!-- Product Name -->
                    <div class="col-md-6">
                        <label class="form-label">Product/Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <!-- Type -->
                    <div class="col-md-6">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required onchange="toggleStock(this.value)">
                            <option value="product" <?= ($_POST['type'] ?? 'product') === 'product' ? 'selected' : '' ?>>
                                Product (physical item)
                            </option>
                            <option value="service" <?= ($_POST['type'] ?? '') === 'service' ? 'selected' : '' ?>>
                                Service (Facial, Manicure, etc.)
                            </option>
                        </select>
                    </div>

                    <!-- Barcode Scanner -->
                    <div class="col-md-6">
                        <label class="form-label">Barcode</label>
                        <div class="input-group">
                            <input type="text" name="barcode" id="barcodeInput" class="form-control"
                                   placeholder="Scan or enter barcode"
                                   value="<?= htmlspecialchars($_POST['barcode'] ?? '') ?>">
                            <button type="button" class="btn btn-dark" onclick="openScanner('add')">
                                <i class="fas fa-barcode"></i> Scan
                            </button>
                        </div>
                        <div class="form-text">Scan barcode using camera</div>
                    </div>

                    <!-- Category -->
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-control" list="categories"
                               placeholder="e.g., Hair Care, Skincare"
                               value="<?= htmlspecialchars($_POST['category'] ?? '') ?>">
                        <datalist id="categories">
                            <?php foreach ($existing_categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Supplier -->
                    <div class="col-md-6">
                        <label class="form-label">Supplier/Company</label>
                        <input type="text" name="supplier" class="form-control" list="suppliers"
                               placeholder="e.g., Beauty Supplies Ltd"
                               value="<?= htmlspecialchars($_POST['supplier'] ?? '') ?>">
                        <datalist id="suppliers">
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= htmlspecialchars($sup) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Cost Price -->
                    <div class="col-md-6">
                        <label class="form-label">Cost Price (KES) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="cost_price" class="form-control" min="0" required
                               value="<?= htmlspecialchars($_POST['cost_price'] ?? '0.00') ?>">
                        <div class="form-text">Purchase price</div>
                    </div>

                    <!-- Selling Price -->
                    <div class="col-md-6">
                        <label class="form-label">Selling Price (KES) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="selling_price" class="form-control" min="0.01" required
                               value="<?= htmlspecialchars($_POST['selling_price'] ?? '0.00') ?>">
                        <div class="form-text">Customer price</div>
                    </div>

                    <!-- Stock -->
                    <div class="col-md-6" id="stockField">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock" class="form-control" min="0"
                               value="<?= (int)($_POST['stock'] ?? 0) ?>">
                        <div class="form-text">Only for products. Services are unlimited.</div>
                    </div>

                    <!-- Image -->
                    <div class="col-md-6">
                        <label class="form-label">Product Image (Optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Save Product
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleStock(type) {
    const stockField = document.getElementById('stockField');
    stockField.style.display = (type === 'service') ? 'none' : 'block';
}
toggleStock('<?= $_POST['type'] ?? 'product' ?>');

// Open mobile scanner
function openScanner(mode) {
    window.open('/zaina-beauty/pos/mobile-scan.php?mode=' + mode, 'scannerWindow', 'width=450,height=700');
}

// Handle scanned data
window.addEventListener("message", function(event) {
    if (!event.data) return;

    // 1️⃣ Add.php mode: fill barcode input
    if (event.data.scannedBarcode) {
        document.getElementById('barcodeInput').value = event.data.scannedBarcode;
    }

    // 2️⃣ POS mode: add to cart
    if (event.data.scannedProduct) {
        const product = event.data.scannedProduct;
        const existingIndex = cart.findIndex(item => item.id == product.id);
        if (existingIndex !== -1) {
            cart[existingIndex].qty += 1;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.selling_price),
                qty: 1,
                type: product.type,
                stock: product.stock_qty
            });
        }
        renderCart();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php'; ?>
