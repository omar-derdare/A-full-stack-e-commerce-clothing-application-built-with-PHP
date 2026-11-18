-- =====================================================
-- Migration v2: Colors management, Homepage Slider, Newsletter
-- Run this against an EXISTING clothing_store database to add
-- the new features without losing any existing data.
-- (New installs: this is already merged into database.sql, no need to run separately.)
-- =====================================================

USE clothing_store;

-- ---------------------------------------------------
-- 15. colors  (admin-managed color palette used by product variants)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    hex_code VARCHAR(7) NOT NULL DEFAULT '#cccccc',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO colors (name, hex_code) VALUES
('Black', '#111111'),
('White', '#ffffff'),
('Blue', '#2c5faa'),
('Red', '#c0392b'),
('Green', '#2e7d32'),
('Gray', '#9e9e9e'),
('Beige', '#e8dcc8'),
('Navy', '#1a2744'),
('Brown', '#6b4226'),
('Pink', '#e8a0bf');

-- ---------------------------------------------------
-- 16. hero_slides  (admin-managed homepage background slider)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS hero_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    button_text VARCHAR(50) DEFAULT NULL,
    button_link VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO hero_slides (title, subtitle, button_text, button_link, image, sort_order, status, created_at) VALUES
('Elevate Your Everyday Style', 'Discover premium clothing crafted for comfort and made to last.', 'Shop Collection', 'shop.php', 'placeholder.jpg', 1, 'active', NOW()),
('New Season Arrivals', 'Fresh styles just landed — be the first to wear them.', 'Shop New Arrivals', 'shop.php?sort=newest', 'placeholder.jpg', 2, 'active', NOW()),
('Up to 40% Off', 'Limited time only. Grab your favorites before they sell out.', 'Shop the Sale', 'shop.php?on_sale=1', 'placeholder.jpg', 3, 'active', NOW());

-- ---------------------------------------------------
-- 17. newsletter_subscribers
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- New settings for expanded admin control
-- ---------------------------------------------------
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('site_logo', ''),
('meta_description', 'Shop premium men''s and women''s clothing, shoes and accessories.'),
('footer_about_text', '');
