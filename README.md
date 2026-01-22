# Shop Platform – Backend (Laravel)

A simple e-commerce backend built with Laravel, used as the API and admin panel for a small online shop.  
It powers a React single-page application (SPA) frontend.

---

## 1. Tech Stack

- **Framework:** Laravel 8.x
- **Language:** PHP 7.4
- **Database:** MySQL
- **Auth:** Built-in Laravel auth (login/register), roles (`admin` / `customer`)
- **Web Server:** PHP built-in server (via `php artisan serve`) or WAMP/XAMPP

---

## 2. Main Features

### Users and Roles

- Registration with a **role** field:
  - `customer` (default)
  - `admin` (store owner)
- Extra `is_admin` boolean used to protect admin routes.
- Login / logout handled by Laravel auth scaffolding.
- After registration / login, users are redirected to the homepage.

### Products and Categories

- Product CRUD for admins:
  - Name, category, description, price, stock.
  - Image upload stored in `storage/app/public/products`.
  - Sizes stored as an array (`sizes`) for each product.
- Categories table (examples: men top, women top, men pants, men shoes, etc.).

### Sizes Logic

- For clothing categories: sizes like `S, M, L, XL, XXL`.
- For shoes categories: numeric sizes `20–47`.
- Admin can select **available sizes** per product.
- Frontend and cart use these sizes so customers can only choose valid sizes.

### Cart & Orders (Customer Side)

- Session-based cart:
  - Add product with chosen size and quantity.
  - Update quantity and size.
  - Remove items.
- Checkout:
  - Creates an `orders` record and related `order_items`.
  - Stores:
    - `user_id`
    - `payment_method` (e.g. Cash)
    - `status` (`pending`, `processing`, `completed`, `cancelled`)
    - `total`
    - `address` (delivery address)
- “My Orders” page:
  - View list of orders.
  - View order details with items, quantities, and totals.

### Order Management (Admin)

- View all orders.
- Filter orders and see details.
- Update order status (e.g. from `pending` to `processing` or `completed`).

---

## 3. Project Structure (Backend)

```text
shop-platform/
├─ app/
│  ├─ Http/
│  │  ├─ Controllers/
│  │  │  ├─ HomeController.php
│  │  │  ├─ ProductController.php
│  │  │  ├─ CartController.php
│  │  │  ├─ OrderController.php
│  │  │  └─ Admin/
│  │  │     ├─ ProductController.php
│  │  │     └─ OrderController.php
│  │  └─ Middleware/
│  │     └─ AdminMiddleware.php
│  ├─ Models/
│  │  ├─ User.php
│  │  ├─ Product.php
│  │  ├─ Category.php
│  │  ├─ Order.php
│  │  └─ OrderItem.php
├─ database/
│  ├─ migrations/
│  └─ seeders/   (optional)
├─ public/
├─ resources/
│  └─ views/     (Blade views for server-side pages)
├─ routes/
│  ├─ web.php
│  └─ api.php
└─ ...
