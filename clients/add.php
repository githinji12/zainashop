<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

$message = '';
$error = '';

if ($_POST) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dob = $_POST['dob'] ?? null;

    if (!$name || !$phone) {
        $error = "Name and phone are required.";
    } else {
        try {
            // Check if phone already exists
            $stmt = $pdo->prepare("SELECT id FROM clients WHERE phone = ?");
            $stmt->execute([$phone]);
            $existing = $stmt->fetch();

            if ($existing) {
                // ✅ UPDATE existing client
                $stmt = $pdo->prepare("UPDATE clients SET name = ?, email = ?, dob = ? WHERE id = ?");
                $stmt->execute([$name, $email, $dob, $existing['id']]);
                $message = "✅ Client updated successfully!";
            } else {
                // ✅ CREATE new client
                $stmt = $pdo->prepare("INSERT INTO clients (name, phone, email, dob) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $phone, $email, $dob]);
                $message = "✅ Client added successfully!";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<?php include '../includes/layout.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <h2><?= isset($_GET['id']) ? 'Edit Client' : '+ Add Client' ?></h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Name *</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label>Phone *</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
            <div class="form-text">Must be unique (e.g., 0712345678)</div>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label>Date of Birth</label>
            <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
        </div>
        <button class="btn btn-primary">Save Client</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php include '../includes/layout_end.php'; ?>