<?php
// Must be included AFTER auth.php
if (!isset($_SESSION['user_role'])) {
    die("Access denied: Authentication required.");
}
$role = $_SESSION['user_role'];
?>
<?php include '../includes/layout.php';?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaina's Beauty Shop</title>
    <!-- Bootstrap 5 -->
    <link href="/zaina-beauty/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link href="/zaina-beauty/assets/css/style.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="/zaina-beauty/assets/img/favicon.ico">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="<?= ($role === 'employee') ? '/zaina-beauty/pos/' : '/zaina-beauty/dashboard/'; ?>">
                <img src="/zaina-beauty/assets/img/logo.png" height="30" alt="Zaina's" class="me-2">
                <span>Zaina's</span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Nav Links -->
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if (in_array($role, ['admin','staff'])): ?>
                        <li class="nav-item"><a class="nav-link" href="/zaina-beauty/dashboard/"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/zaina-beauty/products/"><i class="fas fa-box me-1"></i> Products</a></li>
                        <li class="nav-item"><a class="nav-link" href="/zaina-beauty/sales/"><i class="fas fa-receipt me-1"></i> Sales</a></li>
                        <li class="nav-item"><a class="nav-link" href="/zaina-beauty/expenses/"><i class="fas fa-money-bill-wave me-1"></i> Expenses</a></li>
                        <li class="nav-item"><a class="nav-link" href="/zaina-beauty/reports/sales.php"><i class="fas fa-chart-line me-1"></i> Reports</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/zaina-beauty/pos/"><i class="fas fa-cash-register me-1"></i> POS</a></li>
                    <li class="nav-item"><a class="nav-link" href="/zaina-beauty/clients/"><i class="fas fa-users me-1"></i> Clients</a></li>
                    <li class="nav-item"><a class="nav-link" href="/zaina-beauty/tasks/"><i class="fas fa-tasks me-1"></i> Tasks</a></li>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="/zaina-beauty/settings/general.php"><i class="fas fa-cog me-1"></i> Settings</a></li>
                    <?php endif; ?>
                </ul>

                <!-- User Dropdown -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['user_name']) ?>
                            <span class="badge bg-secondary ms-2"><?= ucfirst($role) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/zaina-beauty/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>