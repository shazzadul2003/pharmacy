<?php
// modules/prescription.php — OCR-BASED PRESCRIPTION SCANNING & MEDICINE AVAILABILITY
// New team member module: adds smart prescription upload, in-browser OCR
// (Tesseract.js — no server OCR engine required), fuzzy matching against
// the medicines table, and live availability/stock lookup.
require_once '../includes/config.php';
require_once '../includes/ocr_match.php';
requireLogin();

$pageTitle = "Prescription Scanner";
$root = "../";
$role = $_SESSION['role'];

// ── SAVE SCANNED PRESCRIPTION ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_scan') {
    requireRole(['admin', 'pharmacist']);

    $patientName  = clean($_POST['patient_name'] ?: 'Walk-in Patient');
    $patientPhone = clean($_POST['patient_phone']);
    $ocrText      = trim($_POST['ocr_text'] ?? '');
    $confidence   = (float)($_POST['ocr_confidence'] ?? 0);

    if ($ocrText === '') {
        setMessage('error', "⚠️ No text was recognized from the image. Try a clearer photo.");
        header("Location: prescription.php"); exit();
    }

    // ── Handle file upload ──
    $imagePath = '';
    if (isset($_FILES['prescription_image']) && $_FILES['prescription_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/prescriptions/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['prescription_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) {
            setMessage('error', "⚠️ Only JPG, PNG or WEBP images are allowed.");
            header("Location: prescription.php"); exit();
        }
        $fileName = 'rx_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        $target = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['prescription_image']['tmp_name'], $target)) {
            $imagePath = 'uploads/prescriptions/' . $fileName;
        }
    }

    if ($imagePath === '') {
        setMessage('error', "⚠️ Prescription image upload failed. Please try again.");
        header("Location: prescription.php"); exit();
    }

    // ── Run the fuzzy match engine ──
    $matches = matchPrescriptionText($conn, $ocrText);
    $matchedCount = count(array_filter($matches, fn($m) => $m['medicine'] !== null));

    $ocrTextEsc = clean($ocrText);
    mysqli_query($conn, "INSERT INTO prescriptions
        (patient_name, patient_phone, image_path, ocr_text, ocr_confidence, matched_count, scanned_by)
        VALUES ('$patientName','$patientPhone','$imagePath','$ocrTextEsc',$confidence,$matchedCount,{$_SESSION['user_id']})");
    $prescriptionId = mysqli_insert_id($conn);

    foreach ($matches as $m) {
        $text = clean($m['candidate_text']);
        $medId = $m['medicine'] ? (int)$m['medicine']['id'] : 'NULL';
        $score = (float)$m['score'];
        $qty   = (int)$m['available_qty'];
        mysqli_query($conn, "INSERT INTO prescription_items
            (prescription_id, matched_text, medicine_id, match_score, available_qty)
            VALUES ($prescriptionId, '$text', $medId, $score, $qty)");
    }

    setMessage('success', "✅ Prescription scanned — $matchedCount medicine(s) matched in inventory!");
    header("Location: prescription.php?view=$prescriptionId"); exit();
}

// ── DELETE ──
if (isset($_GET['delete'])) {
    requireRole(['admin']);
    $id = (int)$_GET['delete'];
    $img = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_path FROM prescriptions WHERE id=$id"));
    if ($img && file_exists('../' . $img['image_path'])) @unlink('../' . $img['image_path']);
    mysqli_query($conn, "DELETE FROM prescriptions WHERE id=$id");
    setMessage('success', "🗑️ Prescription record deleted.");
    header("Location: prescription.php"); exit();
}

// ── VIEW SINGLE PRESCRIPTION ──
$viewRx = null;
$viewItems = [];
if (isset($_GET['view'])) {
    $id = (int)$_GET['view'];
    $viewRx = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, u.full_name AS scanned_by_name
        FROM prescriptions p LEFT JOIN users u ON p.scanned_by = u.id WHERE p.id=$id"));
    if ($viewRx) {
        $itemsRes = mysqli_query($conn, "SELECT pi.*, m.name AS medicine_name, m.price, m.quantity AS current_stock, m.expiry_date
            FROM prescription_items pi LEFT JOIN medicines m ON pi.medicine_id = m.id
            WHERE pi.prescription_id=$id ORDER BY pi.id");
        while ($row = mysqli_fetch_assoc($itemsRes)) $viewItems[] = $row;
    }
}

// ── HISTORY LIST ──
$search = clean($_GET['search'] ?? '');
$where = "1=1";
if ($search) $where .= " AND (p.patient_name LIKE '%$search%' OR p.patient_phone LIKE '%$search%')";
$history = mysqli_query($conn, "SELECT p.*, u.full_name AS scanned_by_name
    FROM prescriptions p LEFT JOIN users u ON p.scanned_by = u.id
    WHERE $where ORDER BY p.created_at DESC LIMIT 50");

include '../includes/header.php';
?>

<?= getMessage() ?>

<?php if ($viewRx): ?>
<!-- ═══════════ SINGLE PRESCRIPTION VIEW ═══════════ -->
<div class="card">
  <div class="flex" style="justify-content:space-between;align-items:center;">
    <div class="card-title" style="margin-bottom:0;">🧾 Prescription #<?= $viewRx['id'] ?> — <?= htmlspecialchars($viewRx['patient_name']) ?></div>
    <a href="prescription.php" class="btn btn-secondary btn-sm">← Back to Scanner</a>
  </div>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:24px;margin-top:16px;">
    <div>
      <img src="../<?= htmlspecialchars($viewRx['image_path']) ?>" alt="Prescription"
           style="width:100%;border-radius:8px;border:1px solid var(--border);">
      <div class="text-muted" style="margin-top:8px;font-size:12px;">
        Scanned by <?= htmlspecialchars($viewRx['scanned_by_name'] ?? 'Unknown') ?><br>
        <?= date('d M Y, h:i A', strtotime($viewRx['created_at'])) ?><br>
        OCR Confidence: <strong><?= number_format($viewRx['ocr_confidence'], 1) ?>%</strong>
      </div>
    </div>

    <div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>Raw OCR Text (as recognized from image)</label>
        <textarea readonly rows="5" style="width:100%;font-family:'JetBrains Mono',monospace;font-size:12.5px;background:var(--surface2);border:1px solid var(--border);border-radius:6px;padding:10px;color:var(--text-muted);"><?= htmlspecialchars($viewRx['ocr_text']) ?></textarea>
      </div>

      <div class="card-title">💊 Matched Medicines &amp; Availability</div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Detected Text</th><th>Matched Medicine</th><th>Match %</th><th>Stock</th><th>Price</th><th>Availability</th></tr>
          </thead>
          <tbody>
          <?php foreach ($viewItems as $it):
              $found = $it['medicine_id'] !== null;
              $inStock = $found && $it['current_stock'] > 0;
          ?>
            <tr class="<?= !$found ? 'row-danger' : (!$inStock ? 'row-warning' : '') ?>">
              <td><?= htmlspecialchars($it['matched_text']) ?></td>
              <td><?= $found ? '<strong>'.htmlspecialchars($it['medicine_name']).'</strong>' : '<span class="text-muted">— no match —</span>' ?></td>
              <td><?= $found ? number_format($it['match_score'],1).'%' : '—' ?></td>
              <td><?= $found ? $it['current_stock'] : '—' ?></td>
              <td><?= $found ? '৳'.number_format($it['price'],2) : '—' ?></td>
              <td>
                <?php if (!$found): ?>
                  <span class="badge badge-danger">Not in Inventory</span>
                <?php elseif (!$inStock): ?>
                  <span class="badge badge-danger">Out of Stock</span>
                <?php elseif ($it['current_stock'] <= 10): ?>
                  <span class="badge badge-warning">Low Stock</span>
                <?php else: ?>
                  <span class="badge badge-success">Available</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($viewItems)): ?>
            <tr><td colspan="6" class="text-muted" style="text-align:center;">No medicine lines detected in this prescription.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (in_array($role, ['admin','pharmacist'])): ?>
      <div class="mt-3">
        <a href="sales.php" class="btn btn-primary">🛒 Proceed to Sale</a>
        <a href="prescription.php" class="btn btn-secondary">🔄 Scan Another</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ═══════════ SCAN NEW PRESCRIPTION ═══════════ -->
<?php if (in_array($role, ['admin','pharmacist'])): ?>
<div class="card">
  <div class="card-title">📷 Scan a New Prescription</div>
  <p class="text-muted" style="margin-top:-8px;margin-bottom:16px;">
    Upload a photo of a handwritten or printed prescription. The browser will read the text
    on-device using OCR, then we'll match each drug line against your live inventory.
  </p>

  <form id="rxForm" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_scan">
    <input type="hidden" name="ocr_text" id="ocrTextField">
    <input type="hidden" name="ocr_confidence" id="ocrConfidenceField" value="0">

    <div class="form-grid">
      <div class="form-group">
        <label>Patient Name</label>
        <input type="text" name="patient_name" placeholder="Walk-in Patient">
      </div>
      <div class="form-group">
        <label>Patient Phone</label>
        <input type="text" name="patient_phone" placeholder="Optional">
      </div>
    </div>

    <div class="form-group mt-3">
      <label>Prescription Image</label>
      <input type="file" name="prescription_image" id="rxImageInput" accept="image/png,image/jpeg,image/webp" required>
    </div>

    <div id="previewWrap" style="display:none;margin-top:16px;">
      <div style="display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start;">
        <img id="rxPreview" style="width:100%;border-radius:8px;border:1px solid var(--border);">
        <div>
          <div id="ocrStatus" class="text-muted" style="margin-bottom:8px;font-size:13px;">Waiting for image…</div>
          <div style="background:var(--surface2);border-radius:6px;height:8px;overflow:hidden;margin-bottom:16px;">
            <div id="ocrProgressBar" style="background:var(--accent);height:100%;width:0%;transition:width .2s;"></div>
          </div>
          <div class="form-group">
            <label>Recognized Text <span class="text-muted" style="font-weight:400;">(edit to correct OCR mistakes before saving)</span></label>
            <textarea id="ocrTextArea" rows="6" placeholder="Recognized prescription text will appear here…" style="width:100%;font-family:'JetBrains Mono',monospace;font-size:12.5px;"></textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-3">
      <button type="submit" class="btn btn-primary" id="rxSubmitBtn" disabled>💾 Match &amp; Save Prescription</button>
      <span class="text-muted" id="rxSubmitHint" style="margin-left:8px;">Upload an image to enable saving.</span>
    </div>
  </form>
</div>
<?php else: ?>
<div class="card">
  <p class="text-muted">Your role (<?= ucfirst($role) ?>) can view prescription history below, but only Admin/Pharmacist can scan new prescriptions.</p>
</div>
<?php endif; ?>

<!-- HISTORY -->
<div class="card">
  <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div class="card-title" style="margin-bottom:0;">📋 Scan History</div>
    <form method="GET" style="display:flex;gap:8px;">
      <input type="text" name="search" placeholder="Search patient..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-secondary btn-sm">🔍</button>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Patient</th><th>Phone</th><th>Matched</th><th>Confidence</th><th>Scanned By</th><th>Date</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php while ($rx = mysqli_fetch_assoc($history)): ?>
        <tr>
          <td class="text-muted"><?= $rx['id'] ?></td>
          <td><strong><?= htmlspecialchars($rx['patient_name']) ?></strong></td>
          <td class="text-muted"><?= htmlspecialchars($rx['patient_phone'] ?: '—') ?></td>
          <td><span class="badge badge-info"><?= $rx['matched_count'] ?> item(s)</span></td>
          <td><?= number_format($rx['ocr_confidence'], 1) ?>%</td>
          <td class="text-muted"><?= htmlspecialchars($rx['scanned_by_name'] ?? '—') ?></td>
          <td class="text-muted"><?= date('d M Y, h:i A', strtotime($rx['created_at'])) ?></td>
          <td>
            <div class="flex gap-2">
              <a href="prescription.php?view=<?= $rx['id'] ?>" class="btn btn-sm btn-secondary">👁️ View</a>
              <?php if ($role === 'admin'): ?>
              <a href="prescription.php?delete=<?= $rx['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this prescription record?')">🗑️</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      <?php if (mysqli_num_rows($history) === 0): ?>
        <tr><td colspan="8" class="text-muted" style="text-align:center;">No prescriptions scanned yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Tesseract.js — in-browser OCR engine (CDN, no server install needed) -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script>
const rxImageInput   = document.getElementById('rxImageInput');
const previewWrap    = document.getElementById('previewWrap');
const rxPreview      = document.getElementById('rxPreview');
const ocrStatus      = document.getElementById('ocrStatus');
const ocrProgressBar = document.getElementById('ocrProgressBar');
const ocrTextArea    = document.getElementById('ocrTextArea');
const ocrTextField   = document.getElementById('ocrTextField');
const ocrConfField   = document.getElementById('ocrConfidenceField');
const rxSubmitBtn    = document.getElementById('rxSubmitBtn');
const rxSubmitHint   = document.getElementById('rxSubmitHint');

if (rxImageInput) {
  rxImageInput.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    previewWrap.style.display = 'block';
    rxPreview.src = URL.createObjectURL(file);
    rxSubmitBtn.disabled = true;
    ocrTextArea.value = '';
    ocrStatus.textContent = '🔎 Reading prescription text on-device…';
    ocrProgressBar.style.width = '0%';

    try {
      const result = await Tesseract.recognize(file, 'eng', {
        logger: (m) => {
          if (m.status === 'recognizing text') {
            const pct = Math.round(m.progress * 100);
            ocrProgressBar.style.width = pct + '%';
            ocrStatus.textContent = `🔎 Recognizing text… ${pct}%`;
          } else {
            ocrStatus.textContent = '⏳ ' + m.status;
          }
        }
      });

      ocrTextArea.value = result.data.text.trim();
      ocrConfField.value = result.data.confidence || 0;
      ocrStatus.textContent = `✅ Done — confidence ${Math.round(result.data.confidence)}%. Review/edit the text below, then save.`;
      rxSubmitBtn.disabled = false;
      rxSubmitHint.textContent = '';
    } catch (err) {
      console.error(err);
      ocrStatus.textContent = '❌ OCR failed. You can still type the prescription text manually below.';
      rxSubmitBtn.disabled = false;
      rxSubmitHint.textContent = '';
    }
  });
}

const rxForm = document.getElementById('rxForm');
if (rxForm) {
  rxForm.addEventListener('submit', (e) => {
    ocrTextField.value = ocrTextArea.value.trim();
    if (!ocrTextField.value) {
      e.preventDefault();
      alert('Please wait for OCR to finish, or type the prescription text manually.');
    }
  });
}
</script>

<?php include '../includes/footer.php'; ?>
