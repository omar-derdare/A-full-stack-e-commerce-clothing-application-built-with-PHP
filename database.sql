-- =====================================================
-- Clothing Store - Complete Database Schema + Seed Data
-- =====================================================

CREATE DATABASE IF NOT EXISTS clothing_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clothing_store;

-- ---------------------------------------------------
-- 1. users
-- ---------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 2. categories
-- ---------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_categories_slug (slug),
    INDEX idx_categories_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 3. products
-- ---------------------------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    stock INT NOT NULL DEFAULT 0,
    featured_image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_products_slug (slug),
    INDEX idx_products_category (category_id),
    INDEX idx_products_status (status),
    INDEX idx_products_featured (featured),
    INDEX idx_products_created (created_at),
    FULLTEXT INDEX ft_products_search (name, description, short_description)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 4. product_images
-- ---------------------------------------------------
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_pimages_product (product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 5. product_variants
-- ---------------------------------------------------
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(20) DEFAULT NULL,
    color VARCHAR(50) DEFAULT NULL,
    sku VARCHAR(100) DEFAULT NULL,
    stock INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_variants_product (product_id),
    UNIQUE KEY uniq_variant (product_id, size, color)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 6. carts
-- ---------------------------------------------------
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(191) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_carts_user (user_id),
    INDEX idx_carts_session (session_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 7. cart_items
-- ---------------------------------------------------
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_cartitems_cart (cart_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 8. wishlists
-- ---------------------------------------------------
CREATE TABLE wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_wishlist (user_id, product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 9. orders
-- ---------------------------------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'cash_on_delivery',
    payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    order_status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    coupon_code VARCHAR(50) DEFAULT NULL,
    customer_name VARCHAR(200) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_number (order_number),
    INDEX idx_orders_status (order_status),
    INDEX idx_orders_created (created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 10. order_items
-- ---------------------------------------------------
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    variant_id INT DEFAULT NULL,
    product_name VARCHAR(200) NOT NULL,
    size VARCHAR(20) DEFAULT NULL,
    color VARCHAR(50) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_orderitems_order (order_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 11. coupons
-- ---------------------------------------------------
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    value DECIMAL(10,2) NOT NULL,
    minimum_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    maximum_discount DECIMAL(10,2) DEFAULT NULL,
    expires_at DATE DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_coupons_code (code)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 12. reviews
-- ---------------------------------------------------
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_reviews_product (product_id),
    INDEX idx_reviews_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 13. settings
-- ---------------------------------------------------
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 14. contact_messages
-- ---------------------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('new','read') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 15. colors  (admin-managed color palette used by product variants)
-- ---------------------------------------------------
CREATE TABLE colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    hex_code VARCHAR(7) NOT NULL DEFAULT '#cccccc',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- 16. hero_slides  (admin-managed homepage background slider)
-- ---------------------------------------------------
CREATE TABLE hero_slides (
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

-- ---------------------------------------------------
-- 17. newsletter_subscribers
-- ---------------------------------------------------
CREATE TABLE newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Default admin: email admin@store.test / password Admin@123
-- (bcrypt hash, compatible with PHP password_verify())
-- IMPORTANT: change this password immediately after first login (see README).
INSERT INTO users (first_name, last_name, email, password, phone, role, status) VALUES
('Store', 'Admin', 'admin@store.test', '$2b$10$1Txm7KA7akxd9rDWJDKNpeDQFElq4LJ0.potG6.HgVB2S.EPTGTJi', '0600000000', 'admin', 'active');

INSERT INTO categories (name, slug, description, status) VALUES
('Men', 'men', 'Men clothing collection', 'active'),
('Women', 'women', 'Women clothing collection', 'active'),
('Shoes', 'shoes', 'Footwear for everyone', 'active'),
('Accessories', 'accessories', 'Bags, caps, belts and more', 'active');

INSERT INTO products (category_id, name, slug, description, short_description, price, sale_price, sku, stock, featured_image, status, featured, created_at) VALUES
(1, 'Oversized Black T-Shirt', 'oversized-black-tshirt', 'Premium oversized cotton t-shirt with a relaxed fit, perfect for everyday streetwear.', 'Relaxed fit cotton tee', 29.99, 19.99, 'MEN-TSH-001', 50, 'placeholder.jpg', 'active', 1, NOW()),
(1, 'Basic Cotton T-Shirt', 'basic-cotton-tshirt', 'Soft breathable cotton t-shirt, a wardrobe essential.', 'Everyday essential tee', 19.99, NULL, 'MEN-TSH-002', 80, 'placeholder.jpg', 'active', 0, NOW()),
(1, 'Premium White Hoodie', 'premium-white-hoodie', 'Heavyweight fleece hoodie with a modern silhouette.', 'Heavyweight fleece hoodie', 59.99, 44.99, 'MEN-HOD-001', 40, 'placeholder.jpg', 'active', 1, NOW()),
(1, 'Classic Denim Jacket', 'classic-denim-jacket', 'Timeless denim jacket crafted from durable washed denim.', 'Timeless washed denim', 89.99, NULL, 'MEN-JKT-001', 25, 'placeholder.jpg', 'active', 0, NOW()),
(1, 'Slim Fit Jeans', 'slim-fit-jeans', 'Comfortable stretch denim jeans with a modern slim cut.', 'Stretch slim denim', 54.99, 39.99, 'MEN-JNS-001', 60, 'placeholder.jpg', 'active', 0, NOW()),
(1, 'Leather Jacket', 'leather-jacket', 'Genuine leather biker jacket for a bold statement look.', 'Genuine leather biker jacket', 199.99, 159.99, 'MEN-JKT-002', 15, 'placeholder.jpg', 'active', 1, NOW()),
(2, 'Summer Dress', 'summer-dress', 'Lightweight floral summer dress, perfect for warm days.', 'Lightweight floral dress', 49.99, 34.99, 'WMN-DRS-001', 35, 'placeholder.jpg', 'active', 1, NOW()),
(3, 'Casual Sneakers', 'casual-sneakers', 'Everyday comfort sneakers with breathable mesh upper.', 'Breathable everyday sneakers', 69.99, NULL, 'SHO-SNK-001', 45, 'placeholder.jpg', 'active', 0, NOW()),
(4, 'Premium Cap', 'premium-cap', 'Adjustable cotton cap with embroidered logo.', 'Adjustable cotton cap', 24.99, NULL, 'ACC-CAP-001', 100, 'placeholder.jpg', 'active', 0, NOW()),
(4, 'Leather Bag', 'leather-bag', 'Handcrafted genuine leather crossbody bag.', 'Handcrafted leather crossbody', 119.99, 89.99, 'ACC-BAG-001', 20, 'placeholder.jpg', 'active', 1, NOW());

-- Variants for a few clothing items
INSERT INTO product_variants (product_id, size, color, sku, stock) VALUES
(1,'S','Black','MEN-TSH-001-S-BLK',10),
(1,'M','Black','MEN-TSH-001-M-BLK',15),
(1,'L','Black','MEN-TSH-001-L-BLK',15),
(1,'XL','Black','MEN-TSH-001-XL-BLK',10),
(3,'M','White','MEN-HOD-001-M-WHT',12),
(3,'L','White','MEN-HOD-001-L-WHT',14),
(3,'XL','White','MEN-HOD-001-XL-WHT',14),
(5,'S','Blue','MEN-JNS-001-S-BLU',15),
(5,'M','Blue','MEN-JNS-001-M-BLU',20),
(5,'L','Blue','MEN-JNS-001-L-BLU',25),
(7,'S','Red','WMN-DRS-001-S-RED',10),
(7,'M','Red','WMN-DRS-001-M-RED',15),
(7,'M','Green','WMN-DRS-001-M-GRN',10);

INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'LUXE Clothing'),
('logo', ''),
('phone', '+212 600 000 000'),
('email', 'contact@luxeclothing.test'),
('address', '123 Fashion Street, Casablanca'),
('currency', 'USD'),
('currency_symbol', '$'),
('shipping_price', '5.00'),
('free_shipping_threshold', '75.00'),
('facebook', 'https://facebook.com'),
('instagram', 'https://instagram.com'),
('tiktok', 'https://tiktok.com'),
('whatsapp', 'https://wa.me/212600000000');

INSERT INTO coupons (code, type, value, minimum_amount, maximum_discount, expires_at, usage_limit, status) VALUES
('SAVE10', 'percentage', 10.00, 30.00, 20.00, DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 500, 'active'),
('WELCOME20', 'percentage', 20.00, 50.00, 40.00, DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 200, 'active'),
('SUMMER30', 'fixed', 30.00, 100.00, NULL, DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 100, 'active');

INSERT INTO colors (name, hex_code) VALUES
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

INSERT INTO hero_slides (title, subtitle, button_text, button_link, image, sort_order, status, created_at) VALUES
('Elevate Your Everyday Style', 'Discover premium clothing crafted for comfort and made to last.', 'Shop Collection', 'shop.php', 'placeholder.jpg', 1, 'active', NOW()),
('New Season Arrivals', 'Fresh styles just landed — be the first to wear them.', 'Shop New Arrivals', 'shop.php?sort=newest', 'placeholder.jpg', 2, 'active', NOW()),
('Up to 40% Off', 'Limited time only. Grab your favorites before they sell out.', 'Shop the Sale', 'shop.php?on_sale=1', 'placeholder.jpg', 3, 'active', NOW());

INSERT INTO settings (setting_key, setting_value) VALUES
('site_logo', ''),
('meta_description', 'Shop premium men\'s and women\'s clothing, shoes and accessories.'),
('footer_about_text', 'Premium clothing designed for comfort and made to last.');
