<?php
// modules/medicines.php — SAKHAWAT — Inventory Management
require_once '../includes/config.php';
requireLogin();

$pageTitle = "Medicine Inventory";
$root = "../";

// ── ADD MEDICINE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    requireRole(['admin','pharmacist']);
    $name      = clean($_POST['name']);
    $batch     = clean($_POST['batch_number']);
    $supplier  = clean($_POST['supplier']);
    $category  = clean($_POST['category']);
    $expiry    = clean($_POST['expiry_date']);
    $price     = (float)$_POST['price'];
    $qty       = (int)$_POST['quantity'];
    $threshold = (int)($_POST['low_stock_threshold'] ?? 10);

    mysqli_query($conn, "INSERT INTO medicines (name,batch_number,supplier,category,expiry_date,price,quantity,low_stock_threshold)
        VALUES ('$name','$batch','$supplier','$category','$expiry',$price,$qty,$threshold)");
    setMessage('success', "✅ Medicine '$name' added to inventory!");
    header("Location: medicines.php"); exit();
}

// ── UPDATE STOCK ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_stock') {
    requireRole(['admin','pharmacist']);
    $id  = (int)$_POST['id'];
    $qty = (int)$_POST['quantity'];
    mysqli_query($conn, "UPDATE medicines SET quantity=$qty WHERE id=$id");
    setMessage('success', "✅ Stock updated!");
    header("Location: medicines.php"); exit();
}

// ── EDIT MEDICINE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    requireRole(['admin','pharmacist']);
    $id       = (int)$_POST['id'];
    $name     = clean($_POST['name']);
    $batch    = clean($_POST['batch_number']);
    $supplier = clean($_POST['supplier']);
    $category = clean($_POST['category']);
    $expiry   = clean($_POST['expiry_date']);
    $price    = (float)$_POST['price'];
    $qty      = (int)$_POST['quantity'];
    $threshold= (int)$_POST['low_stock_threshold'];
    mysqli_query($conn, "UPDATE medicines SET name='$name',batch_number='$batch',supplier='$supplier',
        category='$category',expiry_date='$expiry',price=$price,quantity=$qty,low_stock_threshold=$threshold WHERE id=$id");
    setMessage('success', "✅ Medicine updated!");
    header("Location: medicines.php"); exit();
}

// ── DELETE ──
if (isset($_GET['delete'])) {
    requireRole(['admin']);
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM medicines WHERE id=$id");
    setMessage('success', "🗑️ Medicine deleted.");
    header("Location: medicines.php"); exit();
}

// ── FETCH EDIT ──
$editMed = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editMed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM medicines WHERE id=$id"));
}

// ── SEARCH + FILTER ──
$search = clean($_GET['search'] ?? '');
$filter = clean($_GET['filter'] ?? 'all');
$where  = "1=1";
if ($search) $where .= " AND (name LIKE '%$search%' OR supplier LIKE '%$search%' OR batch_number LIKE '%$search%')";
if ($filter === 'low')    $where .= " AND quantity <= low_stock_threshold";
if ($filter === 'expiry') $where .= " AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";

$medicines = mysqli_query($conn, "SELECT * FROM medicines WHERE $where ORDER BY name");

include '../includes/header.php';
?>

<?= getMessage() ?>

