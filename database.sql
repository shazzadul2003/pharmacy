-- ============================================
-- PHARMACY MANAGEMENT SYSTEM - DATABASE SETUP
-- ============================================

CREATE DATABASE IF NOT EXISTS pharmacy_db;
USE pharmacy_db;

-- USERS TABLE ( - User Management)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pharmacist', 'manager') NOT NULL DEFAULT 'pharmacist',
    email VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- MEDICINES TABLE ( - Inventory Management)
CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    batch_number VARCHAR(50),
    supplier VARCHAR(100),
    category VARCHAR(50),
    expiry_date DATE NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    low_stock_threshold INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- SALES TABLE ( - Sales Management)
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(100) DEFAULT 'Walk-in Customer',
    customer_phone VARCHAR(20),
    total_amount DECIMAL(10,2) NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sold_by INT,
    FOREIGN KEY (sold_by) REFERENCES users(id)
);

-- SALE ITEMS TABLE (each medicine in a sale)
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);


-- PRESCRIPTIONS TABLE (OCR-Based Prescription Scanning)
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_name VARCHAR(100) DEFAULT 'Walk-in Patient',
    patient_phone VARCHAR(20),
    image_path VARCHAR(255) NOT NULL,
    ocr_text TEXT,
    ocr_confidence DECIMAL(5,2) DEFAULT 0,
    matched_count INT DEFAULT 0,
    status ENUM('pending','reviewed','fulfilled') DEFAULT 'pending',
    scanned_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scanned_by) REFERENCES users(id)
);

-- PRESCRIPTION MATCHED ITEMS (medicines detected inside a prescription)
CREATE TABLE IF NOT EXISTS prescription_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    matched_text VARCHAR(150) NOT NULL,
    medicine_id INT DEFAULT NULL,
    match_score DECIMAL(5,2) DEFAULT 0,
    available_qty INT DEFAULT 0,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Default Admin user (password: admin123)
INSERT INTO users (full_name, username, password, role, email) VALUES
('System Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@pharmacy.com'),
('Ahmed Pharmacist', 'pharmacist', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacist', 'pharmacist@pharmacy.com'),
('Sara Manager', 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'manager@pharmacy.com');

-- Sample medicines
INSERT INTO medicines (name, batch_number, supplier, category, expiry_date, price, quantity, low_stock_threshold) VALUES
('Paracetamol 500mg', 'BT-001', 'MediCorp Ltd', 'Analgesic', '2026-12-01', 5.00, 200, 20),
('Amoxicillin 250mg', 'BT-002', 'PharmaCo', 'Antibiotic', '2025-06-15', 15.00, 8, 10),
('Omeprazole 20mg', 'BT-003', 'HealthPlus', 'Antacid', '2025-05-20', 20.00, 50, 15),
('Metformin 500mg', 'BT-004', 'DiabeCare', 'Antidiabetic', '2026-08-10', 12.00, 100, 25),
('Vitamin C 1000mg', 'BT-005', 'VitaSupply', 'Supplement', '2027-01-01', 8.00, 5, 10),
('Cetirizine 10mg', 'BT-006', 'AllergyHelp', 'Antihistamine', '2025-05-30', 10.00, 30, 10),
('Aspirin 75mg', 'BT-007', 'CardioMed', 'Antiplatelet', '2026-03-15', 6.00, 150, 20),
('Azithromycin 500mg', 'BT-008', 'PharmaCo', 'Antibiotic', '2025-06-01', 45.00, 12, 10);
