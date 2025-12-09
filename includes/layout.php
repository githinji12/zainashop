<?php
if (!isset($_SESSION['user_role'])) {
    die("Access denied");
}
$role = $_SESSION['user_role'];
$page_title = $page_title ?? "Zaina's Beauty Shop";

// Get stock count (only if needed)
$stock_count = 0;
if (in_array($role, ['admin', 'staff', 'employee'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE type = 'product'");
    $stmt->execute();
    $stock_count = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — Zaina's Beauty Shop</title>
    <link href="/zaina-beauty/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/zaina-beauty/assets/css/style.css" rel="stylesheet">
    <link rel="icon" href="/zaina-beauty/assets/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar-menu .nav-item {
            position: relative;
        }
        .submenu {
            padding-left: 30px !important;
            background: rgba(0, 0, 0, 0.2) !important;
            border-left: 3px solid #3498db;
            transition: all 0.3s ease;
        }
        .submenu a {
            padding: 10px 20px !important;
            font-size: 0.9em;
            display: block;
            color: #ecf0f1 !important;
        }
        .submenu a:hover {
            background: rgba(52, 152, 219, 0.3) !important;
        }
        .stock-badge {
            background: #e74c3c;
            color: white;
            border-radius: 10px;
            font-size: 0.7em;
            padding: 2px 6px;
            margin-left: 8px;
        }
        .sidebar.collapsed .stock-badge {
            display: none;
        }
        .toggle-arrow {
            margin-left: auto;
            transition: transform 0.2s ease;
            color: #bdc3c7;
        }
        .stock-toggle[aria-expanded="true"] .toggle-arrow {
            transform: rotate(180deg);
            color: #ecf0f1;
        }
        .sidebar.collapsed .toggle-arrow {
            display: none;
        }
    </style>
</head>
<body>

<!-- SIDEBAR (Collapsed by default) -->
<div class="sidebar collapsed" id="sidebar">
    <div class="sidebar-header p-3">
        <img src="/zaina-beauty/assets/img/logo.png" alt="Zaina's" height="35" class="logo">
    </div>
    <ul class="sidebar-menu">

        <!-- ✅ FULL ACCESS FOR ADMIN AND STAFF (IDENTICAL) -->
        <?php if ($role === 'admin' || $role === 'staff'): ?>
            <li><a href="/zaina-beauty/dashboard/" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="/zaina-beauty/products/" class="<?= strpos($_SERVER['REQUEST_URI'], '/products/') !== false ? 'active' : '' ?>"><i class="fas fa-box"></i> <span>Products</span></a></li>
            <li><a href="/zaina-beauty/sales/confirm_payment.php"><i class="fas fa-check-circle"></i> <span>Confirm Payment</span></a></li>
            
            <!-- STOCK MENU -->
            <li class="nav-item">
                <a href="#" 
                   class="nav-link stock-toggle <?= strpos($_SERVER['REQUEST_URI'], '/stock/') !== false ? 'active' : '' ?>" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#stockSubmenu" 
                   aria-expanded="<?= strpos($_SERVER['REQUEST_URI'], '/stock/') !== false ? 'true' : 'false' ?>" 
                   aria-controls="stockSubmenu">
                    <i class="fas fa-warehouse"></i> 
                    <span>
                        Stock 
                        <span class="stock-badge"><?= $stock_count ?></span>
                    </span>
                    <i class="fas fa-chevron-down toggle-arrow ms-auto"></i>
                </a>
                <ul class="submenu collapse <?= strpos($_SERVER['REQUEST_URI'], '/stock/') !== false ? 'show' : '' ?>" id="stockSubmenu">
                    <li><a href="/zaina-beauty/stock/" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/stock/') !== false ? 'active' : '' ?>">View Stock</a></li>
                    <li><a href="/zaina-beauty/stock/adjust.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'adjust.php') !== false ? 'active' : '' ?>">Adjust Stock</a></li>
                    <li><a href="/zaina-beauty/stock/import.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'import.php') !== false ? 'active' : '' ?>">Import CSV</a></li>
                    <li><a href="/zaina-beauty/stock/export.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'export.php') !== false ? 'active' : '' ?>">Export Report</a></li>
                </ul>
            </li>
            
            <!-- PURCHASE ORDERS -->
            <li class="nav-item">
                <a href="#" 
                   class="nav-link stock-toggle <?= strpos($_SERVER['REQUEST_URI'], '/purchase_orders/') !== false ? 'active' : '' ?>" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#poSubmenu" 
                   aria-expanded="<?= strpos($_SERVER['REQUEST_URI'], '/purchase_orders/') !== false ? 'true' : 'false' ?>" 
                   aria-controls="poSubmenu">
                    <i class="fas fa-shopping-cart"></i> 
                    <span>Purchase Orders</span>
                    <i class="fas fa-chevron-down toggle-arrow ms-auto"></i>
                </a>
                <ul class="submenu collapse <?= strpos($_SERVER['REQUEST_URI'], '/purchase_orders/') !== false ? 'show' : '' ?>" id="poSubmenu">
                    <li><a href="/zaina-beauty/purchase_orders/create.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'create.php') !== false ? 'active' : '' ?>">Create New PO</a></li>
                    <li><a href="/zaina-beauty/purchase_orders/" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/purchase_orders/') !== false ? 'active' : '' ?>">View All Orders</a></li>
                    <li><a href="/zaina-beauty/suppliers/" class="<?= strpos($_SERVER['REQUEST_URI'], '/suppliers/') !== false ? 'active' : '' ?>">Manage Suppliers</a></li>
                </ul>
            </li>
            
            <li><a href="/zaina-beauty/sales/" class="<?= strpos($_SERVER['REQUEST_URI'], '/sales/') !== false ? 'active' : '' ?>"><i class="fas fa-receipt"></i> <span>Sales</span></a></li>
            <li><a href="/zaina-beauty/expenses/" class="<?= strpos($_SERVER['REQUEST_URI'], '/expenses/') !== false ? 'active' : '' ?>"><i class="fas fa-money-bill-wave"></i> <span>Expenses</span></a></li>
            <li><a href="/zaina-beauty/reports/" class="<?= strpos($_SERVER['REQUEST_URI'], '/reports/') !== false ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> <span>Reports</span></a></li>
            <li><a href="/zaina-beauty/reports/audit.php" class="<?= strpos($_SERVER['REQUEST_URI'], '/reports/audit.php') !== false ? 'active' : '' ?>">
                <i class="fas fa-shield-alt"></i> <span>Security Audit</span>
            </a></li>
            <li><a href="/zaina-beauty/settings/general.php" class="<?= strpos($_SERVER['REQUEST_URI'], '/settings/') !== false ? 'active' : '' ?>"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        <?php endif; ?>

        <!-- ✅ EMPLOYEE MENU (Restricted) -->
        <?php if ($role === 'employee'): ?>
            <li><a href="/zaina-beauty/dashboard/" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="/zaina-beauty/products/" class="<?= strpos($_SERVER['REQUEST_URI'], '/products/') !== false ? 'active' : '' ?>"><i class="fas fa-box"></i> <span>Products</span></a></li>
            <li><a href="/zaina-beauty/pos/" class="<?= strpos($_SERVER['REQUEST_URI'], '/pos/') !== false ? 'active' : '' ?>"><i class="fas fa-cash-register"></i> <span>POS</span></a></li>
            <li><a href="/zaina-beauty/clients/" class="<?= strpos($_SERVER['REQUEST_URI'], '/clients/') !== false ? 'active' : '' ?>"><i class="fas fa-users"></i> <span>Clients</span></a></li>
            <li><a href="/zaina-beauty/tasks/" class="<?= strpos($_SERVER['REQUEST_URI'], '/tasks/') !== false ? 'active' : '' ?>"><i class="fas fa-tasks"></i> <span>Tasks</span></a></li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-footer p-3">
        <div class="user-info mb-2">
            <i class="fas fa-user-circle"></i>
            <div>
                <div><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                <small class="text-muted"><?= ucfirst($role) ?></small>
            </div>
        </div>
        <a href="/zaina-beauty/auth/logout.php" class="btn btn-danger btn-sm w-100">
            <i class="fas fa-sign-out-alt me-1"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content" id="main-content">
    <nav class="top-bar navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <button class="btn btn-outline-light" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-text"><?= $page_title ?></span>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- YOUR PAGE CONTENT GOES HERE -->