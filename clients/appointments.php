<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);
require_once '../includes/db.php';

// For now: just list upcoming appointments (you can extend later)
$stmt = $pdo->query("SELECT c.name, c.phone, t.title, t.due_date 
                      FROM tasks t 
                      JOIN clients c ON t.assigned_to = c.id 
                      WHERE t.due_date >= CURDATE() 
                      ORDER BY t.due_date");
$appointments = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid mt-4">
    <h2>📅 Appointments</h2>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Client</th>
                <th>Phone</th>
                <th>Service</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appt): ?>
            <tr>
                <td><?= htmlspecialchars($appt['name']) ?></td>
                <td><?= htmlspecialchars($appt['phone']) ?></td>
                <td><?= htmlspecialchars($appt['title']) ?></td>
                <td><?= htmlspecialchars($appt['due_date']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>