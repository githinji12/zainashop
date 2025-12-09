<?php
require_once '../includes/auth.php';
// Only admin should edit products
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid product ID.');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die('Product not found.');
}

// Fetch categories & suppliers
$stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category != '' AND id != ? ORDER BY category");
$stmt->execute([$id]);
$existing_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT name FROM suppliers ORDER BY name");
$suppliers = $stmt->fetchAll(PDO::FETCH_COLUMN);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? $product['type'];
    $category = trim($_POST['category'] ?? '');
    $supplier = trim($_POST['supplier'] ?? '');
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
        $error = "Selling price cannot be less than cost price. You'll incur a loss!";
    } else {
        // Handle image upload
        $image = $product['image']; // keep existing if not changed
        if (!empty($_FILES['image']['name'])) {
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, JPEG, PNG, WEBP, or GIF images are allowed.";
            } else {
                // Delete old image if exists
                if ($product['image'] && file_exists('../uploads/' . $product['image'])) {
                    unlink('../uploads/' . $product['image']);
                }
                $image = 'products/' . uniqid() . '.' . $ext;
                $upload_path = '../uploads/' . $image;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error = "Failed to upload new image.";
                }
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, type = ?, category = ?, supplier = ?, cost_price = ?, selling_price = ?, stock_qty = ?, image = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $type, $category, $supplier, $cost_price, $selling_price, $stock, $image, $id]);

            header('Location: index.php?updated=1');
            exit();
        }
    }
}
?>

<?php include '../includes/layout.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container-fluid mt-4">
    <h2>
        <i class="fas fa-edit text-warning me-2"></i> 
        Edit Product: <?= htmlspecialchars($product['name']) ?>
    </h2>
    
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
                    <div class="col-md-6">
                        <label class="form-label">Product/Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required 
                               value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required onchange="toggleStock(this.value)">
                            <option value="product" <?= ($product['type'] === 'product') ? 'selected' : '' ?>>
                                Product (physical item)
                            </option>
                            <option value="service" <?= ($product['type'] === 'service') ? 'selected' : '' ?>>
                                Service (e.g., Facial, Manicure)
                            </option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-control" list="categories" 
                               value="<?= htmlspecialchars($_POST['category'] ?? $product['category']) ?>"
                               placeholder="e.g., Hair Care, Skincare">
                        <datalist id="categories">
                            <?php foreach ($existing_categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Supplier/Company</label>
                        <input type="text" name="supplier" class="form-control" list="suppliers" 
                               value="<?= htmlspecialchars($_POST['supplier'] ?? $product['supplier']) ?>"
                               placeholder="e.g., Beauty Supplies Ltd">
                        <datalist id="suppliers">
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= htmlspecialchars($sup) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Cost Price (KES) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="cost_price" class="form-control" min="0" required
                               value="<?= htmlspecialchars($_POST['cost_price'] ?? number_format($product['cost_price'], 2, '.', '')) ?>">
                        <div class="form-text">What you paid to acquire this item</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Selling Price (KES) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="selling_price" class="form-control" min="0.01" required
                               value="<?= htmlspecialchars($_POST['selling_price'] ?? number_format($product['selling_price'], 2, '.', '')) ?>">
                        <div class="form-text">Price charged to customers</div>
                    </div>
                    
                    <div class="col-md-6" id="stockField">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock" class="form-control" min="0"
                               value="<?= ($product['type'] === 'product') ? (int)($_POST['stock'] ?? $product['stock_qty']) : 0 ?>">
                        <div class="form-text">For products only. Services are unlimited.</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Product Image</label>
                        <?php if (($product['image'] ?? null) && file_exists('../uploads/' . $product['image'])): ?>
                            <div class="mb-2">
                                <img src="/zaina-beauty/uploads/<?= htmlspecialchars($product['image']) ?>" 
                                     alt="Current product image" width="100" class="img-thumbnail">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <div class="form-text">Leave blank to keep current image</div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i> Update Product
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($product['type'] === 'service'): ?>
<div class="col-12">
    <label class="form-label">Service QR Code</label>
    <div class="input-group">
        <input type="text" 
               name="qr_code" 
               class="form-control" 
               value="<?= htmlspecialchars($_POST['qr_code'] ?? $product['qr_code']) ?>"
               placeholder="Leave empty to auto-generate">
        <button class="btn btn-outline-secondary" type="button" onclick="generateQR()">
            <i class="fas fa-qrcode"></i> Generate
        </button>
    </div>
    <div class="mt-2">
        <?php if ($product['qr_code']): ?>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($product['qr_code']) ?>" 
                 alt="QR Code" class="img-thumbnail" id="qrPreview">
        <?php endif; ?>
    </div>
</div>

<script>
function generateQR() {
    // Use product ID as QR content
    const qrContent = 'SERVICE:<?= $product['id'] ?>';
    document.querySelector('[name="qr_code"]').value = qrContent;
    document.getElementById('qrPreview').src = 
        'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(qrContent);
}
</script>
<?php endif; ?>
<script>
function toggleStock(type) {
    const stockField = document.getElementById('stockField');
    if (type === 'service') {
        stockField.style.display = 'none';
    } else {
        stockField.style.display = 'block';
    }
}
toggleStock('<?= $product['type'] ?>');
</script>

<?php include '../includes/layout_end.php'; ?>