<!-- ADD / EDIT FORM -->
<?php if ($_SESSION['role'] !== 'manager'): ?>
<div class="card">
  <div class="card-title"><?= $editMed ? '✏️ Edit Medicine' : '➕ Add New Medicine' ?></div>
  <form method="POST">
    <input type="hidden" name="action" value="<?= $editMed ? 'edit' : 'add' ?>">
    <?php if ($editMed): ?><input type="hidden" name="id" value="<?= $editMed['id'] ?>"><?php endif; ?>
    <div class="form-grid">
      <div class="form-group">
        <label>Medicine Name</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($editMed['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Batch Number</label>
        <input type="text" name="batch_number" value="<?= htmlspecialchars($editMed['batch_number'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Supplier</label>
        <input type="text" name="supplier" value="<?= htmlspecialchars($editMed['supplier'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="category">
          <?php foreach (['Analgesic','Antibiotic','Antacid','Antidiabetic','Antihistamine','Antiplatelet','Supplement','Cardiovascular','Other'] as $cat): ?>
          <option <?= ($editMed['category']??'')===$cat?'selected':'' ?>><?= $cat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Expiry Date</label>
        <input type="date" name="expiry_date" required value="<?= $editMed['expiry_date'] ?? '' ?>">
      </div>
      <div class="form-group">
        <label>Price (৳)</label>
        <input type="number" name="price" step="0.01" min="0" required value="<?= $editMed['price'] ?? '' ?>">
      </div>
      <div class="form-group">
        <label>Quantity</label>
        <input type="number" name="quantity" min="0" required value="<?= $editMed['quantity'] ?? '' ?>">
      </div>
      <div class="form-group">
        <label>Low Stock Alert At</label>
        <input type="number" name="low_stock_threshold" min="1" value="<?= $editMed['low_stock_threshold'] ?? 10 ?>">
      </div>
    </div>
    <div class="flex gap-2 mt-3">
      <button type="submit" class="btn btn-primary"><?= $editMed ? '💾 Update' : '➕ Add Medicine' ?></button>
      <?php if ($editMed): ?><a href="medicines.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- SEARCH & FILTER -->
<div class="card">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <div class="form-group" style="flex:1;min-width:200px;">
      <label>Search</label>
      <input type="text" name="search" placeholder="Name, supplier, batch..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="form-group">
      <label>Filter</label>
      <select name="filter">
        <option value="all"    <?= $filter==='all'?'selected':'' ?>>All Medicines</option>
        <option value="low"    <?= $filter==='low'?'selected':'' ?>>Low Stock</option>
        <option value="expiry" <?= $filter==='expiry'?'selected':'' ?>>Expiring Soon</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">🔍 Search</button>
    <a href="medicines.php" class="btn btn-secondary">Clear</a>
  </form>
</div>

<!-- MEDICINES TABLE -->
<div class="card">
  <div class="card-title">📦 Stock List (<?= mysqli_num_rows($medicines) ?> items)</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Name</th><th>Batch</th><th>Supplier</th><th>Category</th><th>Expiry</th><th>Price</th><th>Qty</th><th>Status</th><?php if ($_SESSION['role']!=='manager'): ?><th>Actions</th><?php endif; ?></tr>
      </thead>
      <tbody>
      <?php while ($m = mysqli_fetch_assoc($medicines)):
        $isLow     = $m['quantity'] <= $m['low_stock_threshold'];
        $expDate   = strtotime($m['expiry_date']);
        $daysLeft  = round(($expDate - time()) / 86400);
        $isExpired = $daysLeft < 0;
        $isExpiring= $daysLeft >= 0 && $daysLeft <= 30;
        $rowClass  = $isExpired ? 'row-danger' : ($isLow || $isExpiring ? 'row-warning' : '');
      ?>
      <tr class="<?= $rowClass ?>">
        <td class="text-muted"><?= $m['id'] ?></td>
        <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
        <td style="font-family:monospace;font-size:12px;"><?= $m['batch_number'] ?></td>
        <td class="text-muted"><?= htmlspecialchars($m['supplier']) ?></td>
        <td><span class="badge badge-info"><?= $m['category'] ?></span></td>
        <td>
          <?= date('d M Y', $expDate) ?>
          <?php if ($isExpired): ?>
            <span class="badge badge-danger">Expired</span>
          <?php elseif ($isExpiring): ?>
            <span class="badge badge-warning"><?= $daysLeft ?>d left</span>
          <?php endif; ?>
        </td>
        <td>৳<?= number_format($m['price'], 2) ?></td>
        <td style="font-weight:700;font-family:monospace;color:<?= $isLow?'var(--danger)':'var(--accent)' ?>"><?= $m['quantity'] ?></td>
        <td>
          <?php if ($isExpired): ?>
            <span class="badge badge-danger">Expired</span>
          <?php elseif ($isLow): ?>
            <span class="badge badge-danger">Low Stock</span>
          <?php else: ?>
            <span class="badge badge-success">OK</span>
          <?php endif; ?>
        </td>
        <?php if ($_SESSION['role'] !== 'manager'): ?>
        <td>
          <div class="flex gap-2">
            <a href="medicines.php?edit=<?= $m['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
            <?php if ($_SESSION['role']==='admin'): ?>
            <a href="medicines.php?delete=<?= $m['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this medicine?')">🗑️</a>
            <?php endif; ?>
          </div>
        </td>
        <?php endif; ?>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
