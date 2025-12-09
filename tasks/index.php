<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);
require_once '../includes/db.php';

// Handle task completion (employee only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['user_role'] === 'employee') {
    $task_id = (int)$_POST['task_id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify employee owns this task
    $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
    $stmt->execute([$task_id, $user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
        $stmt->execute([$task_id]);
        header("Location: index.php?completed=1");
        exit();
    }
}

// Fetch tasks based on role
if ($_SESSION['user_role'] === 'employee') {
    $stmt = $pdo->prepare("
        SELECT t.*, u.name as assignee 
        FROM tasks t 
        JOIN users u ON t.assigned_to = u.id 
        WHERE t.assigned_to = ? 
        ORDER BY t.status = 'completed', t.due_date
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->query("
        SELECT t.*, u.name as assignee 
        FROM tasks t 
        JOIN users u ON t.assigned_to = u.id 
        ORDER BY t.status = 'completed', t.due_date
    ");
}
$tasks = $stmt->fetchAll();
?>
<?php include '../includes/layout.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>✅ Tasks</h2>
        <?php if ($_SESSION['user_role'] !== 'employee'): ?>
            <a href="add.php" class="btn btn-primary">+ New Task</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['completed'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <strong>✅ Task Completed!</strong> Status updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <?php if (empty($tasks)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="fas fa-check-circle fa-2x mb-2"></i>
            <p>No tasks assigned</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Task</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <?php if ($_SESSION['user_role'] === 'employee'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $t): ?>
                    <tr class="<?= $t['status'] === 'completed' ? 'table-success' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars($t['title']) ?></strong>
                            <?php if (!empty($t['description'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($t['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($t['assignee']) ?></td>
                        <td>
                            <span class="<?= (strtotime($t['due_date']) < strtotime('today') && $t['status'] !== 'completed') ? 'text-danger fw-bold' : '' ?>">
                                <?= date('M j, Y', strtotime($t['due_date'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($t['status'] === 'completed'): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($_SESSION['user_role'] === 'employee' && $t['status'] !== 'completed'): ?>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success" title="Mark as Done">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </td>
                        <?php elseif ($_SESSION['user_role'] === 'employee'): ?>
                            <td>
                                <span class="text-success"><i class="fas fa-check-circle"></i> Done</span>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php'; ?>