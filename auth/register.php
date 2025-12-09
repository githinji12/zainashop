<?php
// views/auth/register.php
session_start();
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('views/admin/dashboard.php');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Register – <?= APP_NAME ?></title>
  <link href="<?= ASSETS_URL ?>css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; display:flex; align-items:center; justify-content:center; height:100vh; }
    .auth-box { width: 100%; max-width: 500px; padding:2rem; background:white; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
  </style>
</head>
<body>

<div class="auth-box">
  <h3 class="text-center mb-4">Register</h3>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>controllers/AuthController.php?action=register">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Full Name</label>
        <input name="full_name" type="text" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Username</label>
        <input name="username" type="text" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input name="phone" type="tel" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Confirm Password</label>
        <input name="password_confirm" type="password" class="form-control" required>
      </div>
    </div>
    <button class="btn btn-success w-100 mt-4">Register</button>
  </form>

  <p class="text-center mt-3 small">
    Already have an account? 
    <a href="<?= BASE_URL ?>views/auth/login.php">Login here</a>
  </p>
</div>

<script src="<?= ASSETS_URL ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>