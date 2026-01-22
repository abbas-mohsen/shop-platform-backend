# Shop Platform – Backend (Laravel)

A simple e-commerce backend built with Laravel for managing products, sizes, categories, carts, and orders.  
This backend is used by the **React SPA frontend** to provide a basic online shop experience.

---

## Tech Stack

- **Framework:** Laravel 8
- **Language:** PHP 7.4
- **Database:** MySQL
- **Auth:** Laravel auth (login/register), roles (`admin` / `customer`)
- **Other:** Laravel Sanctum (if you enable API auth later)

---

## Main Features

- User registration & login
  - Role selection: `customer` or `admin`
  - `is_admin` flag used for protecting admin routes
- Product management (admin)
  - CRUD for products
  - Product categories (e.g. men top, women top, shoes, etc.)
  - Product sizes:
    - Clothing: `S, M, L, XL, XXL`
    - Shoes: numeric sizes (20–47)
  - Product images stored in `/storage/app/public/products`
- Cart & order flow (customer)
  - Add products to cart with **size** and **quantity**
  - Update cart items
  - Checkout to create an order + order items
  - Store customer address and payment method
- Order management (admin)
  - View all orders
  - Update order status (`pending`, `processing`, `completed`, `cancelled`)

---

## Project Structure (backend)

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
│  └─ seeders/
├─ public/
├─ resources/
│  └─ views/
├─ routes/
│  ├─ web.php
│  └─ api.php
└─ ...
