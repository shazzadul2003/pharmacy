# 💊 Smart Pharmacy Management System
### Group Project — HTML + PHP + CSS + MySQL + In-Browser OCR

---

## 📁 Project Structure

```
pharmacy/
├── login.php               ← Login page
├── logout.php              ← Logout
├── dashboard.php           ← Main dashboard
├── database.sql            ← Import this into MySQL!
├── css/
│   └── style.css           ← All styles
├── includes/
│   ├── config.php          ← DB connection + helpers
│   ├── header.php          ← Shared navbar/sidebar
│   ├── footer.php          ← Shared footer
│   └── ocr_match.php       ← 🧠 Fuzzy drug-name matching engine
├── uploads/
│   └── prescriptions/      ← Uploaded prescription photos land here
└── modules/
    ├── users.php           ← 👤 User Management (Ayon bhai)
    ├── medicines.php       ← 💉 Inventory (sakhawat)
    ├── expiry.php          ← ⏰ Expiry Alerts (khadija)
    ├── sales.php           ← 🛒 New Sale (sazzad)
    ├── sales_history.php   ← 📋 Sales Records (sazzad)
    ├── invoice.php         ← 🧾 Invoice Print (sazzad)
    └── prescription.php    ← 📷 OCR Prescription Scanner (NEW)
```

---

## 🚀 How to Run (XAMPP)

### Step 1 — Copy the project
Copy the `pharmacy/` folder to:
```
C:\xampp\htdocs\pharmacy\
```

### Step 2 — Import the database
1. Open your browser → go to `http://localhost/phpmyadmin`
2. Click **"New"** on the left → create a database named `pharmacy_db`
3. Click on `pharmacy_db` → click **"Import"** tab at the top
4. Choose the file `pharmacy/database.sql` → click **"Go"**

### Step 3 — Configure database
Open `includes/config.php` and set:
```php
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password (blank by default in XAMPP)
```

### Step 4 — Open in browser
```
http://localhost/pharmacy/login.php
```

---

## 🔐 Login Credentials

| Role        | Username     | Password   |
|-------------|--------------|------------|
| **Admin**   | `admin`      | `password` |
| Pharmacist  | `pharmacist` | `password` |
| Manager     | `manager`    | `password` |

---

## 👥 Who Does What

| Module              | Team Member  | File                          |
|---------------------|--------------|-------------------------------|
| Login / Logout      | Ayon bhai    | `login.php`, `logout.php`     |
| User Management     | Ayon bhai    | `modules/users.php`           |
| Inventory / Stock   | sakhawat     | `modules/medicines.php`       |
| Expiry Alerts       | khadija      | `modules/expiry.php`          |
| Sales / Invoice     | sazzad       | `modules/sales.php`, etc.     |
| 🧠 Prescription OCR Scanner | (you) | `modules/prescription.php`, `includes/ocr_match.php` |

---

## ✅ Features Implemented

### 1️⃣ User Management (Ayon bhai)
- [x] Login with username & password
- [x] Logout
- [x] Role-based access: Admin, Pharmacist, Manager
- [x] Add / Edit / Delete users (Admin only)
- [x] Password hashing (secure, using PHP password_hash)
- [x] Active / Inactive user status

### 2️⃣ Inventory Management (sakhawat)
- [x] Add medicine (name, batch, supplier, category, expiry, price, qty)
- [x] Edit / Update medicine details
- [x] Delete medicine (Admin only)
- [x] View all stock
- [x] Low stock alert (highlighted in red)
- [x] Search by name, supplier, batch number
- [x] Filter: All / Low Stock / Expiring Soon

### 3️⃣ Expiry Tracking (khadija)
- [x] Automatic expiry date checking
- [x] Color-coded: Red = Expired, Yellow = ≤30 days, Green = Safe
- [x] Expired medicines list (remove immediately)
- [x] Expiring soon list with days remaining
- [x] Summary stats on dashboard

### 4️⃣ Sales (sazzad)
- [x] Record sales with multiple items
- [x] Auto-deducts stock after each sale
- [x] Unique invoice number generation
- [x] Printable invoice
- [x] Sales history with search
- [x] Filter by date range, customer, invoice number
- [x] Revenue totals

### 5️⃣ 🧠 Smart Prescription Scanner (NEW)
- [x] Upload a photo of a handwritten/printed prescription
- [x] OCR runs **entirely in the browser** via [Tesseract.js](https://github.com/naptha/tesseract.js) — no server-side OCR engine to install
- [x] Editable OCR output — pharmacist can correct misreads before saving
- [x] Smart fuzzy-matching engine (`includes/ocr_match.php`) tokenises the recognized text, strips dosage/instruction noise (mg, tab, BD, TDS…), and matches drug names against inventory using similarity scoring — so "Amoxicilin" still matches "Amoxicillin 250mg"
- [x] Live availability check per matched drug: **Available / Low Stock / Out of Stock / Not in Inventory**
- [x] Full scan history with patient name/phone, confidence score, and matched item count
- [x] One-click hand-off to **New Sale** after reviewing matches

---

## 🧠 How the OCR Feature Works (Architecture)

```
[Browser]                                   [Server: PHP + MySQL]
   │
   │ 1. User selects prescription image
   ▼
Tesseract.js (client-side OCR)
   │
   │ 2. Extracts raw text + confidence score
   ▼
Pharmacist reviews/edits text in <textarea>
   │
   │ 3. Form POST (multipart/form-data): image + ocr_text + confidence
   ▼
modules/prescription.php  ──save_scan──▶  uploads/prescriptions/*.jpg
   │
   │ 4. calls matchPrescriptionText($conn, $ocrText)
   ▼
includes/ocr_match.php
   ├── extractCandidateLines()  → splits OCR text into drug-name candidates,
   │                               strips dosage/frequency noise
   └── fuzzyMatchMedicine()     → similar_text() + containment scoring
                                   against the `medicines` table
   │
   │ 5. INSERT prescriptions + prescription_items
   ▼
MySQL (pharmacy_db)
   │
   │ 6. Redirect to prescription.php?view=ID
   ▼
Results page: matched drug ↔ live stock qty ↔ price ↔ availability badge
```

**Why client-side OCR?** Running Tesseract.js in the browser means zero server
setup (no `tesseract-ocr` binary, no PHP extension, no PATH configuration) —
it works the moment you open the page, on any XAMPP install. The trade-off is
that OCR quality depends on the device's browser/CPU, so the UI always lets
the pharmacist review and fix the recognized text before it's matched and saved.

**New database tables** (already included in `database.sql`):
- `prescriptions` — one row per scanned prescription (patient info, image path, raw OCR text, confidence, status)
- `prescription_items` — one row per detected drug line, linked to a `medicines.id` when matched, with the match score and stock snapshot at scan time
