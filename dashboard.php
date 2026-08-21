<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = "Dashboard";
$root = "";

// ── STATS ──
$totalMeds    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM medicines"))['c'];
$lowStock     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM medicines WHERE quantity <= low_stock_threshold"))['c'];
$expiringSoon = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE()"))['c'];
$todaySales   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total_amount),0) total FROM sales WHERE DATE(sale_date) = CURDATE()"))['total'];
$totalUsers   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];
$rxScannedToday = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM prescriptions WHERE DATE(created_at) = CURDATE()"))['c'];

// ── RECENT SALES ──
$recentSales = mysqli_query($conn, "SELECT s.*, u.full_name FROM sales s LEFT JOIN users u ON s.sold_by=u.id ORDER BY s.sale_date DESC LIMIT 5");

// ── LOW STOCK ──
$lowMeds = mysqli_query($conn, "SELECT * FROM medicines WHERE quantity <= low_stock_threshold ORDER BY quantity ASC LIMIT 5");

include 'includes/header.php';
?>

<?= getMessage() ?>

<!-- STAT CARDS -->
<div class="stats-grid">
  <div class="stat-card" style="--accent-color:#6ee7b7">
    <div class="stat-icon">💊</div>
    <div class="stat-value"><?= $totalMeds ?></div>
    <div class="stat-label">Total Medicines</div>
  </div>
  <div class="stat-card" style="--accent-color:#f87171">
    <div class="stat-icon">⚠️</div>
    <div class="stat-value"><?= $lowStock ?></div>
    <div class="stat-label">Low Stock Items</div>
  </div>
  <div class="stat-card" style="--accent-color:#fbbf24">
    <div class="stat-icon">⏰</div>
    <div class="stat-value"><?= $expiringSoon ?></div>
    <div class="stat-label">Expiring in 30 Days</div>
  </div>
  <div class="stat-card" style="--accent-color:#818cf8">
    <div class="stat-icon">💰</div>
    <div class="stat-value">৳<?= number_format($todaySales, 0) ?></div>
    <div class="stat-label">Today's Sales</div>
  </div>
  <div class="stat-card" style="--accent-color:#c084fc">
    <div class="stat-icon">🧾</div>
    <div class="stat-value"><?= $rxScannedToday ?></div>
    <div class="stat-label">Prescriptions Scanned Today</div>
  </div>
  <?php if ($_SESSION['role'] === 'admin'): ?>
  <div class="stat-card" style="--accent-color:#34d399">
    <div class="stat-icon">👥</div>
    <div class="stat-value"><?= $totalUsers ?></div>
    <div class="stat-label">System Users</div>
  </div>
  <?php endif; ?>
</div>

<!-- ALERTS -->
<?php if ($lowStock > 0): ?>
<div class="alert alert-danger">⚠️ <strong><?= $lowStock ?> medicine(s)</strong> are below minimum stock level! <a href="modules/medicines.php" style="color:inherit;font-weight:700;margin-left:8px;">View →</a></div>
<?php endif; ?>
<?php if ($expiringSoon > 0): ?>
<div class="alert alert-warning">⏰ <strong><?= $expiringSoon ?> medicine(s)</strong> are expiring within 30 days! <a href="modules/expiry.php" style="color:inherit;font-weight:700;margin-left:8px;">View →</a></div>
<?php endif; ?>
<?php if (in_array($_SESSION['role'], ['admin','pharmacist'])): ?>
<div class="alert" style="background:#c084fc20;border:1px solid #c084fc40;color:#c084fc;">
  🧾 New: <strong>Smart Prescription Scanner</strong> — upload a prescription photo and instantly check medicine availability.
  <a href="modules/prescription.php" style="color:inherit;font-weight:700;margin-left:8px;">Scan Now →</a>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

  <!-- RECENT SALES -->
  <div class="card">
    <div class="card-title">Recent Sales</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Invoice</th><th>Customer</th><th>Amount</th><th>Date</th></tr></thead>
        <tbody>
        <?php while ($s = mysqli_fetch_assoc($recentSales)): ?>
        <tr>
          <td><a href="modules/invoice.php?id=<?= $s['id'] ?>" style="color:var(--accent2);font-family:monospace;"><?= $s['invoice_number'] ?></a></td>
          <td><?= htmlspecialchars($s['customer_name']) ?></td>
          <td>৳<?= number_format($s['total_amount'], 2) ?></td>
          <td class="text-muted"><?= date('d M', strtotime($s['sale_date'])) ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if (mysqli_num_rows($recentSales) === 0): ?>
        <tr><td colspan="4" class="text-muted" style="text-align:center;padding:20px;">No sales yet today</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- LOW STOCK -->
  <div class="card">
    <div class="card-title">⚠️ Low Stock Alert</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Medicine</th><th>Qty</th><th>Min</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($m = mysqli_fetch_assoc($lowMeds)): ?>
        <tr class="row-danger">
          <td><?= htmlspecialchars($m['name']) ?></td>
          <td style="color:var(--danger);font-weight:700;font-family:monospace;"><?= $m['quantity'] ?></td>
          <td class="text-muted"><?= $m['low_stock_threshold'] ?></td>
          <td><span class="badge badge-danger">Low</span></td>
        </tr>
        <?php endwhile; ?>
        <?php if (mysqli_num_rows($lowMeds) === 0): ?>
        <tr><td colspan="4" class="text-muted" style="text-align:center;padding:20px;">✅ All stock levels OK</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include 'includes/footer.php'; ?>
