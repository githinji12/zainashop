<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Handle product deletion (POST) — only admin/staff
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!in_array($_SESSION['user_role'], ['admin', 'staff'])) {
        die('🚫 Access Denied: You do not have permission to delete products.');
    }

    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) die('Product not found');

    logAudit($pdo, 'delete_product', 'products', $id, $product, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    sendSecurityAlert('Product Deleted', $_SESSION['user_name'], $_SERVER['REMOTE_ADDR'], $id);

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND type = 'product'");
    $stmt->execute([$id]);

    header('Location: index.php?deleted=1');
    exit();
}

// === SEARCH LOGIC ===
$search = trim($_GET['search'] ?? '');

// Build query
$sql = "SELECT * FROM products WHERE type = 'product'";
$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR category LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$deleted = isset($_GET['deleted']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zaina Beauty - Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include '../includes/layout.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-boxes me-2 text-primary"></i>Products</h2>
        <?php if (in_array($_SESSION['user_role'], ['admin', 'staff'])): ?>
            <a href="add.php" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i> Add Product</a>
        <?php endif; ?>
    </div>

    <!-- 🔍 SEARCH BAR -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search products by name or category..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($deleted): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Product deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="fas fa-inbox fa-2x mb-2 text-muted"></i><br>
            No products found <?= $search ? 'for "' . htmlspecialchars($search) . '"' : '' ?>.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Selling_Price (KES)</th>
                        <th>Stock Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= (int)($p['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($p['name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($p['category'] ?? 'Uncategorized') ?></td>
                            <td>KES <?= number_format((float)($p['selling_price'] ?? 0), 2) ?></td>
                            <td>
                                <?php
                                // Handle stock display
                                if ($p['type'] === 'service') {
                                    echo '<span class="badge bg-info">Service</span>';
                                } else {
                                    $stock = (int)($p['stock_qty'] ?? 0);
                                    if ($stock > 10) {
                                        echo '<span class="badge bg-success">In Stock</span>';
                                    } elseif ($stock > 0) {
                                        echo '<span class="badge bg-warning text-dark">Low Stock (' . $stock . ')</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Out of Stock</span>';
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (in_array($_SESSION['user_role'], ['admin', 'staff'])): ?>
                                    <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php'; ?>