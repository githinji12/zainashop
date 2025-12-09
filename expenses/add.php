<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

if ($_POST) {
    $desc = $_POST['description'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['date'];

    if ($desc && $amount && $date) {
        $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, category, date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$desc, $amount, $category, $date]);
        header('Location: index.php?added=1');
        exit;
    }
}
?>
<?php include '../includes/layout.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <h2>+ Add Expense</h2>
    <form method="POST">
        <div class="mb-3">
            <label>Description *</label>
            <input type="text" name="description" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Amount (KES) *</label>
            <input type="number" step="0.01" name="amount" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Category</label>
            <input type="text" name="category" class="form-control" placeholder="e.g., Rent, Supplies">
        </div>
        <div class="mb-3">
            <label>Date *</label>
            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <button class="btn btn-primary">Save Expense</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php';?>