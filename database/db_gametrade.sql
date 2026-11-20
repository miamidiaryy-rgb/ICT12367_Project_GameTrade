CREATE DATABASE IF NOT EXISTS db_gametrade
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_gametrade;

-- 1. users – ข้อมูลสมาชิก
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(20) DEFAULT '',
    role ENUM('buyer', 'seller', 'admin') NOT NULL DEFAULT 'buyer',
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. games – ข้อมูลเกม
CREATE TABLE IF NOT EXISTS games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    game_name VARCHAR(100) NOT NULL UNIQUE
);

-- 3. categories – ประเภทไอเทม
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

-- 4. items – รายการไอเทมที่ลงขาย
CREATE TABLE IF NOT EXISTS items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    game_id INT NOT NULL,
    category_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image VARCHAR(150) DEFAULT '',
    status ENUM('พร้อมขาย', 'ขายแล้ว') NOT NULL DEFAULT 'พร้อมขาย',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- 5. orders – ข้อมูลคำสั่งซื้อ
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    order_status ENUM('รอดำเนินการ', 'กำลังส่งมอบ', 'สำเร็จ', 'ยกเลิก') NOT NULL DEFAULT 'รอดำเนินการ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(item_id),
    FOREIGN KEY (buyer_id) REFERENCES users(user_id),
    FOREIGN KEY (seller_id) REFERENCES users(user_id)
);

-- 6. transactions – ประวัติการซื้อขาย
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_status ENUM('สำเร็จ', 'ยกเลิก') NOT NULL DEFAULT 'สำเร็จ',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

-- 7. reports – ข้อมูลการแจ้งรายงานผู้ขาย
CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    seller_id INT NOT NULL,
    item_id INT NULL,
    reason_type VARCHAR(100) NOT NULL,
    detail TEXT NOT NULL,
    status ENUM('รอตรวจสอบ', 'อนุมัติ', 'ปฏิเสธ') NOT NULL DEFAULT 'รอตรวจสอบ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(user_id),
    FOREIGN KEY (seller_id) REFERENCES users(user_id)
);

-- 8. blacklist – รายชื่อผู้ขายที่ถูกขึ้นบัญชีดำ
CREATE TABLE IF NOT EXISTS blacklist (
    blacklist_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL UNIQUE,
    reason TEXT NOT NULL,
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(user_id),
    FOREIGN KEY (added_by) REFERENCES users(user_id)
);

-- 9. report_evidence – หลักฐานประกอบการรายงาน
CREATE TABLE IF NOT EXISTS report_evidence (
    evidence_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    note VARCHAR(255) DEFAULT '',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE CASCADE
);

-- 10. reviews – คะแนนและรีวิวผู้ขาย
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (buyer_id) REFERENCES users(user_id),
    FOREIGN KEY (seller_id) REFERENCES users(user_id)
);

-- ข้อมูลตัวอย่างเริ่มต้น (รหัสผ่านทุกบัญชีคือ 123456)
INSERT INTO users (username, password, fullname, email, phone, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ GameTrade', 'admin@gametrade.local', '080-000-0001', 'admin'),
('seller01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'กิตติพงษ์ ร้านเกมโปร', 'seller01@gmail.com', '081-234-5678', 'seller'),
('seller02', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ธนากร ช็อปไอเทม', 'seller02@gmail.com', '082-345-6789', 'seller'),
('scammer99', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'นายสมหมาย (บัญชีต้องสงสัย)', 'scam99@gmail.com', '089-999-9999', 'seller'),
('buyer01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สมชาย ใจดี', 'buyer01@gmail.com', '083-456-7890', 'buyer');

INSERT INTO games (game_name) VALUES ('Valorant'), ('ROV'), ('Genshin Impact'), ('Roblox'), ('CS2');
INSERT INTO categories (category_name) VALUES ('สกินอาวุธ'), ('ไอดีเกม'), ('ไอเทมแรร์'), ('สกุลเงินในเกม');

INSERT INTO items (seller_id, game_id, category_id, item_name, description, price, image, status) VALUES
(2, 1, 1, 'Reaver Vandal Skin Account', 'สกินปืน Reaver Vandal + Phantom ครบเซต เปลี่ยนอีเมลได้', 1250.00, 'valorant_skin.jpg', 'พร้อมขาย'),
(2, 2, 2, 'ไอดี ROV แรงค์ Conqueror สกิน 80+', 'สกินแรร์ Nakroth Dimension Breaker + TelAnnas', 1800.00, 'rov_id.jpg', 'พร้อมขาย'),
(3, 3, 2, 'ไอดี Genshin AR58 ตัวละคร 5 ดาว 12 ตัว', 'มี Raiden Shogun C2 + อาวุธประจำตัว พร้อมเล่น', 2400.00, 'genshin_id.jpg', 'พร้อมขาย'),
(3, 4, 3, 'Blox Fruits Permanent Kitsune', 'ผลถาวร Kitsune ส่งกิฟต์ในเกมทันที ปลอดภัย 100%', 890.00, 'roblox_item.jpg', 'พร้อมขาย'),
(4, 5, 1, 'AWP Asiimov Factory New (ราคาถูกพิเศษ)', 'สกิน AWP หายาก ขายด่วน โอนก่อนส่งทีหลัง', 950.00, 'cs2_awp.jpg', 'พร้อมขาย');

INSERT INTO blacklist (seller_id, reason, added_by) VALUES
(4, 'ได้รับการร้องเรียนว่าโกงเงินและไม่ส่งมอบไอเทมตามที่ตกลง ตรวจสอบหลักฐานแล้วพบว่ามีความผิดจริง', 1);

INSERT INTO orders (item_id, buyer_id, seller_id, price, order_status) VALUES
(1, 5, 2, 1250.00, 'สำเร็จ');

INSERT INTO transactions (order_id, buyer_id, seller_id, amount, transaction_status) VALUES
(1, 5, 2, 1250.00, 'สำเร็จ');

INSERT INTO reviews (order_id, buyer_id, seller_id, rating, comment) VALUES
(1, 5, 2, 5, 'ส่งมอบไอเทมไวมาก ข้อมูลตรงตามประกาศ แนะนำร้านนี้ครับ');

INSERT INTO reports (reporter_id, seller_id, item_id, reason_type, detail, status) VALUES
(5, 4, 5, 'โกงเงิน / ไม่ส่งไอเทม', 'โอนเงินจำลองแล้วผู้ขายไม่ส่งไอเทมและปิดช่องทางติดต่อ', 'อนุมัติ'),
(5, 3, 4, 'ข้อมูลสินค้าไม่ตรงกับประกาศ', 'รายละเอียดจำนวนไอเทมในประกาศไม่ตรงกับที่สอบถาม', 'รอตรวจสอบ');

INSERT INTO report_evidence (report_id, file_path, note) VALUES
(1, 'evidence_slip_01.jpg', 'ภาพหน้าจอประวัติการสั่งซื้อและแชตยืนยัน'),
(2, 'evidence_item_02.jpg', 'ภาพรายละเอียดไอเทมที่พบความคลาดเคลื่อน');
