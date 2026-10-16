# DeiTrack: Bathala Tissue

Inventory, point-of-sale, and GPS delivery tracking system for **Bathala Tissue Supplier**.
Built with PHP, MySQL/MariaDB, Bootstrap 5, and Leaflet maps. Installable on phones as a web app (PWA).

**Team:** Paul Patrick M. Balbalosa · Stephon Aevron Vinze P. Flores · Kirby C. Galedo · John Harold O. Santos

---

## Features

| Module | What it does |
|---|---|
| **Authentication** | Role-based login for Admin, Staff, Warehouse and Rider; customer sign-up with email or Google |
| **Customer Portal** | Browse products, place cash-on-delivery orders, view order history, track deliveries live |
| **Point of Sale** | Walk-in and delivery sales, receipts, shareable tracking links |
| **Order Confirmation** | Staff review and confirm or decline online orders (stock is restored on decline) |
| **Inventory** | Add, edit, restock and delete products with photos; low-stock alerts |
| **Delivery (Rider)** | Accept orders, photo proof of pickup and delivery |
| **GPS Tracking** | Riders share their location; admin live map; customer tracking page |
| **Admin Dashboard** | Sales, orders, low stock, users and reports |

## Folder structure

```
assets/        CSS, JS, icons, product images, PH address data (regions → barangays)
backend/       PHP endpoints (login, orders, uploads, rider actions, geocoding)
config/        Database connection + secrets (secrets.php is NOT in this repo)
customer/      Customer portal pages
dashboard/     Admin, POS, Inventory and Rider pages
database/      schema.sql — table structure to create a fresh database
includes/      Shared auth check and navbar
```

## Run it locally (XAMPP)

1. Copy this folder into `C:\xampp\htdocs\` and start **Apache** and **MySQL**.
2. In phpMyAdmin, create a database named `smartpos` and import `database/schema.sql`.
3. Copy `config/secrets.example.php` to `config/secrets.php` and set your database login
   (XAMPP default: user `root`, empty password).
4. Create the first admin account in phpMyAdmin → SQL:
   ```sql
   INSERT INTO users (email, password, role, status)
   VALUES ('admin@example.com', '<hash>', 'admin', 'active');
   ```
   Get `<hash>` by running `php -r "echo password_hash('YourPassword', PASSWORD_DEFAULT);"`.
5. Open `http://localhost/` and log in at `/login.php` (staff) or `/customer_login.php` (customers).

> Paths in the code start with `/`, so the site expects to be at the web root
> (e.g. a virtual host or `htdocs` itself), not a subfolder.

### Google sign-in (optional)
Create an OAuth client in Google Cloud Console, put the ID and secret in `config/secrets.php`,
and add `http://localhost/backend/google_callback.php` as an authorized redirect URI.

## Security notes

- Passwords and API keys live only in `config/secrets.php`, which is git-ignored.
- Prices and totals are computed on the server from the database, never trusted from the browser.
- Orders and stock updates run in a single transaction; overselling is refused.
- Uploads are checked by real file type, renamed by the server, and scripts are blocked in `assets/images/`.
- All database queries that take user input use prepared statements.
