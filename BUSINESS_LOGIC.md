# XTREMEFIT — Business Logic & Workflow Overview

## 1. Executive Summary

XTREMEFIT is an online sportswear store serving the Lebanese market, selling men's, women's, and footwear collections from brands such as Nike, Adidas, Under Armour, and others. It gives shoppers a full self-service storefront — browsing, cart, checkout, order tracking, reviews, an AI shopping assistant, and virtual try-on — while giving the store owner a complete back-office to manage inventory, orders, pricing, promotions, and staff access. The system is built around a workflow the local market actually uses: cash-on-delivery payment and a map-based delivery address, rather than assuming card payment and postal addresses.

## 2. Problem Statement

Small sportswear resellers in Lebanon commonly operate through informal channels (e.g. Instagram direct messages): no searchable catalogue, no live stock visibility, no structured checkout, and no order history. This creates real business pain — overselling out-of-stock items, lost orders, no analytics, and a poor, slow buying experience for the customer. XTREMEFIT replaces that informal workflow with a real e-commerce platform, while keeping the payment and delivery model (cash on delivery, pinned map location) that actually fits how the local market transacts — rather than importing a card-payment, postal-address model that doesn't fit the market.

## 3. Target Users & Roles

The system defines three roles (`App\Models\User::ROLES`), plus an unauthenticated guest tier that the frontend treats as a fourth practical role:

| Role | Description | What they can do in the system |
|---|---|---|
| **Guest** (not logged in) | Any visitor who hasn't created an account | Browse products, use filters/search, build a cart (saved in the browser and merged into their account on login). Cannot check out, review, wishlist, use the AI assistant, or use virtual try-on — all of these require an account. |
| **Customer** | A registered shopper | Everything a guest can do, plus: checkout (cash on delivery), track/cancel their own pending orders, download invoices, maintain a wishlist, write one review per product, chat with the AI assistant, use virtual try-on, edit their profile. |
| **Admin** | Day-to-day store staff | Everything a customer can do, plus: manage the product catalogue (create/edit/delete, bulk sale pricing, bulk delete), manage orders (view all, change status, cancel), view the sales dashboard. Admins cannot manage other users' roles, store settings, categories, coupons, banners, or FAQs. |
| **Super Admin** | The store owner / senior operator | Everything an admin can do, plus the store-wide configuration surface: manage user roles, store settings (branding, delivery fee, homepage content), categories, coupons, FAQs, and announcement banners. One specific super-admin account (the seeded "store owner," matched by the email in `SUPER_ADMIN_EMAIL`) is permanently locked into this role and can never be demoted — this guarantees the store can never be left without an owner-level account. |

**Source:** `app/Models/User.php`, `app/Http/Middleware/AdminMiddleware.php`, `routes/api.php` (`admin` middleware group), per-controller `authorizeSuperAdmin()`/`requireSuperAdmin()` checks.

## 4. Core Domain Concepts

- **User** — an account with a role (customer / admin / super_admin); has one cart, many orders, many wishlist items, many reviews.
- **Product** — a catalogue item with a price, an optional sale price, a category, an optional single colour, and stock tracked per size (or as one overall stock number for items without sizes).
- **Category** — a grouping such as "Men Tops" or "Women Shoes"; a product belongs to exactly one category, and a category cannot be deleted while it still has products.
- **Cart** / **CartItem** — a customer (or guest, locally) has one cart made of line items, each a product + chosen size + chosen colour + quantity.
- **Order** / **OrderItem** — placing an order converts the cart into a permanent record; each order item freezes the price paid at that moment, independent of later price changes to the product.
- **Coupon** — a discount code (percentage or fixed amount) with optional expiry, usage cap, and minimum order amount.
- **Review** — a 1–5 star rating with an optional comment, tied to one customer and one product; a customer may only review a given product once.
- **WishlistItem** — a saved-for-later product, tied to a customer.
- **StoreSetting** — a flexible key/value table driving store-wide configuration (delivery charge, free-shipping threshold, homepage hero content, etc.) without needing new database columns for every new setting.
- **AnnouncementBanner** — a dismissible promotional strip shown across the storefront.
- **Faq** — a store-managed question/answer entry shown on the public FAQ page.

## 5. Key Workflows

