<?php
require_once '../includes/auth.php';
requireRole(['admin']);
include '../includes/functions.php';
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <h2>👥 Manage Staff</h2>
    <a href="add_user.php" class="btn btn-primary mb-3">+ Add User</a>

    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT * FROM users ORDER BY role, name");
            while ($user = $stmt->fetch()):
                if ($user['id'] == $_SESSION['user_id']) continue; // don't show current user
            ?>
            <tr>
                <td><?= escape($user['name']) ?></td>
                <td><?= escape($user['email']) ?></td>
                <td><?= ucfirst($user['role']) ?></td>
                <td>
                    <a href="#" class="btn btn-sm btn-warning">Edit</a>
                    <a href="#" class="btn btn-sm btn-danger">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>