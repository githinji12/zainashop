<?php
// This file must be included AFTER auth.php
if (!isset($_SESSION['user_role']) || !isset($_SESSION['user_name'])) {
    die("Access denied: Authentication required.");
}
$role = $_SESSION['user_role'];
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container-fluid">
    <!-- Logo & Home Link -->
    <a class="navbar-brand" href="<?= ($role === 'employee') ? '/zaina-beauty/pos/' : '/zaina-beauty/dashboard/'; ?>">
      <img src="/zaina-beauty/assets/img/logo.png" height="30" alt="Zaina's Beauty Shop">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">

        <!-- Dashboard -->
        <?php if (in_array($role, ['admin', 'staff'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="/zaina-beauty/dashboard/">Dashboard</a>
          </li>
        <?php endif; ?>

        <!-- POS -->
        <li class="nav-item">
          <a class="nav-link" href="/zaina-beauty/pos/">POS</a>
        </li>

        <!-- Products -->
        <?php if (in_array($role, ['admin', 'staff'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="/zaina-beauty/products/">Products</a>
          </li>
        <?php endif; ?>

        <!-- Sales -->
        <?php if (in_array($role, ['admin', 'staff'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="/zaina-beauty/sales/">Sales</a>
          </li>
        <?php endif; ?>

        <!-- Clients (CRM) -->
        <li class="nav-item">
          <a class="nav-link" href="/zaina-beauty/clients/">Clients</a>
        </li>

        <!-- Tasks -->
        <li class="nav-item">
          <a class="nav-link" href="/zaina-beauty/tasks/">Tasks</a>
        </li>

        <!-- Expenses -->
        <?php if (in_array($role, ['admin', 'staff'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="/zaina-beauty/expenses/">Expenses</a>
          </li>
        <?php endif; ?>

        <!-- Reports -->
        <?php if (in_array($role, ['admin', 'staff'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="/zaina-beauty/reports/sales.php">Reports</a>
          </li>
        <?php endif; ?>

        <!-- Settings (Admin Only) -->
        <?php if ($role === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link" href="/zaina-beauty/settings/general.php">Settings</a>
          </li>
        <?php endif; ?>

      </ul>

      <!-- User Dropdown -->
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
            <?= htmlspecialchars($_SESSION['user_name']) ?> 
            <span class="badge bg-secondary ms-2"><?= ucfirst($role) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="/zaina-beauty/auth/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>