<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']); // Only they can assign tasks
require_once '../includes/db.php';

// Get staff list for assignment
$stmt = $pdo->query("SELECT id, name FROM users WHERE role IN ('staff','employee') ORDER BY name");
$staff = $stmt->fetchAll();

if ($_POST) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $assigned = $_POST['assigned_to'];
    $due = $_POST['due_date'];

    if ($title && $assigned && $due) {
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, due_date, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $desc, $assigned, $due, $_SESSION['user_id']]);
        header('Location: index.php?added=1');
        exit;
    }
}
?>
<?php include '../includes/layout.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <h2>+ Create Task</h2>
    <form method="POST">
        <div class="mb-3">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label>Assign To *</label>
            <select name="assigned_to" class="form-select" required>
                <option value="">-- Select Staff --</option>
                <?php foreach ($staff as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Due Date *</label>
            <input type="date" name="due_date" class="form-control" required>
        </div>
        <button class="btn btn-primary">Create Task</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php';?>