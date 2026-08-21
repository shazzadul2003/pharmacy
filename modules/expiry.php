<?php
// modules/expiry.php — KHADIJA — Expiry Tracking & Alerts
require_once '../includes/config.php';
requireLogin();

$pageTitle = "Expiry Tracking";
$root = "../";

// ── FETCH MEDICINES BY EXPIRY ──
$expired   = mysqli_query($conn, "SELECT * FROM medicines WHERE expiry_date < CURDATE() ORDER BY expiry_date ASC");
$expiring  = mysqli_query($conn, "SELECT * FROM medicines WHERE expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY expiry_date ASC");
$safe      = mysqli_query($conn, "SELECT * FROM medicines WHERE expiry_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY expiry_date ASC");

$cntExpired  = mysqli_num_rows($expired);
$cntExpiring = mysqli_num_rows($expiring);
$cntSafe     = mysqli_num_rows($safe);

include '../includes/header.php';
?>

<?= getMessage() ?>

<!-- SUMMARY CARDS -->
<div class="stats-grid">
  <div class="stat-card" style="--accent-color:#ef4444">
    <div class="stat-icon">💀</div>
    <div class="stat-value"><?= $cntExpired ?></div>
    <div class="stat-label">Expired Medicines</div>
  </div>
  <div class="stat-card" style="--accent-color:#fbbf24">
    <div class="stat-icon">⚠️</div>
    <div class="stat-value"><?= $cntExpiring ?></div>
    <div class="stat-label">Expiring in 30 Days</div>
  </div>
  <div class="stat-card" style="--accent-color:#10b981">
    <div class="stat-icon">✅</div>
    <div class="stat-value"><?= $cntSafe ?></div>
    <div class="stat-label">Safe / Valid</div>
  </div>
</div>

<!-- EXPIRED -->
<?php if ($cntExpired > 0): ?>
<div class="card">
  <div class="card-title" style="color:var(--danger)">💀 Expired Medicines — Remove Immediately!</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Medicine</th><th>Batch</th><th>Supplier</th><th>Expired On</th><th>Days Ago</th><th>Qty</th></tr></thead>
      <tbody>
      <?php while ($m = mysqli_fetch_assoc($expired)):
        $days = round((time() - strtotime($m['expiry_date'])) / 86400);
      ?>
      <tr class="row-danger">
        <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
        <td style="font-family:monospace;font-size:12px;"><?= $m['batch_number'] ?></td>
        <td class="text-muted"><?= htmlspecialchars($m['supplier']) ?></td>
        <td><?= date('d M Y', strtotime($m['expiry_date'])) ?></td>
        <td><span class="badge badge-danger"><?= $days ?> days ago</span></td>
        <td style="font-family:monospace;color:var(--danger)"><?= $m['quantity'] ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- EXPIRING SOON -->
<div class="card">
  <div class="card-title" style="color:var(--warning)">⏰ Expiring Within 30 Days</div>
  <?php if ($cntExpiring === 0): ?>
    <p class="text-muted">✅ No medicines expiring in the next 30 days.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Medicine</th><th>Batch</th><th>Supplier</th><th>Expiry Date</th><th>Days Left</th><th>Qty</th><th>Action</th></tr></thead>
      <tbody>
      <?php while ($m = mysqli_fetch_assoc($expiring)):
        $days = round((strtotime($m['expiry_date']) - time()) / 86400);
        $color = $days <= 7 ? 'var(--danger)' : 'var(--warning)';
      ?>
      <tr class="row-warning">
        <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
        <td style="font-family:monospace;font-size:12px;"><?= $m['batch_number'] ?></td>
        <td class="text-muted"><?= htmlspecialchars($m['supplier']) ?></td>
        <td><?= date('d M Y', strtotime($m['expiry_date'])) ?></td>
        <td><span class="badge" style="background:<?= $color ?>20;color:<?= $color ?>"><?= $days ?> days</span></td>
        <td style="font-family:monospace;"><?= $m['quantity'] ?></td>
        <td><a href="medicines.php?edit=<?= $m['id'] ?>" class="btn btn-sm btn-warning">Update</a></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- SAFE MEDICINES -->
<div class="card">
  <div class="card-title" style="color:var(--accent)">✅ Valid Stock (<?= $cntSafe ?> items)</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Medicine</th><th>Category</th><th>Expiry Date</th><th>Days Remaining</th><th>Qty</th></tr></thead>
      <tbody>
      <?php while ($m = mysqli_fetch_assoc($safe)):
        $days = round((strtotime($m['expiry_date']) - time()) / 86400);
      ?>
      <tr>
        <td><?= htmlspecialchars($m['name']) ?></td>
        <td><span class="badge badge-info"><?= $m['category'] ?></span></td>
        <td><?= date('d M Y', strtotime($m['expiry_date'])) ?></td>
        <td><span class="badge badge-success"><?= $days ?> days</span></td>
        <td style="font-family:monospace;"><?= $m['quantity'] ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
