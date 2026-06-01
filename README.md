# 👟 Jade Sole — Shoe Store Website System

A full-stack shoe store ordering system built with PHP, MySQL, and vanilla CSS.

---

## 📁 Project Structure

```
jade_sole/
├── index.php              ← Home page
├── menu.php               ← Full product collection
├── order.php              ← Order page with cart
├── track.php              ← Track order by ID
├── login.php              ← Staff/Admin login
├── logout.php             ← Logout
├── config.php             ← DB config & helpers
├── includes/
│   ├── navbar.php
│   └── footer.php
├── admin/
│   ├── index.php          ← Admin dashboard
│   ├── products.php       ← Manage products
│   ├── orders.php         ← View & update all orders
│   ├── staff.php          ← Manage staff
│   └── sidebar.php
├── staff/
│   └── index.php          ← Staff order dashboard
├── assets/
│   ├── css/style.css
│   └── images/            ← Product images go here
└── database/
    └── jade_sole.sql      ← Import this first!
```

---

## 🚀 Setup Instructions

### 1. Install XAMPP
Download and install XAMPP from https://www.apachefriends.org

### 2. Copy files
Place the entire `jade_sole/` folder inside:
```
C:\xampp\htdocs\jade_sole\
```

### 3. Import the Database
1. Start Apache and MySQL in XAMPP Control Panel
2. Open **phpMyAdmin**: http://localhost/phpmyadmin
3. Click **Import** → Choose file → select `database/jade_sole.sql`
4. Click **Go**

### 4. Configure Database (if needed)
Edit `config.php` and update credentials if yours differ:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Leave blank for default XAMPP
define('DB_NAME', 'jade_sole');
```

### 5. Run the Site
Open: http://localhost/jade_sole/

---

## 🔐 Login Credentials

| Role  | Username | Password  |
|-------|----------|-----------|
| Admin | admin    | admin123  |
| Staff | staff    | staff123  |

Admin Portal: http://localhost/jade_sole/login.php

---

## 🛍️ Pages

| Page | URL |
|------|-----|
| Home | /index.php |
| Collection | /menu.php |
| Order | /order.php |
| Track Order | /track.php |
| Login | /login.php |
| Admin Dashboard | /admin/index.php |
| Staff Dashboard | /staff/index.php |

---

## 🏷️ Product Categories

1. **Running Shoes** — SpeedFlex Pro, TrailBlazer X, AirStride Elite, PaceRunner Lite
2. **Casual Sneakers** — UrbanEdge Classic, SoftStep Canvas, NeoWave Street, CloudComfort Knit
3. **Formal Shoes** — Oxford Premier, Derby Luxe, Loafer Signature
4. **Sandals & Slippers** — Drift Slide, Bali Strap, Terra Sport
5. **Boots** — Highland Chukka, Storm Rider, Chelsea Noir

---

## 💡 Promotions

- 📶 Free WiFi in-store
- 🏷️ 10% off orders above ₱500
- 🚚 Free delivery for orders above ₱300

---

## 🖼️ Adding Product Images

1. Place image files in `assets/images/`
2. Upload via Admin → Products → Edit → choose image file
3. Supported formats: JPG, PNG, WebP

---

## 📦 Order Flow

1. Customer browses **Collection** or **Order** page
2. Adds items to cart
3. Clicks **Place Order** → fills in Name, Contact, Pickup/Delivery
4. System generates **Order ID** (e.g. `JS-4A2F8C1D`)
5. Customer saves ID and uses **Track Order** to check status
6. Staff/Admin updates order status from their dashboard

---

## ⚙️ Order Statuses

`Received` → `Preparing` → `Ready for Pickup` / `Out for Delivery` → `Completed`

Or: `Cancelled`