### 5.1 Browsing and adding to cart (guest or customer)
- **Trigger:** A visitor opens the storefront.
- **Steps:**
  1. Products are listed with search, category, price-range, and sort filters.
  2. The visitor opens a product, chooses a size (if applicable), and adds it to the cart. The system will not let them exceed available stock for that size.
  3. If the visitor is not logged in, the cart is kept in the browser only.
  4. If they log in later, that browser cart is merged into their account's server-side cart (matching lines by product + size + colour combine their quantities).
- **Outcome:** A cart of purchasable items, correctly capped by real stock.
- **Source:** `app/Http/Controllers/Api/CartApiController.php`, `app/Models/Product.php` (`availableStockFor`), frontend `CartContext.js`.

### 5.2 Checkout and order placement
- **Trigger:** A logged-in customer proceeds from cart to checkout.
- **Steps:**
  1. Customer supplies a delivery address (free text) and, optionally, a pinned map location (latitude/longitude) — used because Lebanon lacks a reliable postal addressing system.
  2. Customer chooses a payment method — in practice, cash on delivery is the store's supported method.
  3. Customer may enter a coupon code.
  4. The system re-validates every cart line's stock at the moment of purchase (not just when it was added to cart), inside a single database transaction with row-level locking, so two simultaneous checkouts cannot both buy the last unit.
  5. If any line is out of stock, the entire order is rejected with a specific message — nothing is partially charged or partially reserved.
  6. If a coupon is supplied and is valid (active, not expired, under its usage cap, order meets its minimum), the discount is applied and the coupon's usage count increments.
  7. The order and its line items are created; each line item stores the price actually paid, so later price or sale changes never retroactively change a past order's total.
  8. Stock is deducted per size (and from the product's overall stock).
  9. A confirmation email (with PDF invoice) is queued to the customer, and a new-order alert is queued to the store admin — sent in the background so the customer isn't kept waiting on email delivery.
- **Outcome:** A new order in `pending` status; stock reduced; cart cleared.
- **Source:** `app/Services/CheckoutService.php`, `app/Http/Requests/CheckoutRequest.php`, `app/Models/Coupon.php`.

### 5.3 Order lifecycle management (admin)
- **Trigger:** An order needs to move forward (approved, delivered) or be stopped (rejected, cancelled).
- **Steps:**
  1. Admin views the order queue, optionally filtered by status or searched by order ID / customer.
  2. Admin changes an order's status (individually or in bulk).
  3. If the new status is `rejected` or `cancelled`, the system automatically restores the stock that order had reserved.
  4. If an order is moved *out* of `rejected`/`cancelled` back to an active status (e.g. re-approving a previously rejected order), the system deducts that stock again — so inventory never silently drifts from what was actually reserved.
  5. `cancelled` is treated as a dead end: once cancelled, an order can never be reactivated into any other status (this prevents re-selling stock that was already given back, and prevents restoring stock twice).
  6. A status-change email is queued to the customer whenever the status actually changes.
- **Outcome:** Order status reflects reality; product stock always matches what is genuinely reserved by active orders.
- **Source:** `app/Http/Controllers/Api/AdminOrderApiController.php`, `app/Services/CheckoutService.php` (`restoreStock`, `reapplyStock`).

### 5.4 Customer self-service cancellation
- **Trigger:** A customer wants to cancel their own order.
- **Steps:**
  1. Customer opens "My Orders" and requests cancellation.
  2. The system only allows this while the order is still `pending` — once an admin has approved it, the customer can no longer self-cancel.
  3. On cancellation, stock is restored exactly as in the admin flow, and a cancellation email is sent.
- **Outcome:** Order marked `cancelled`; stock returned to sale.
- **Source:** `app/Policies/OrderPolicy.php` (`cancel`), `app/Http/Controllers/Api/AdminOrderApiController.php` (`cancel`).

### 5.5 Product reviews
- **Trigger:** A logged-in customer wants to rate a product.
- **Steps:**
  1. Customer submits a 1–5 star rating and an optional comment.
  2. The system blocks a second review from the same customer on the same product.
  3. A review can be deleted by its author or by any admin.
- **Outcome:** A public rating/comment attached to the product, factored into its average rating shown to shoppers.
- **Source:** `app/Http/Controllers/Api/ReviewApiController.php`, `app/Policies/ReviewPolicy.php`.

### 5.6 AI shopping assistant
- **Trigger:** A logged-in customer opens the chat widget and asks a question.
- **Steps:**
  1. The message is scanned for shopping intent (product type, budget, sale/discount request, "cheapest" request, gender, or "outfit" request).
  2. If it's a genuine product query, the system pulls real, in-stock-aware catalogue candidates matching any detected budget/category/gender/sale filters, then ranks them by semantic similarity to the request using AI embeddings (skipping the semantic step for pure price-sorted requests like "cheapest" or "on sale").
  3. If the customer asks for a full "outfit" without specifying men's or women's, the assistant is instructed to ask that one clarifying question rather than guess — but for any other request (single item, browsing a category), it must not ask and should present matches directly.
  4. The assistant is given real store facts (that payment is cash-on-delivery only, current delivery fee/free-shipping threshold) so it never invents payment or shipping terms, and is restricted to only ever discussing XTREMEFIT shopping topics — off-topic questions get a fixed redirect reply instead of an answer.
- **Outcome:** A grounded, on-brand reply, optionally accompanied by real, clickable product recommendations.
- **Source:** `app/Http/Controllers/Api/ChatApiController.php`, `app/Services/EmbeddingService.php`.

### 5.7 Virtual try-on
- **Trigger:** A logged-in customer uploads their own photo on a clothing product page.
- **Steps:**
  1. Customer uploads a photo (validated as a real image, size-capped).
  2. The system reads the product's garment image from storage and sends both images to a third-party AI try-on service.
  3. The customer's uploaded photo is never saved to disk — it is only held in memory for the single request.
  4. This feature is limited to 5 generations per minute per user to control third-party API cost.
- **Outcome:** A generated preview image showing the customer wearing the garment.
- **Source:** `app/Http/Controllers/Api/TryOnApiController.php`, `app/Services/TryOnService.php`, `routes/api.php` (throttle middleware).

### 5.8 Store configuration (super admin)
- **Trigger:** The store owner needs to change how the storefront looks or behaves.
- **Steps:** Update homepage hero content/video, category tile images, top announcement bar text, delivery charge and free-shipping threshold, contact details, and other settings — all stored as flexible key/value pairs rather than fixed columns, so new settings can be added without a schema change.
- **Outcome:** Storefront reflects new configuration immediately (the public settings/category endpoints are cached for performance and invalidated on save).
- **Source:** `app/Http/Controllers/Api/StoreSettingApiController.php`, `app/Models/StoreSetting.php`.

### 5.9 User role management (super admin)
- **Trigger:** The store owner needs to promote a customer to staff, or change a staff member's access.
- **Steps:** Super admin searches/filters the user list and changes a target user's role to customer, admin, or super_admin.
- **Constraints:** A super admin cannot change their own role (prevents accidental self-lockout), and the permanent store-owner account can never have its role changed by anyone (guarantees the store always retains at least one super admin).
- **Outcome:** User's permissions change immediately on their next request.
- **Source:** `app/Http/Controllers/Api/AdminUserApiController.php`.

## 6. Business Rules & Constraints

| Rule | Why it exists / what it protects against | Where it's enforced |
|---|---|---|
| Stock is re-validated at checkout time, inside a locked database transaction, not just when added to cart | Prevents overselling when two customers try to buy the last unit at the same time | `CheckoutService::validateStock()` (row locking via `lockForUpdate`) |
| An order line freezes the unit price at purchase time | Ensures a later price change or sale never alters the amount a past customer actually agreed to pay | `CheckoutService::execute()` |
| A `cancelled` order can never transition to any other status | Prevents stock being restored twice (once on cancel, again on a bogus "re-cancel"), and prevents reselling units that were already released | `AdminOrderApiController::updateStatus()` |
| Moving an order into `rejected`/`cancelled` restores stock; moving it back out deducts stock again | Keeps recorded stock in sync with which orders are actually still "live," even through multiple status changes | `AdminOrderApiController::syncStockForTransition()`, `CheckoutService::restoreStock()` / `reapplyStock()` |
| A customer can only self-cancel while the order is still `pending` | Once an admin has approved an order, the customer can no longer unilaterally back out — protects the fulfilment process once it has started | `OrderPolicy::cancel()` |
| A coupon must be active, unexpired, under its usage cap, and the order must meet its minimum spend | Standard promotion-abuse guardrails (expired/overused/under-threshold codes must not apply) | `Coupon::isValidFor()` |
| A discount amount is capped so it can never exceed the order total | Prevents a fixed-amount coupon from producing a negative order total | `Coupon::calculateDiscount()` |
| A customer may only review a given product once | Prevents rating manipulation via repeated reviews from the same account | `ReviewApiController::store()` |
| A category cannot be deleted while it still has products | Prevents orphaning products with no category | `CategoryApiController::destroy()` |
| A super admin cannot change their own role; the seeded store-owner account's role can never be changed by anyone | Prevents accidental self-lockout and guarantees at least one permanent owner-level account always exists | `AdminUserApiController::updateRole()`, `User::isOwner()` |
| Admin-area routes require `isAdmin()`; several sub-areas (users, settings, categories, coupons, banners, FAQs) additionally require `isSuperAdmin()` inside the controller | Two-tier staff access: day-to-day admins run the shop floor, only the owner tier can reconfigure the store or its people | `AdminMiddleware`, per-controller `requireSuperAdmin()` checks |
| Login/register/password endpoints are rate-limited to 5 attempts per minute | Basic brute-force protection on authentication | `routes/api.php` (`throttle:5,1`) |
| Virtual try-on is rate-limited to 5 generations per minute per user | Controls cost exposure to the paid third-party AI try-on service | `routes/api.php` (`throttle:5,1`) |
| The AI assistant is only given real catalogue data and real store facts (payment method, delivery fee) to work from | Prevents the assistant from inventing prices, products, or policies that don't exist | `ChatApiController::generateAiReply()` |
| The AI assistant refuses to discuss anything outside XTREMEFIT shopping | Keeps the assistant on-brand and prevents it being used as a general-purpose chatbot | System prompt in `ChatApiController` |
| The customer's uploaded try-on photo is never written to disk | Privacy protection — a stranger's photo isn't persisted anywhere on the server | `TryOnApiController::generate()` |
| A guest's cart persists locally and merges into their account on login; a guest's wishlist does not persist past logout | Deliberate difference: cart-building shouldn't require an account (reduces friction), but the wishlist is treated as an account feature | `CartContext.js`, `WishlistContext.js` (frontend) |

## 7. State Machines / Status Lifecycles

**Order status** (`orders.status`):

```
pending → approved → delivered
pending → rejected
pending → cancelled   (customer self-cancel, or admin cancel)
approved → rejected   (admin can still reject after approval)
approved/rejected/delivered → cancelled   (admin cancel, terminal)
rejected → approved / delivered   (admin can reactivate a rejected order)
cancelled → (nothing — terminal, no further transitions allowed)
```

- Entering `rejected` or `cancelled` from any non-terminal state **restores** the reserved stock.
- Leaving `rejected` for any active state **re-deducts** that stock.
- `cancelled` is a one-way door: no code path allows a transition out of it.
- Customers can only trigger the `pending → cancelled` edge, and only while still `pending`. All other transitions are admin-only.

**Coupon validity** is not a persisted state machine but a computed check evaluated fresh on every use: `is_active` flag AND not expired AND under `max_uses` AND order meets `min_order_amount`. There is no separate "expired" status stored — expiry is derived from `expires_at` at check time.

**Source:** `app/Http/Controllers/Api/AdminOrderApiController.php`, `app/Policies/OrderPolicy.php`, `app/Models/Coupon.php`.

## 8. Integrations & Their Business Purpose

| Integration | Business purpose |
|---|---|
| **OpenAI** (Chat Completions + Embeddings) | Powers the AI shopping assistant's replies and its semantic product search, so customers can describe what they want in plain language instead of using filters. |
| **FASHN** (virtual try-on API) | Generates a preview of a customer wearing a garment, reducing the "will this look good on me" hesitation that causes cart abandonment in online clothing sales. |
| **Google Maps** | Lets a customer pin their exact delivery location, working around Lebanon's unreliable postal addressing. |
| **Google Sign-In** | Lowers the signup barrier for customers who'd rather not create a new password. |
| **Cloudflare R2** (object storage) | Hosts product images, hero media, and other uploaded assets outside the application server, so they survive server redeploys and scale independently. |
| **SMTP email** | Sends order confirmations (with PDF invoice attached), new-order alerts to the store, and order status-change notifications — the customer's and store's paper trail for every order. |

## 9. Edge Cases & Validation Handled

- Two customers checking out the same last unit at the same time — resolved with a locked database transaction, not just an application-level check.
- Checking out with an item that went out of stock after it was added to the cart — re-validated at the moment of purchase, not trusted from cart state.
- A coupon that expired, hit its usage cap, or the order doesn't meet its minimum spend — rejected at apply time with a clear reason.
- A fixed-amount coupon larger than the order total — discount is capped so the order can never go negative.
- Submitting a duplicate review on the same product — blocked with an explicit error rather than creating a second row.
- Deleting a category that still has products in it — blocked, with a message telling the admin to reassign or delete the products first.
- An admin trying to reactivate a `cancelled` order — explicitly blocked (`422` with a clear message), since it's a terminal state.
- A super admin trying to change their own role, or anyone's attempt to change the seeded store-owner's role — both explicitly blocked.
- Guest users attempting checkout, wishlist, reviews, chat, or try-on — these all require authentication; a guest is redirected to log in rather than allowed to hit the underlying endpoint (and the endpoints themselves also sit behind `auth:sanctum`, so this isn't just a UI-level restriction).
- The AI assistant being asked something off-topic, or for an "outfit" without specifying gender — both handled with fixed, deliberate response behavior instead of the model improvising.
- Brute-force login/password-reset attempts and try-on API abuse — both rate-limited server-side.

## 10. Notable Gaps / Assumptions

- **Only one payment method is really supported end-to-end.** The checkout form's validation still technically accepts `card` as a payment method value, but the AI assistant, store copy, and business workflow are all built exclusively around cash on delivery. Card payment does not appear to be actually processed anywhere (no payment-gateway integration exists in the codebase).
- **No explicit "delivered → returned/refunded" workflow.** The status list stops at `delivered`; there is no modelled post-delivery return or refund process.
- **Coupon rules apply per coupon, not per customer.** There is a global `max_uses` counter on the coupon itself, but nothing in the code limits how many times one specific customer can use a given code (short of the global cap).
- **Guest wishlist behavior is a frontend decision, not a backend one.** The backend wishlist endpoints simply require authentication like everything else; the choice to *not* persist a wishlist locally for guests (unlike the cart) is implemented in the frontend `WishlistContext`, not as a distinct backend rule — worth knowing if asked "is this enforced by the server?"
- **The `admin`-role vs `super_admin`-role split is enforced inconsistently in mechanism** (though consistently in outcome): the whole `/admin` route group requires `isAdmin()` via middleware, but the *further* restriction to `super_admin`-only for users/settings/categories/coupons/banners/FAQs is implemented as a manual check inside each of those individual controllers, rather than as a second, dedicated middleware layer. Functionally correct, but worth knowing before a panel question like "walk me through your authorization layers."

## 11. Suggested Slide Outline

1. Title
2. Problem Statement — the informal-reseller pain point and why the local payment/delivery model matters
3. Target Users & Roles (table from Section 3)
4. System Overview — core entities (Section 4, kept to one relationship diagram or list)
5. Workflow: Checkout & Order Placement (Section 5.2) — this is the operational core of the business
6. Workflow: Order Lifecycle & Stock Integrity (Sections 5.3/5.4 + the state diagram from Section 7) — strong material, shows real engineering thought about a real business risk (overselling)
7. Workflow: AI Shopping Assistant (Section 5.6) — the most differentiated feature versus a generic store
8. Business Rules Highlight — pick 4–5 rows from Section 6 that best demonstrate deliberate design (stock locking, coupon guardrails, terminal cancelled state, review dedup, rate limiting)
9. Integrations at a Glance (Section 8, condensed to icons + one-liners)
10. Architecture at a Glance — one high-level diagram only (reference the earlier architecture diagram already produced for this project)
11. Challenges & Design Decisions — worth pulling from Section 10 honestly (e.g. "we deliberately scoped to cash-on-delivery only because that's what the market needs")
12. Demo / Q&A
