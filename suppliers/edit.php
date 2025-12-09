<?php
require_once '../includes/auth.php';
requireRole(['admin']);
require_once '../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid supplier ID.');

$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) die('Supplier not found.');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        $error = "Supplier name is required.";
    } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE suppliers 
            SET name = ?, contact_person = ?, phone = ?, email = ?, address = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $contact_person, $phone, $email, $address, $id]);

        header('Location: index.php?updated=1');
        exit();
    }
}
?>

<?php include '../includes/layout.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit text-warning me-2"></i> Edit Supplier</h2>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Supplier Name *</label>
                        <input type="text" name="name" class="form-control" required 
                               value="<?= htmlspecialchars($_POST['name'] ?? $supplier['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control"
                               value="<?= htmlspecialchars($_POST['contact_person'] ?? $supplier['contact_person']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= htmlspecialchars($_POST['phone'] ?? $supplier['phone']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($_POST['email'] ?? $supplier['email']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($_POST['address'] ?? $supplier['address']) ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i> Update Supplier
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/layout_end.php'; ?>