CREATE DATABASE IF NOT EXISTS pc_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pc_shop;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    google_id VARCHAR(80) DEFAULT NULL UNIQUE,
    auth_provider VARCHAR(30) NOT NULL DEFAULT 'local',
    phone VARCHAR(30) DEFAULT NULL,
    address TEXT,
    role ENUM('customer', 'admin', 'staff') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(180) NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock_quantity INT NOT NULL DEFAULT 0,
    description TEXT,
    image_url VARCHAR(255) DEFAULT NULL,
    socket VARCHAR(50) DEFAULT NULL,
    ram_type VARCHAR(30) DEFAULT NULL,
    vram_gb INT DEFAULT 0,
    wattage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subtotal_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    coupon_code VARCHAR(50) DEFAULT NULL,
    status ENUM('pending', 'processing', 'shipping', 'completed', 'cancelled', 'returned') DEFAULT 'pending',
    shipping_address TEXT,
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_status ENUM('unpaid', 'paid', 'cancelled') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    unit_cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_review_order_product (order_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(12,2) NOT NULL DEFAULT 0,
    min_order_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    starts_at DATETIME DEFAULT NULL,
    ends_at DATETIME DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS combo_promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    category_a_id INT NOT NULL,
    category_b_id INT NOT NULL,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_a_id) REFERENCES categories(id),
    FOREIGN KEY (category_b_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS flash_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(12,2) NOT NULL DEFAULT 0,
    starts_at DATETIME DEFAULT NULL,
    ends_at DATETIME DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

INSERT INTO categories (name, slug) VALUES
('CPU', 'cpu'),
('Mainboard', 'mainboard'),
('VGA', 'vga'),
('RAM', 'ram'),
('PSU', 'psu')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO products (category_id, name, price, stock_quantity, description, socket, ram_type, vram_gb, wattage)
VALUES
((SELECT id FROM categories WHERE slug = 'cpu'), 'Intel Core i5-14600K', 8200000, 20, 'CPU gen 14 cho gaming va da tac vu', 'LGA1700', '', 0, 125),
((SELECT id FROM categories WHERE slug = 'mainboard'), 'ASUS B760M-A DDR5', 4200000, 15, 'Mainboard B760M ho tro LGA1700 va DDR5', 'LGA1700', 'DDR5', 0, 65),
((SELECT id FROM categories WHERE slug = 'vga'), 'NVIDIA RTX 5070 12GB', 16900000, 10, 'Card do hoa RTX 5070 12GB', '', '', 12, 250),
((SELECT id FROM categories WHERE slug = 'psu'), 'Corsair RM750e 750W', 2600000, 25, 'Nguon cong suat thuc 750W chuan 80 Plus Gold', '', '', 0, 750),
((SELECT id FROM categories WHERE slug = 'ram'), 'Kingston Fury 32GB DDR5 Bus 6000', 2900000, 30, 'RAM DDR5 32GB (2x16GB) bus 6000', '', 'DDR5', 0, 10);

INSERT INTO users (name, email, password_hash, role)
VALUES ('Administrator', 'admin@shop.local', 'admin123', 'admin')
ON DUPLICATE KEY UPDATE role = VALUES(role);

