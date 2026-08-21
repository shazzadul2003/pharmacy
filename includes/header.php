<?php
// includes/header.php — shared navigation for all pages
requireLogin();
$role = $_SESSION['role'];
$name = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Pharmacy MS' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $root ?? '../' ?>css/style.css">
</head>
<body>
<div class="layout">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <span class="brand-icon">💊</span>
      <span class="brand-name">PharmaMS</span>
    </div>

    <nav class="sidebar-nav">
      <a href="<?= $root ?? '../' ?>dashboard.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='dashboard.php')?'active':'' ?>">
        <span>📊</span> Dashboard
      </a>

      <?php if (in_array($role, ['admin','manager','pharmacist'])): ?>
      <div class="nav-group">INVENTORY</div>
      <a href="<?= $root ?? '../' ?>modules/medicines.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='medicines.php')?'active':'' ?>">
        <span>💉</span> Medicines
      </a>
      <a href="<?= $root ?? '../' ?>modules/expiry.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='expiry.php')?'active':'' ?>">
        <span>⏰</span> Expiry Alerts
      </a>
      <?php endif; ?>

      <?php if (in_array($role, ['admin','manager','pharmacist'])): ?>
      <div class="nav-group">SMART TOOLS</div>
      <a href="<?= $root ?? '../' ?>modules/prescription.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='prescription.php')?'active':'' ?>">
        <span>🧾</span> Prescription Scanner
      </a>
      <?php endif; ?>

      <?php if (in_array($role, ['admin','manager','pharmacist'])): ?>
      <div class="nav-group">SALES</div>
      <a href="<?= $root ?? '../' ?>modules/sales.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='sales.php')?'active':'' ?>">
        <span>🛒</span> New Sale
      </a>
      <a href="<?= $root ?? '../' ?>modules/sales_history.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='sales_history.php')?'active':'' ?>">
        <span>📋</span> Sales History
      </a>
      <?php endif; ?>

      <?php if ($role === 'admin'): ?>
      <div class="nav-group">ADMIN</div>
      <a href="<?= $root ?? '../' ?>modules/users.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='users.php')?'active':'' ?>">
        <span>👥</span> User Management
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($name,0,1)) ?></div>
        <div>
          <div class="user-name"><?= htmlspecialchars($name) ?></div>
          <div class="user-role"><?= ucfirst($role) ?></div>
        </div>
      </div>
      <a href="<?= $root ?? '../' ?>logout.php" class="btn-logout">Logout</a>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main-content">
    <div class="page-header">
      <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
    </div>
    <div class="content-body">
