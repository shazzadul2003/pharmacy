<?php
// modules/invoice.php — Print Invoice
require_once '../includes/config.php';
requireLogin();

$id   = (int)($_GET['id'] ?? 0);
$sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, u.full_name as cashier FROM sales s LEFT JOIN users u ON s.sold_by=u.id WHERE s.id=$id"));

if (!$sale) { header("Location: sales_history.php"); exit(); }

$items = mysqli_query($conn, "SELECT si.*, m.name FROM sale_items si JOIN medicines m ON si.medicine_id=m.id WHERE si.sale_id=$id");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice <?= $sale['invoice_number'] ?></title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
<style>
  * { margin:0;padding:0;box-sizing:border-box; }
  body { font-family:'Space Grotesk',sans-serif; background:#0f1117; color:#e2e8f0; padding:40px; }
  .invoice { max-width:680px; margin:0 auto; background:#1a1d27; border:1px solid #2e3347; border-radius:12px; padding:40px; }
  .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:32px; border-bottom:2px solid #6ee7b7; padding-bottom:20px; }
  .brand { font-size:24px; font-weight:700; color:#6ee7b7; }
  .brand small { display:block; font-size:12px; color:#64748b; font-weight:400; margin-top:2px; }
  .invoice-meta { text-align:right; }
  .invoice-no { font-family:'JetBrains Mono'; font-size:16px; font-weight:600; color:#818cf8; }
  .date { font-size:12px; color:#64748b; margin-top:4px; }
  .section { margin-bottom:20px; }
  .label { font-size:11px; color:#64748b; font-weight:700; letter-spacing:1px; text-transform:uppercase; }
  .value { font-size:14px; margin-top:2px; }
  table { width:100%; border-collapse:collapse; margin:16px 0; }
  th { padding:8px 12px; text-align:left; font-size:11px; font-weight:700; letter-spacing:1px; color:#64748b; border-bottom:1px solid #2e3347; }
  td { padding:10px 12px; font-size:14px; border-bottom:1px solid #2e334750; }
  .total-row td { font-size:18px; font-weight:700; color:#6ee7b7; font-family:'JetBrains Mono'; border-top:2px solid #2e3347; border-bottom:none; }
  .footer { margin-top:32px; text-align:center; font-size:12px; color:#64748b; border-top:1px solid #2e3347; padding-top:16px; }
  .btn { display:inline-block; padding:10px 20px; background:#6ee7b7; color:#0f1117; font-weight:700; border-radius:7px; text-decoration:none; font-size:13px; cursor:pointer; border:none; font-family:inherit; }
  .btn-back { background:#22263a; color:#e2e8f0; margin-right:8px; }
  .actions { margin-bottom:24px; }
  @media print { .actions { display:none; } body { background:white; color:black; } .invoice { background:white; color:black; border-color:#ccc; } .invoice-no { color:#333; } .brand { color:#059669; } }
</style>
</head>
<body>
<div class="invoice">
  <div class="actions">
    <a href="sales_history.php" class="btn btn-back">← Back</a>
    <button class="btn" onclick="window.print()">🖨️ Print Invoice</button>
  </div>

  <div class="header">
    <div>
      <div class="brand">💊 PharmaMS<small>Pharmacy Management System</small></div>
    </div>
    <div class="invoice-meta">
      <div class="invoice-no"><?= $sale['invoice_number'] ?></div>
      <div class="date"><?= date('d M Y, h:i A', strtotime($sale['sale_date'])) ?></div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <div class="section">
      <div class="label">Customer</div>
      <div class="value"><?= htmlspecialchars($sale['customer_name']) ?></div>
      <?php if ($sale['customer_phone']): ?>
      <div style="font-size:12px;color:#64748b;"><?= $sale['customer_phone'] ?></div>
      <?php endif; ?>
    </div>
    <div class="section">
      <div class="label">Served By</div>
      <div class="value"><?= htmlspecialchars($sale['cashier']) ?></div>
    </div>
  </div>

  <table>
    <thead><tr><th>Medicine</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
    <tbody>
    <?php while ($item = mysqli_fetch_assoc($items)): ?>
    <tr>
      <td><?= htmlspecialchars($item['name']) ?></td>
      <td><?= $item['quantity'] ?></td>
      <td>৳<?= number_format($item['unit_price'], 2) ?></td>
      <td>৳<?= number_format($item['subtotal'], 2) ?></td>
    </tr>
    <?php endwhile; ?>
    <tr class="total-row">
      <td colspan="3">TOTAL</td>
      <td>৳<?= number_format($sale['total_amount'], 2) ?></td>
    </tr>
    </tbody>
  </table>

  <div class="footer">Thank you for your purchase! • PharmaMS</div>
</div>
</body>
</html>
