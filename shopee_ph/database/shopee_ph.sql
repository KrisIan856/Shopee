-- ============================================================
--  Shopee PH — MySQL Database Schema + Seed Data
--  Import via phpMyAdmin or: mysql -u root shopee_ph < shopee_ph.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS shopee_ph CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shopee_ph;

-- ─────────────────────────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE users (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(60)  NOT NULL UNIQUE,
  email       VARCHAR(120) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('buyer','seller','admin','rider') NOT NULL DEFAULT 'buyer',
  full_name   VARCHAR(120),
  avatar      VARCHAR(255) DEFAULT NULL,
  phone       VARCHAR(20),
  address     TEXT,
  shop_rating DECIMAL(3,2) DEFAULT 0.00,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- CATEGORIES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80)  NOT NULL,
  slug        VARCHAR(80)  NOT NULL UNIQUE,
  emoji       VARCHAR(10),
  color_class VARCHAR(30)  DEFAULT 'emj-bg-1',
  parent_id   INT UNSIGNED DEFAULT NULL,
  sort_order  INT UNSIGNED DEFAULT 0,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- PRODUCTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE products (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id     INT UNSIGNED NOT NULL,
  category_id   INT UNSIGNED NOT NULL,
  name          VARCHAR(255) NOT NULL,
  description   TEXT,
  price         DECIMAL(10,2) NOT NULL,
  original_price DECIMAL(10,2),
  stock         INT UNSIGNED NOT NULL DEFAULT 0,
  sold_count    INT UNSIGNED NOT NULL DEFAULT 0,
  emoji         VARCHAR(10),
  color_class   VARCHAR(30)  DEFAULT 'emj-bg-1',
  location      VARCHAR(80),
  rating        DECIMAL(3,2) DEFAULT 0.00,
  rating_count  INT UNSIGNED DEFAULT 0,
  free_shipping TINYINT(1)   DEFAULT 1,
  is_hot        TINYINT(1)   DEFAULT 0,
  is_new        TINYINT(1)   DEFAULT 0,
  is_active     TINYINT(1)   DEFAULT 1,
  image         VARCHAR(255) DEFAULT NULL,
  created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (seller_id)   REFERENCES users(id)       ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- FLASH DEALS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE flash_deals (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL UNIQUE,
  flash_price DECIMAL(10,2) NOT NULL,
  stock_limit INT UNSIGNED  DEFAULT 100,
  sold_count  INT UNSIGNED  DEFAULT 0,
  start_time  DATETIME      NOT NULL,
  end_time    DATETIME      NOT NULL,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- BANNERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE banners (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(120),
  subtitle    VARCHAR(255),
  label       VARCHAR(60),
  bg_gradient VARCHAR(120) DEFAULT 'linear-gradient(135deg,#FF6B35,#EE4D2D)',
  cta_text    VARCHAR(40)  DEFAULT 'Shop Now →',
  cta_url     VARCHAR(255) DEFAULT '#',
  type        ENUM('hero','side_a','side_b') DEFAULT 'hero',
  sort_order  INT UNSIGNED DEFAULT 0,
  is_active   TINYINT(1)   DEFAULT 1
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- CART ITEMS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE cart_items (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity   INT UNSIGNED NOT NULL DEFAULT 1,
  added_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart (user_id, product_id),
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- WISHLIST
-- ─────────────────────────────────────────────────────────────
CREATE TABLE wishlists (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  added_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wish (user_id, product_id),
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- ORDERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE orders (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buyer_id       INT UNSIGNED NOT NULL,
  rider_id       INT UNSIGNED DEFAULT NULL,
  total_amount   DECIMAL(10,2) NOT NULL,
  status         ENUM('pending','to_ship','shipped','delivered','cancelled','returned') DEFAULT 'pending',
  shipping_addr  TEXT,
  payment_method VARCHAR(40) DEFAULT 'COD',
  voucher_code   VARCHAR(30),
  discount_amt   DECIMAL(10,2) DEFAULT 0,
  created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- ORDER ITEMS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE order_items (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id   INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  seller_id  INT UNSIGNED NOT NULL,
  quantity   INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- REVIEWS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE reviews (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  rating     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment    TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review (product_id, user_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- RIDER REVIEWS
-- Buyers can rate the rider assigned to their delivery
CREATE TABLE rider_reviews (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id   INT UNSIGNED NOT NULL,
  rider_id   INT UNSIGNED NOT NULL,
  buyer_id   INT UNSIGNED NOT NULL,
  rating     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment    TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rider_review (order_id, buyer_id),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- VOUCHERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE vouchers (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code           VARCHAR(30) NOT NULL UNIQUE,
  discount_type  ENUM('percent','fixed') DEFAULT 'fixed',
  discount_value DECIMAL(10,2) NOT NULL,
  min_spend      DECIMAL(10,2) DEFAULT 0,
  max_uses       INT UNSIGNED  DEFAULT 100,
  used_count     INT UNSIGNED  DEFAULT 0,
  expires_at     DATETIME,
  is_active      TINYINT(1)   DEFAULT 1
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- NOTIFICATIONS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE notifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  message    TEXT NOT NULL,
  type       VARCHAR(30) DEFAULT 'info',
  is_read    TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════════════════
--  SEED DATA
-- ═══════════════════════════════════════════════════════════

-- Admin & demo users (password = "password123" bcrypt hash)
INSERT INTO users (username, email, password, role, full_name, phone, address) VALUES
('admin',       'admin@shopeeph.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',  'Admin User',       '09171234567', 'Taguig City, Metro Manila'),
('seller_juan', 'juan@shopeeph.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'Juan dela Cruz',    '09281234567', 'Makati City, Metro Manila'),
('seller_maria','maria@shopeeph.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'Maria Santos',      '09391234567', 'Quezon City, Metro Manila'),
('buyer_pedro', 'pedro@shopeeph.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer',  'Pedro Reyes',       '09501234567', 'Pasig City, Metro Manila'),
('buyer_ana',   'ana@shopeeph.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer',  'Ana Gonzalez',      '09611234567', 'Cebu City');

-- Categories
INSERT INTO categories (name, slug, emoji, color_class, sort_order) VALUES
('Mobiles & Gadgets',  'mobiles-gadgets',  '📱', 'emj-bg-1', 1),
("Women's Fashion",    'womens-fashion',   '👗', 'emj-bg-2', 2),
("Men's Fashion",      'mens-fashion',     '👔', 'emj-bg-3', 3),
('Home & Living',      'home-living',      '🏠', 'emj-bg-4', 4),
('Health & Beauty',    'health-beauty',    '💄', 'emj-bg-5', 5),
('Sports & Outdoors',  'sports-outdoors',  '⚽', 'emj-bg-6', 6),
('Toys & Kids',        'toys-kids',        '🧸', 'emj-bg-7', 7),
('Food & Beverages',   'food-beverages',   '🍜', 'emj-bg-8', 8),
('Computers',          'computers',        '💻', 'emj-bg-1', 9),
('Tools & Home Impr.', 'tools-home',       '🔧', 'emj-bg-3', 10);

-- Products (seller_id 2 = juan, seller_id 3 = maria)
INSERT INTO products (seller_id, category_id, name, price, original_price, stock, sold_count, emoji, color_class, location, rating, rating_count, free_shipping, is_hot) VALUES
(2,1, 'Samsung Galaxy A55 5G 8GB+256GB Awesome Navy',          14999, 19999, 50,  892,  '📱', 'emj-bg-1', 'Makati City',       4.8, 324, 1, 1),
(2,1, 'Apple AirPods Pro 2nd Gen MagSafe Charging Case',        8999, 14995,  30,  241,  '🎧', 'emj-bg-4', 'Makati City',       4.9, 198, 1, 1),
(3,5, 'Cetaphil Moisturizing Cream 550g Sensitive Skin',          469,   799, 200, 21000, '🧴', 'emj-bg-3', 'Pasay City',        5.0, 867, 1, 0),
(2,1, 'PlayStation 5 DualSense Wireless Controller',            3299,  4990,  80,  890,  '🎮', 'emj-bg-3', 'Quezon City',       5.0, 412, 1, 1),
(3,5, 'Vitamineral Collagen + Glutathione Skin Glow 60s',         599,  1299, 150, 3700,  '🌿', 'emj-bg-5', 'Taguig City',       4.9, 280, 1, 0),
(3,3, 'Nike Air Max 270 Running Shoes Men Women Unisex',         1849,  4500,  60, 2300,  '👟', 'emj-bg-1', 'Metro Manila',      5.0, 645, 1, 1),
(2,3, 'Samsonite T5 Laptop Backpack Waterproof Anti-theft 15.6',1199,  2800,  40, 4400,  '🎒', 'emj-bg-6', 'Makati City',       4.4, 193, 1, 0),
(3,6, 'Adjustable Dumbbell Set 2-25kg Home Gym Fitness',        3599,  7000,  25,  620,  '🏋', 'emj-bg-7', 'Mandaluyong',       5.0, 155, 1, 0),
(3,4, 'Scented Soy Wax Candle Set Aromatherapy Home Decor',      245,   550, 300, 8900,  '🕯', 'emj-bg-8', 'Las Piñas',         5.0, 412, 1, 0),
(2,1, 'Canon EOS M50 Mark II Mirrorless Camera 24.1MP Kit',    34999, 49995,  15,  182,  '📷', 'emj-bg-1', 'Quezon City',       4.7,  89, 1, 0),
(3,2, 'MAC Cosmetics Matte Lipstick Long Lasting 24HR Wear',     349,   950, 200, 5100,  '💄', 'emj-bg-2', 'Cebu City',         4.3, 326, 1, 0),
(2,8, 'Nescafé Gold Premium Coffee Blend 200g Rich Aroma',       289,   450, 400,12000,  '☕', 'emj-bg-4', 'Pasig City',        4.6, 574, 1, 0),
(3,9, 'ASUS VivoBook 15 Intel Core i5 8GB 512GB SSD',          29500, 49999,  20,   47,  '💻', 'emj-bg-2', 'Quezon City',       4.7,  62, 1, 1),
(2,4, 'Xiaomi Smart LED Bulb 9W RGB Color App Control',           199,   450, 500, 9300,  '💡', 'emj-bg-4', 'Taguig City',       4.5, 721, 1, 0),
(3,7, 'LEGO Classic Medium Creative Brick Box 484 pcs',         1299,  1899, 100,  340,  '🧱', 'emj-bg-7', 'Pasig City',        4.8, 118, 1, 1);

-- Flash deals (next 3 hours from now)
INSERT INTO flash_deals (product_id, flash_price, stock_limit, sold_count, start_time, end_time) VALUES
(1,  4999, 50, 39, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 3 HOUR),
(6,   999, 60, 33, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 3 HOUR),
(13,29500, 20,  8, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 3 HOUR),
(2,  1299, 30, 27, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 3 HOUR),
(4,  2199, 80, 52, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 3 HOUR);

-- Banners
INSERT INTO banners (title, subtitle, label, bg_gradient, cta_text, cta_url, type, sort_order) VALUES
('Up to 90% OFF Top Brands', 'Shop the biggest sale of the season — limited time only.', '⚡ Mega Sale', 'linear-gradient(135deg,#FF6B35,#EE4D2D,#C84120)', 'Shop Now →', 'search.php', 'hero', 1),
('No Min. Spend All Day',    'Free delivery on every order today!',                       'Free Delivery', 'linear-gradient(135deg,#6C63FF,#4834D4)', 'Get Free Ship',  'search.php', 'side_a', 1),
('Fresh Picks Every Day',    'Discover new arrivals from top sellers.',                   'New Arrivals',  'linear-gradient(135deg,#26AA99,#1A7A6E)', 'Explore Now',   'category.php', 'side_b', 1);

-- Vouchers
INSERT INTO vouchers (code, discount_type, discount_value, min_spend, max_uses, expires_at) VALUES
('WELCOME100', 'fixed',   100.00,  500.00, 1000, DATE_ADD(NOW(), INTERVAL 30 DAY)),
('SAVE200',    'fixed',   200.00, 1000.00,  500, DATE_ADD(NOW(), INTERVAL 14 DAY)),
('FLASH50',    'percent',  50.00,  300.00,  200, DATE_ADD(NOW(), INTERVAL 3 DAY)),
('FREESHIP',   'fixed',    60.00,    0.00, 9999, DATE_ADD(NOW(), INTERVAL 60 DAY));

-- Sample cart for buyer_pedro (user_id 4)
INSERT INTO cart_items (user_id, product_id, quantity) VALUES
(4, 1, 1),
(4, 6, 2),
(4, 3, 1);

-- Sample wishlist
INSERT INTO wishlists (user_id, product_id) VALUES
(4, 2), (4, 4), (4, 8);

-- Sample reviews
INSERT INTO reviews (product_id, user_id, rating, comment) VALUES
(1, 4, 5, 'Great phone! Fast delivery and well-packed. Highly recommended seller!'),
(6, 4, 5, 'Super legit Nike shoes. Exactly as described. Will order again!'),
(3, 5, 5, 'My go-to moisturizer. Very affordable here vs malls. Fast shipping!');
