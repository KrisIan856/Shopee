# Shopee PH — Full-Stack E-Commerce Website

A complete Shopee-inspired Philippine e-commerce platform built with PHP + MySQL, 
ready to run on XAMPP.

---

## ⚡ Quick Setup (5 steps)

### 1. Copy project to XAMPP
Place the entire `shopee_ph/` folder inside:
```
C:\xampp\htdocs\shopee_ph\     (Windows)
/Applications/XAMPP/htdocs/shopee_ph/  (macOS)
```

### 2. Start XAMPP
- Open XAMPP Control Panel
- Start **Apache** and **MySQL**

### 3. Import the Database
- Open your browser → `http://localhost/phpmyadmin`
- Click **New** → Name the database `shopee_ph` → Click **Create**
- Click **Import** → Choose `database/shopee_ph.sql` → Click **Go**

### 4. Check Config (optional)
Open `config/db.php` and verify:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'shopee_ph');
define('DB_USER', 'root');
define('DB_PASS', '');      // default XAMPP = empty password
define('SITE_URL', 'http://localhost/shopee_ph');
```

### 5. Open the Website
Go to: **http://localhost/shopee_ph/**

---

## 🔑 Demo Accounts

| Role   | Username     | Password   |
|--------|-------------|-------------|
| Buyer  | buyer_pedro | password    |
| Seller | seller_juan | password    |
| Admin  | admin       | password    |

---

## 📁 Project Structure

```
shopee_ph/
├── index.php           — Homepage (hero, categories, flash deals, products)
├── login.php           — Login page
├── register.php        — Registration
├── logout.php          — Logout
├── product.php         — Product detail page
├── cart.php            — Shopping cart
├── checkout.php        — Checkout & order placement
├── search.php          — Search results & category browse
├── category.php        — Category redirect
├── profile.php         — User profile, orders, wishlist, password
│
├── seller/
│   ├── index.php       — Seller dashboard & product management
│   └── add-product.php — Add / Edit products
│
├── admin/
│   ├── index.php       — Admin dashboard (stats, orders, users)
│   ├── products.php    — Manage all products
│   └── users.php       — Manage users & roles
│
├── api/
│   ├── cart.php        — Cart AJAX API (add/update/remove/voucher)
│   ├── wishlist.php    — Wishlist toggle API
│   └── search.php      — Live search autocomplete API
│
├── config/
│   └── db.php          — Database connection & constants
│
├── includes/
│   ├── session.php     — Session, auth helpers, formatters
│   ├── header.php      — Shared nav header
│   └── footer.php      — Shared footer
│
├── assets/
│   ├── css/style.css   — Full stylesheet (responsive)
│   ├── js/main.js      — Cart, wishlist, countdown, autocomplete
│   └── uploads/        — Product image uploads (create manually)
│
└── database/
    └── shopee_ph.sql   — DB schema + seed data
```

---

## ✨ Features

### Buyer
- Browse homepage with hero banner, flash deals, categories, recommended products
- Live search autocomplete
- Product detail page with reviews & related products
- Shopping cart with quantity controls & voucher codes
- Checkout with address & payment method selection
- User profile: edit info, order history, wishlist, change password

### Seller
- Seller dashboard with revenue/order stats
- Add, edit, hide products
- Buyers can become sellers with one click

### Admin
- Full admin dashboard with site-wide stats
- Manage all products (toggle visibility, delete)
- Manage users (change roles, ban/unban)
- Update order statuses

### Technical
- PDO with prepared statements (SQL injection safe)
- CSRF tokens on all forms
- Password hashing with `password_hash()`
- Session-based authentication
- AJAX cart & wishlist (no page reload)
- Responsive design: 1200px → 992px → 768px → 480px
- Live countdown timer for flash deals
- Search autocomplete with debounce
- Toast notifications

---

## 🗄️ Database Tables

| Table          | Purpose                              |
|----------------|--------------------------------------|
| users          | Buyers, sellers, admins              |
| categories     | Product categories (10 default)      |
| products       | Product listings                     |
| flash_deals    | Timed flash sale discounts           |
| cart_items     | Shopping cart per user               |
| wishlists      | Saved/liked products per user        |
| orders         | Placed orders                        |
| order_items    | Line items per order                 |
| reviews        | Product reviews & ratings            |
| vouchers       | Discount codes                       |
| banners        | Homepage hero & side banners         |
| notifications  | User notification inbox              |

---

## 🚀 Optional Improvements

- Add product image uploads (save to `assets/uploads/`, store path in DB)
- Integrate GCash/Maya payment gateway webhooks
- Add email notifications via PHPMailer
- Add a live chat support module
- Deploy to production using Apache Virtual Hosts
