<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);
include '../includes/functions.php';
?>
<?php include '../includes/layout.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>👥 Clients</h2>
        <?php if ($_SESSION['user_role'] !== 'employee'): ?>
            <a href="add.php" class="btn btn-success">+ Add Client</a>
        <?php endif; ?>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Loyalty Points</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT * FROM clients ORDER BY name");
            while ($client = $stmt->fetch()):
            ?>
            <tr>
                <td><?= escape($client['name']) ?></td>
                <td><?= escape($client['phone']) ?></td>
                <td><?= $client['loyalty_points'] ?></td>
                <td>
                    <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include '../includes/footer.php';?>
<?php include '../includes/layout_end.php'; ?>