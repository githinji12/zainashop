<?php if (in_array($_SESSION['user_role'], ['admin', 'staff'])): ?>
    <li class="nav-item">
        <a class="nav-link" href="/zaina-beauty/invoices/create.php">
            <i class="fas fa-file-invoice"></i> Invoices
        </a>
    </li>
<?php endif; ?>