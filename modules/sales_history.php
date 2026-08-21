<?php
// modules/sales_history.php — SAZZAD — Sales History & Search
require_once '../includes/config.php';
requireLogin();

$pageTitle = "Sales History";
$root = "../";

// ── SEARCH FILTERS ──
$search   = clean($_GET['search'] ?? '');
$dateFrom = clean($_GET['date_from'] ?? '');
$dateTo   = clean($_GET['date_to'] ?? '');

$where = "1=1";
if ($search) $where .= " AND (s.invoice_number LIKE '%$search%' OR s.customer_name LIKE '%$search%' OR s.customer_phone LIKE '%$search%')";
if ($dateFrom) $where .= " AND DATE(s.sale_date) >= '$dateFrom'";
if ($dateTo)   $where .= " AND DATE(s.sale_date) <= '$dateTo'";

$sales = mysqli_query($conn, "SELECT s.*, u.full_name as cashier, COUNT(si.id) as item_count
    FROM sales s
    LEFT JOIN users u ON s.sold_by = u.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE $where
    GROUP BY s.id
    ORDER BY s.sale_date DESC");

$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total_amount),0) t FROM sales s WHERE $where"))['t'];

include '../includes/header.php';
?>

<?= getMessage() ?>

<!-- SEARCH -->
<div class="card">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <div class="form-group" style="flex:1;min-width:180px;">
      <label>Search</label>
      <input type="text" name="search" placeholder="Invoice, customer, phone..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="form-group">
      <label>From Date</label>
      <input type="date" name="date_from" value="<?= $dateFrom ?>">
    </div>
    <div class="form-group">
      <label>To Date</label>
      <input type="date" name="date_to" value="<?= $dateTo ?>">
    </div>
    <button type="submit" class="btn btn-primary">🔍 Filter</button>
    <a href="sales_history.php" class="btn btn-secondary">Clear</a>
  </form>
</div>

<!-- SUMMARY -->
<div class="stats-grid" style="margin-bottom:20px;">
  <div class="stat-card" style="--accent-color:#818cf8">
    <div class="stat-icon">📋</div>
    <div class="stat-value"><?= mysqli_num_rows($sales) ?></div>
    <div class="stat-label">Transactions</div>
  </div>
  <div class="stat-card" style="--accent-color:#6ee7b7">
    <div class="stat-icon">💰</div>
    <div class="stat-value">৳<?= number_format($totalRevenue, 0) ?></div>
    <div class="stat-label">Total Revenue</div>
  </div>
</div>

<!-- SALES TABLE -->
<div class="card">
  <div class="card-title" style="display:flex;justify-content:space-between;align-items:center;">
    <span>📋 Sales Records</span>
    <a href="sales.php" class="btn btn-primary btn-sm">➕ New Sale</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Invoice</th><th>Customer</th><th>Items</th><th>Amount</th><th>Cashier</th><th>Date</th><th>Action</th></tr>
      </thead>
      <tbody>
      <?php while ($s = mysqli_fetch_assoc($sales)): ?>
      <tr>
        <td style="font-family:monospace;color:var(--accent2)"><?= $s['invoice_number'] ?></td>
        <td>
          <?= htmlspecialchars($s['customer_name']) ?>
          <?php if ($s['customer_phone']): ?>
          <div class="text-muted" style="font-size:11px;"><?= $s['customer_phone'] ?></div>
          <?php endif; ?>
        </td>
        <td><span class="badge badge-info"><?= $s['item_count'] ?> items</span></td>
        <td style="font-weight:700;color:var(--accent);font-family:monospace;">৳<?= number_format($s['total_amount'], 2) ?></td>
        <td class="text-muted"><?= htmlspecialchars($s['cashier'] ?? '-') ?></td>
        <td class="text-muted"><?= date('d M Y H:i', strtotime($s['sale_date'])) ?></td>
        <td><a href="invoice.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary">🧾 Invoice</a></td>
      </tr>
      <?php endwhile; ?>
      <?php if (mysqli_num_rows($sales) === 0): ?>
      <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted);">No sales records found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
