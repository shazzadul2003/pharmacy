<?php
// modules/sales.php — SAZZAD — New Sale / POS
require_once '../includes/config.php';
requireLogin();

$pageTitle = "New Sale";
$root = "../";

// ── PROCESS SALE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name  = clean($_POST['customer_name'] ?? 'Walk-in Customer');
    $customer_phone = clean($_POST['customer_phone'] ?? '');
    $items          = $_POST['items'] ?? [];   // array of [medicine_id, qty]

    if (empty($items)) {
        setMessage('error', "❌ Add at least one medicine to the sale.");
        header("Location: sales.php"); exit();
    }

    // Build sale
    $total = 0;
    $lineItems = [];
    foreach ($items as $item) {
        $med_id = (int)$item['medicine_id'];
        $qty    = (int)$item['quantity'];
        if ($med_id <= 0 || $qty <= 0) continue;

        $med = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM medicines WHERE id=$med_id"));
        if (!$med) continue;
        if ($med['quantity'] < $qty) {
            setMessage('error', "❌ Not enough stock for: {$med['name']}. Available: {$med['quantity']}");
            header("Location: sales.php"); exit();
        }
        $subtotal = $med['price'] * $qty;
        $total += $subtotal;
        $lineItems[] = ['id' => $med_id, 'qty' => $qty, 'price' => $med['price'], 'subtotal' => $subtotal];
    }

    if (empty($lineItems)) {
        setMessage('error', "❌ No valid items selected.");
        header("Location: sales.php"); exit();
    }

    // Generate invoice number
    $invoice = 'INV-' . date('Ymd') . '-' . str_pad(rand(1,999), 3, '0', STR_PAD_LEFT);
    $userId  = $_SESSION['user_id'];

    mysqli_query($conn, "INSERT INTO sales (invoice_number,customer_name,customer_phone,total_amount,sold_by) VALUES ('$invoice','$customer_name','$customer_phone',$total,$userId)");
    $saleId = mysqli_insert_id($conn);

    foreach ($lineItems as $li) {
        mysqli_query($conn, "INSERT INTO sale_items (sale_id,medicine_id,quantity,unit_price,subtotal) VALUES ($saleId,{$li['id']},{$li['qty']},{$li['price']},{$li['subtotal']})");
        mysqli_query($conn, "UPDATE medicines SET quantity = quantity - {$li['qty']} WHERE id={$li['id']}");
    }

    header("Location: invoice.php?id=$saleId");
    exit();
}

// ── MEDICINES FOR DROPDOWN ──
$medicines = mysqli_query($conn, "SELECT * FROM medicines WHERE quantity > 0 AND expiry_date >= CURDATE() ORDER BY name");

include '../includes/header.php';
?>

<?= getMessage() ?>

<div class="card">
  <div class="card-title">🛒 Record New Sale</div>
  <form method="POST" id="saleForm">

    <div class="form-grid" style="margin-bottom:20px;">
      <div class="form-group">
        <label>Customer Name</label>
        <input type="text" name="customer_name" placeholder="Walk-in Customer">
      </div>
      <div class="form-group">
        <label>Customer Phone</label>
        <input type="text" name="customer_phone" placeholder="Optional">
      </div>
    </div>

    <hr class="divider">
    <div class="card-title">💊 Add Medicines</div>

    <div id="items-container">
      <div class="item-row" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end;margin-bottom:12px;">
        <div class="form-group" style="margin:0">
          <label>Medicine</label>
          <select name="items[0][medicine_id]" class="med-select" onchange="updatePrice(this)">
            <option value="">— Select Medicine —</option>
            <?php
            $medList = [];
            while ($m = mysqli_fetch_assoc($medicines)) {
                $medList[] = $m;
                echo "<option value='{$m['id']}' data-price='{$m['price']}' data-stock='{$m['quantity']}'>{$m['name']} (Stock: {$m['quantity']}) — ৳{$m['price']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="form-group" style="margin:0">
          <label>Qty</label>
          <input type="number" name="items[0][quantity]" min="1" value="1" class="qty-input" onchange="calcTotal()">
        </div>
        <div class="form-group" style="margin:0">
          <label>Subtotal</label>
          <input type="text" class="subtotal-display" readonly placeholder="৳0.00" style="background:var(--bg);color:var(--accent);">
        </div>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)" style="margin-bottom:2px">✕</button>
      </div>
    </div>

    <button type="button" class="btn btn-secondary" onclick="addRow()" style="margin-bottom:20px">➕ Add Another Medicine</button>

    <hr class="divider">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <div style="font-size:22px;font-weight:700;">
        Total: <span id="grand-total" style="color:var(--accent);font-family:monospace;">৳0.00</span>
      </div>
      <button type="submit" class="btn btn-primary" style="font-size:15px;padding:12px 28px;">💰 Complete Sale</button>
    </div>
  </form>
</div>

<script>
let rowIndex = 1;
const medOptions = document.querySelector('.med-select').innerHTML;

function addRow() {
  const container = document.getElementById('items-container');
  const div = document.createElement('div');
  div.className = 'item-row';
  div.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end;margin-bottom:12px;';
  div.innerHTML = `
    <div class="form-group" style="margin:0"><label>Medicine</label>
      <select name="items[${rowIndex}][medicine_id]" class="med-select" onchange="updatePrice(this)">${medOptions}</select></div>
    <div class="form-group" style="margin:0"><label>Qty</label>
      <input type="number" name="items[${rowIndex}][quantity]" min="1" value="1" class="qty-input" onchange="calcTotal()"></div>
    <div class="form-group" style="margin:0"><label>Subtotal</label>
      <input type="text" class="subtotal-display" readonly placeholder="৳0.00" style="background:var(--bg);color:var(--accent);"></div>
    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)" style="margin-bottom:2px">✕</button>`;
  container.appendChild(div);
  rowIndex++;
}

function removeRow(btn) {
  const rows = document.querySelectorAll('.item-row');
  if (rows.length > 1) { btn.closest('.item-row').remove(); calcTotal(); }
}

function updatePrice(sel) {
  const opt = sel.selectedOptions[0];
  const price = parseFloat(opt.dataset.price || 0);
  const row = sel.closest('.item-row');
  const qty = parseFloat(row.querySelector('.qty-input').value || 1);
  row.querySelector('.subtotal-display').value = '৳' + (price * qty).toFixed(2);
  calcTotal();
}

function calcTotal() {
  let total = 0;
  document.querySelectorAll('.item-row').forEach(row => {
    const sel = row.querySelector('.med-select');
    const opt = sel.selectedOptions[0];
    const price = parseFloat(opt?.dataset.price || 0);
    const qty = parseFloat(row.querySelector('.qty-input').value || 0);
    const sub = price * qty;
    row.querySelector('.subtotal-display').value = price ? '৳' + sub.toFixed(2) : '';
    total += sub;
  });
  document.getElementById('grand-total').textContent = '৳' + total.toFixed(2);
}
</script>

<?php include '../includes/footer.php'; ?>
