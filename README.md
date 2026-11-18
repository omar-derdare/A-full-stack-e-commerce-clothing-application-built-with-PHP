# LUXE Clothing — PHP/MySQL E-Commerce Store

A complete, dynamic clothing e-commerce system built with **PHP 8+, MySQL 8+, vanilla JavaScript (ES6+), and Bootstrap 5** — no frameworks. Cash on Delivery checkout, full admin dashboard, product variants (size/color), coupons, reviews, wishlist, and search/filtering.

## Requirements

- PHP 8.0+ with `pdo_mysql`, `gd` (or any image extension used by `getimagesize`), and `fileinfo` extensions enabled
- MySQL 8.0+ (or MariaDB 10.4+)
- Apache with `mod_rewrite`/`.htaccess` support (XAMPP works out of the box)

## Installation (XAMPP / local)

1. Copy the entire `project` folder into your XAMPP `htdocs` directory, e.g.
   `C:\xampp\htdocs\clothing-store` or `/Applications/XAMPP/htdocs/clothing-store`.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and create a database, OR simply import `database.sql`
   directly — it contains `CREATE DATABASE IF NOT EXISTS clothing_store` at the top, so importing it is enough.
4. In phpMyAdmin, go to **Import**, choose `database.sql`, and click **Go**. This creates all tables and seeds
   sample categories, products, variants, settings, and coupons, plus a default admin account.
5. Open `config/database.php` and confirm the credentials match your MySQL setup (defaults to `root` / empty
   password, which is standard for XAMPP):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'clothing_store');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
6. Visit `http://localhost/clothing-store/index.php` — the storefront should load with seeded products.
7. Visit `http://localhost/clothing-store/admin/login.php` to access the admin dashboard.

## Default Admin Login

```
Email:    admin@store.test
Password: Admin@123
```

**Change this password immediately after your first login** via *Admin → My Profile* (top-right avatar menu →
My Profile → Change Password), or by creating a new admin user directly and disabling this one. The password
above is only ever stored as a bcrypt hash in the database — never in plaintext.

## Project Structure

```
/admin        Admin dashboard (products, categories, orders, customers, coupons, reviews, messages, settings)
/api          JSON endpoints consumed via fetch() — cart, wishlist, checkout, coupon, search, products
/assets       CSS, JS, images, and user-uploaded files (assets/uploads/products, assets/uploads/categories)
/auth         Customer login, register, logout, profile
/config       Database connection + global config (BASE_URL, upload limits, etc.)
/includes     Shared PHP includes: functions.php (core helpers), header/navbar/footer, product-functions.php
index.php, shop.php, product.php, category.php, search.php, cart.php, wishlist.php,
checkout.php, order-success.php, orders.php, order-details.php, about.php, contact.php
database.sql  Full schema + seed data
sitemap.php   Dynamic XML sitemap (also referenced from robots.txt)
```

## How Core Flows Work

- **Cart**: guest carts are tracked by PHP session ID (`carts.session_id`); logging in merges the guest cart
  into the user's own cart automatically (see `mergeGuestCartIntoUserCart()` in `includes/functions.php`).
- **Checkout (Cash on Delivery)**: `api/checkout.php` re-validates stock and re-calculates the subtotal,
  discount, shipping, and total **entirely server-side** inside a database transaction — the frontend total is
  never trusted. Stock is locked with `SELECT ... FOR UPDATE` to prevent overselling under concurrent orders.
- **Stock**: products without variants use `products.stock`; products with variants (size/color) track stock
  per-variant in `product_variants.stock`. The product page won't let a customer add to cart until a valid,
  in-stock size/color combination is selected.
- **Coupons**: validated server-side on both "Apply" and at checkout (never trusts a client-supplied discount).
- **Reviews**: only customers who have a completed (non-cancelled) order containing the product can submit a
  review, and new reviews start as `pending` until an admin approves them from *Admin → Reviews*.
- **Admin order cancellation**: automatically restores product/variant stock; moving a cancelled order back to
  an active status re-deducts it.

## Security Notes

- All database queries use PDO prepared statements — no raw string concatenation of user input into SQL.
- Passwords are hashed with `password_hash()` / verified with `password_verify()`.
- Every state-changing form and AJAX call is protected by a CSRF token (session-based, checked via
  `verifyCsrf()`/`requireCsrf()`).
- All user-controlled output is escaped with `htmlspecialchars()` via the `e()` helper.
- File uploads are restricted to `jpg/jpeg/png/webp`, capped at 5MB, validated with `getimagesize()` (not just
  the file extension), and saved under randomized filenames — the `assets/uploads/.htaccess` also blocks PHP
  execution inside the uploads folder as defense in depth.
- `config/`, `includes/`, and `.sql`/`.md` files are blocked from direct browser access via `.htaccess`.
- PHP error display is disabled (`display_errors = 0`); errors are logged instead of shown to visitors.

## Customizing

- **Store name, currency, shipping rules, social links, logo, footer text, SEO description**: *Admin → Settings*.
- **Colors**: *Admin → Colors* — add any color name + swatch (hex picker). These colors populate the dropdown
  when adding product size/color variants, and automatically drive the swatch colors shown to customers on the
  shop filters and product pages — no code changes needed to add a new color.
- **Homepage background slider**: *Admin → Homepage Slider* — add/edit/reorder/activate slides (image, title,
  subtitle, button text/link). If no active slides exist, the homepage falls back to a simple static hero.
- **Newsletter subscribers**: *Admin → Newsletter* — every signup from the footer form is saved to the
  database (duplicates are handled gracefully) and can be viewed or exported as CSV from this page.
- **Design tokens** (colors, fonts): edit the CSS custom properties at the top of `assets/css/style.css`.
- **BASE_URL**: auto-detected from the request in `config/config.php`. If you deploy under a different folder
  structure or subdomain, you can hardcode it instead by defining `BASE_URL` before that detection logic runs.

## Upgrading an Existing Install

If you already imported an earlier version of `database.sql`, run `migration_v2.sql` against your existing
database to add the new `colors`, `hero_slides`, and `newsletter_subscribers` tables (plus new settings keys)
without losing any existing data:

```
mysql -u root -p clothing_store < migration_v2.sql
```

New installs don't need this — everything is already merged into `database.sql`.

## Deploying to Shared Hosting / VPS

1. Upload all files via FTP/SFTP (or `git clone` on a VPS) to your web root.
2. Create a MySQL database on your host and import `database.sql`.
3. Update `config/database.php` with your live database credentials.
4. Ensure the `assets/uploads/` directory is writable by the web server (`chmod 755` or as required by your host).
5. Point your domain at the project root, and change the default admin password immediately.
6. Optionally set up a cron job / HTTPS certificate (Let's Encrypt) — the codebase itself makes no assumptions
   about HTTPS beyond respecting the `HTTPS` server variable when building `BASE_URL`.

## What Was Tested

Full customer flow: register → login → browse/filter products → open product → select size/color → add to
cart → update quantity → apply coupon (`SAVE10`, `WELCOME20`, or `SUMMER30` from seed data) → checkout → enter
delivery info → place Cash on Delivery order → order success → My Orders → order details.

Full admin flow: admin login → dashboard (stats + charts) → create category → create product with images and
variants → manage stock → view incoming order → update status through confirmed → processing → shipped →
delivered (or cancel, which restores stock) → moderate a review → read a contact message → update store
settings.
