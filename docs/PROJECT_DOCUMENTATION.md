# SwiftPOS — Complete Project Documentation

> A Step-by-Step Technical Walkthrough of every component, code layer, and integration in the SwiftPOS system.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Directory Structure](#3-directory-structure)
4. [Database Schema & Migrations](#4-database-schema--migrations)
5. [Models & Relationships](#5-models--relationships)
6. [Authentication System](#6-authentication-system)
7. [Role-Based Access Control (RBAC)](#7-role-based-access-control-rbac)
8. [Middleware](#8-middleware)
9. [Routing Architecture](#9-routing-architecture)
10. [Services Layer](#10-services-layer)
11. [Controller Layer](#11-controller-layer)
12. [Request Validation](#12-request-validation)
13. [POS Module](#13-pos-module)
14. [Marketplace Module](#14-marketplace-module)
15. [Cart System](#15-cart-system)
16. [InstaPay Payment Flow](#16-instapay-payment-flow)
17. [Admin Panel](#17-admin-panel)
18. [Frontend Architecture](#18-frontend-architecture)
19. [Configuration & Environment](#19-configuration--environment)
20. [Testing](#20-testing)

---

## 1. Project Overview

**SwiftPOS** is a full-stack Point of Sale (POS) and Marketplace system built for restaurants and retail businesses. It provides:

- **POS Terminal** — Cashier-operated checkout with session management
- **Marketplace** — Card-based browsable storefront with product listings
- **Cart & Checkout** — Server-side cart with guest localStorage fallback
- **InstaPay** — Manual bank payment flow with QR code, screenshot upload, and admin approval
- **Admin Panel** — Full CRUD for categories, cards, stores, products, and payment management
- **Inventory Tracking** — Stock management with movement history
- **Multi-role Authentication** — Credentials + Google/Facebook OAuth with RBAC

---

## 2. Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| **Backend** | Laravel (PHP) | 11.x |
| **Frontend** | Vue 3 (Composition API) | 3.4+ |
| **SPA Bridge** | Inertia.js | 2.x |
| **Styling** | Tailwind CSS | 3.x |
| **Build Tool** | Vite | 7.x |
| **Type System** | TypeScript | 5.x |
| **Auth** | Laravel Breeze + Socialite | — |
| **RBAC** | Spatie Permission | — |
| **Database** | SQLite (dev) / MySQL (prod) | — |
| **Font** | Manrope (Google Fonts) | — |
| **QR Codes** | qrcode (npm) | 1.5.x |

---

## 3. Directory Structure

```
swiftpos/
├── app/
│   ├── Console/           # Artisan commands
│   ├── Http/
│   │   ├── Controllers/   # 15 main controllers + Admin/ + Auth/ sub-dirs
│   │   ├── Middleware/     # EnsureRole, HandleInertiaRequests
│   │   └── Requests/      # 6 form request validators + Admin/ sub-dir
│   ├── Models/            # 13 Eloquent models
│   ├── Policies/          # CardPolicy, CategoryPolicy
│   ├── Providers/         # AppServiceProvider
│   ├── Services/          # OrderService, InventoryService, ImageService
│   └── Support/           # PosCache helper
├── config/                # 13 config files (including instapay.php)
├── database/
│   ├── factories/         # 8 model factories
│   ├── migrations/        # 37 migration files
│   └── seeders/           # 6 seeders
├── resources/
│   ├── css/app.css        # Tailwind + custom design system
│   ├── js/
│   │   ├── Components/    # 16 reusable Vue components
│   │   ├── Layouts/       # AuthenticatedLayout, GuestLayout
│   │   ├── Pages/         # 14 page directories (Dashboard, POS, etc.)
│   │   ├── stores/        # cart.ts (reactive cart store)
│   │   ├── types/         # TypeScript declarations
│   │   ├── app.ts         # Inertia app bootstrap
│   │   └── bootstrap.ts   # Axios setup
│   └── views/
│       └── app.blade.php  # Root Blade template
├── routes/
│   ├── web.php            # All application routes
│   └── auth.php           # Authentication routes
├── tests/                 # PHPUnit feature + unit tests
└── public/                # Public assets
```

---

## 4. Database Schema & Migrations

The database is built through **37 migrations** that progressively add features.

### Core Tables

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `users` | User accounts | `name`, `email`, `password`, `provider`, `provider_id`, `google_id`, `avatar` |
| `categories` | Product/card categories | `name`, `slug`, `description`, `is_active` |
| `products` | Items for sale | `category_id`, `store_id`, `card_id`, `sku`, `barcode`, `name`, `price`, `cost`, `tax_rate`, `track_inventory`, `stock_quantity` |
| `orders` | Sales transactions | `order_number`, `user_id`, `pos_session_id`, `store_id`, `status`, `total_amount`, `payment_status`, `payment_method` |
| `order_items` | Line items per order | `order_id`, `product_id`, `quantity`, `unit_price`, `line_total` |
| `payments` | Payment records | `order_id`, `user_id`, `method`, `amount`, `status`, `paid_at` |
| `pos_sessions` | Cashier sessions | `user_id`, `opened_at`, `closed_at`, `opening_cash`, `closing_cash`, `status` |
| `inventory_movements` | Stock change history | `product_id`, `user_id`, `movement_type`, `quantity`, `previous_stock`, `new_stock` |
| `stores` | Marketplace stores | `category_id`, `name`, `slug`, `owner_id`, `image_path` |
| `cards` | Dashboard/marketplace cards | `category_id`, `parent_id`, `store_id`, `title`, `slug`, `image_path`, `color`, `route_name` |
| `carts` | User shopping carts | `user_id` |
| `cart_items` | Items in cart | `cart_id`, `product_id`, `quantity` |
| `restaurants` | Restaurant records | `name`, `address`, `phone` |

### Migration Evolution

The migrations show an iterative development process:

1. **Base tables** (`0001_01_01_*`) — Users, cache, jobs
2. **RBAC** (`2026_03_09_112105`) — Spatie permission tables
3. **POS core** (`2026_03_09_112117` – `112123`) — Categories → Products → Inventory → Sessions → Orders → Payments
4. **OAuth** (`2026_03_09_124322`, `135802`) — Social login columns
5. **Session safety** (`2026_03_09_220500`) — Unique constraint for open sessions
6. **Stores & Cards** (`2026_03_10` – `2026_03_11`) — Restaurant, store, card hierarchy
7. **Cart** (`2026_03_11_170000`) — Cart + cart_items tables
8. **Marketplace links** (`2026_03_11` – `2026_03_12`) — Store-product, card-product relationships
9. **InstaPay** (`2026_03_14`) — Payment fields, expiry, access tokens, nullable user_id

---

## 5. Models & Relationships

### Entity Relationship Diagram

```
User ─┬─< Order ─┬─< OrderItem ──> Product
      │          └─< Payment              │
      ├─< PosSession ─< Order             ├──> Category
      ├─< InventoryMovement               ├──> Store ──> Category
      ├── Cart ─< CartItem ──> Product     └──> Card ──> Category
      └─< Payment                                 │
                                                   ├──> Card (parent)
                                                   └──> Store
```

### Model Details

#### `User` (`app/Models/User.php`)
- Uses `HasFactory`, `HasRoles` (Spatie), `Notifiable`
- Fillable: `name`, `email`, `email_verified_at`, `password`, `provider`, `provider_id`, `google_id`, `avatar`
- Hidden: `password`, `remember_token`
- Casts: `email_verified_at` → datetime, `password` → hashed
- Relations: `orders()`, `posSessions()`, `inventoryMovements()`, `payments()`, `cart()`

**Code walkthrough**: The `User` model extends Laravel's `Authenticatable` class and mixes in Spatie's `HasRoles` trait, enabling role/permission checks like `$user->hasRole('admin')` throughout the codebase. The `provider`/`provider_id`/`google_id` fields support OAuth logins.

#### `Order` (`app/Models/Order.php`)
- Uses integer-based money (stored as `decimal:2` strings) for precision
- Relations: `user()`, `posSession()`, `store()`, `items()`, `payments()`
- Accessor: `getBalanceDueAttribute()` — calculates `total_amount - paid_amount`
- Fillable includes `access_token` (for guest InstaPay), `instapay_screenshot`, `expires_at`

#### `Product` (`app/Models/Product.php`)
- Scope: `scopeActive()` — filters `is_active = true`
- Method: `isLowStock()` — checks `stock_quantity <= low_stock_threshold`
- Relations: `category()`, `store()`, `card()`, `inventoryMovements()`, `orderItems()`

#### `Card` (`app/Models/Card.php`)
- Self-referential parent-child hierarchy via `parent_id`
- Method: `effectiveStoreId($maxDepth)` — walks up the parent chain to find the inherited store_id

#### `Store` (`app/Models/Store.php`)
- Auto-generates unique slug via `booted()` model event
- Relations: `category()`, `products()`, `cards()`, `owner()`

---

## 6. Authentication System

### Standard Authentication
Located in `app/Http/Controllers/Auth/`:

1. **Registration** (`RegisteredUserController.php`):
   - Validates name, email (unique, lowercase), password (confirmed)
   - Creates user with hashed password
   - Assigns default `cashier` role via Spatie
   - Fires `Registered` event and auto-logs in

2. **Login** (`AuthenticatedSessionController.php`):
   - Uses `LoginRequest` form request for validation
   - Regenerates session on login
   - Redirects to intended URL or dashboard

3. **Logout**: Invalidates session + regenerates CSRF token

### Social OAuth Authentication
`SocialAuthController.php` handles Google and Facebook OAuth:

```
Flow: Click OAuth button → Redirect to provider → Provider callback → Match/create user → Login
```

**Step-by-step**:
1. `redirectToProvider()` — checks if provider is configured (env vars), redirects to Google/Facebook
2. `handleProviderCallback()` — receives OAuth response, extracts social user info
3. `loginWithSocialUser()`:
   - Searches for existing user by `provider + provider_id`
   - Falls back to `provider + google_id` (legacy)
   - Falls back to matching `email`
   - Creates new user if not found (with random password)
   - Assigns `cashier` role if no roles exist
   - Logs in with "remember me"

---

## 7. Role-Based Access Control (RBAC)

Uses **Spatie Laravel Permission** package with three roles:

| Role | Capabilities |
|------|-------------|
| `admin` | Full access: manage users, categories, stores, cards, products, marketplace, InstaPay, POS |
| `manager` | Products, inventory, POS, orders, payments |
| `cashier` | POS terminal, orders (own), payments |

### Enforcement Points
- **Route middleware**: `role:admin`, `role:admin|manager`, `role:admin|manager|cashier`
- **Custom middleware**: `EnsureRole` — normalizes pipe-delimited role strings
- **Request authorization**: `StoreOrderRequest`, `InventoryAdjustmentRequest`, `StoreProductRequest` check roles in `authorize()`
- **Policies**: `CardPolicy`, `CategoryPolicy` — restrict CRUD to admin role
- **Controller logic**: `$user->hasAnyRole(['admin', 'manager'])` for conditional data access

---

## 8. Middleware

### `EnsureRole` (`app/Http/Middleware/EnsureRole.php`)
Custom middleware that:
1. Checks user is authenticated (401 if not)
2. Normalizes roles (splits `admin|manager` into array)
3. Checks `hasAnyRole()` (403 if unauthorized)

### `HandleInertiaRequests` (`app/Http/Middleware/HandleInertiaRequests.php`)
Shares global data to every Vue page:
- `auth.user` — id, name, email, avatar, avatar_url, roles, permissions
- `flash.success` / `flash.error` / `flash.order` — session flash messages

---

## 9. Routing Architecture

### `routes/web.php` Structure

```
Public (guest)
├── GET  /                    → Login page (home)

Public (no auth required)
├── GET  /instapay/{order}    → InstaPay payment page
├── POST /instapay/{order}/confirm    → Mark payment as sent
├── POST /instapay/{order}/screenshot → Upload payment proof

Auth Required
├── GET  /dashboard           → Dashboard
├── POST /instapay/initiate   → Create InstaPay order
├── CRUD /profile             → User profile
├── CRUD /cart                → Cart operations
├── GET  /cards/{slug}        → Marketplace card view
├── GET  /cards/{slug}/product → Card products
├── GET  /categories/{slug}   → Category view
│
├── role:admin|manager
│   ├── CRUD /products        → Product management
│   └── /inventory            → Inventory management
│
├── role:admin
│   └── /admin/...            → Full admin panel
│       ├── categories, stores, products CRUD
│       ├── marketplace management
│       ├── card management
│       └── instapay approval/rejection
│
└── role:admin|manager|cashier
    ├── /orders               → Order listing/detail
    ├── /payments             → Payment listing
    └── /pos                  → POS terminal
```

### `routes/auth.php` Structure
Standard Laravel Breeze auth routes + Social OAuth routes for Google and Facebook.

---

## 10. Services Layer

### `OrderService` (`app/Services/OrderService.php`)
The most complex service — handles all order creation with precise financial calculations.

**Key design decision**: Uses integer-based arithmetic internally (cents/millis) to avoid floating-point precision errors, then converts back to decimal strings for storage.

#### Constants
```php
MONEY_SCALE    = 2  // e.g., 1099 = $10.99
QUANTITY_SCALE = 3  // e.g., 1500 = 1.500 units
TAX_RATE_SCALE = 2  // e.g., 1000 = 10.00%
```

#### Methods

1. **`checkout(array $payload, User $actor): Order`**
   - Used by POS terminal checkout
   - Locks POS session and products (prevents race conditions)
   - Validates session is open and belongs to actor
   - Calculates per-item: subtotal, discount, tax, line total
   - Determines payment status: `paid` / `partial` / `unpaid`
   - Creates payment record if money tendered
   - Adjusts inventory for tracked products

2. **`checkoutMarketplace(Cart $cart, User $actor): Order[]`**
   - Groups cart items by `store_id`
   - Creates separate orders per store
   - Simpler calculation (no tax, no discount)
   - Clears cart after successful checkout

3. **`createFromProduct(Product $product, int $quantity, ?User $actor, array $overrides): Order`**
   - Single-product order creation (used by InstaPay)
   - Accepts overrides for payment_method, status, etc.
   - Also adjusts inventory

4. **`generateOrderNumber(): string`**
   - Format: `SP-YYYYMMDDHHmmss-XXXX` (4 random uppercase chars)
   - Ensures uniqueness via DB check loop

5. **Helper methods**:
   - `toScaledInt()` — converts `"10.99"` → `1099` with banker rounding
   - `roundDiv()` — integer division with proper rounding
   - `formatMoney()` / `formatScaled()` — converts back to `"10.99"`

### `InventoryService` (`app/Services/InventoryService.php`)
- Single method: `adjustStock()`
- Uses pessimistic locking (`lockForUpdate`) to prevent race conditions
- Validates sufficient stock for tracked products
- Creates `InventoryMovement` record with before/after stock levels
- Supports polymorphic references (links movement to source order)

### `ImageService` (`app/Services/ImageService.php`)
- `storeProductImage()` — saves to `storage/app/public/products/`
- `replaceProductImage()` — deletes old image, stores new one
- `deleteProductImage()` — removes file from disk

---

## 11. Controller Layer

### Main Controllers

| Controller | Purpose | Key Actions |
|-----------|---------|-------------|
| `DashboardController` | Dashboard page (invokable) | Stats, recent orders, low stock, category cards |
| `POSController` | POS terminal | `index`, `openSession`, `closeSession`, `checkout` |
| `OrderController` | Order viewing | `index` (filtered, paginated), `show` (with items/payments) |
| `PaymentController` | Payment listing | `index` (filtered by method, status, date range) |
| `ProductController` | Product CRUD | Full resource with image upload, slug generation |
| `InventoryController` | Stock management | `index`, `adjust` (with direction: in/out) |
| `CartController` | Shopping cart | `show`, `storeItem`, `updateItem`, `destroyItem`, `checkout` |
| `InstaPayController` | InstaPay flow | `initiate`, `show`, `confirmSent`, `uploadScreenshot` |
| `MarketplaceCardController` | Public card view | `show` (children), `products` |
| `MarketplaceCategoryController` | Public category | `show` (cards list) |
| `ProfileController` | Profile management | `edit`, `update`, `destroy` |
| `UserController` | User listing | `index` (admin only) |
| `RestaurantController` | Restaurant listing | `index` |
| `StoreController` | Store listing | `index` |

### Admin Controllers (in `app/Http/Controllers/Admin/`)

| Controller | Purpose |
|-----------|---------|
| `MarketplaceController` | Full marketplace management: categories, cards, stores, products |
| `CardController` | Card CRUD with image upload, reordering, toggle active |
| `CategoryController` | Category CRUD with slug generation |
| `StoreController` | Store CRUD with image upload |
| `ProductController` | Simple product CRUD (for admin marketplace view) |
| `InstaPayController` | Approve/reject InstaPay payments |

---

## 12. Request Validation

### `StoreOrderRequest`
- Authorization: must have `admin`, `manager`, or `cashier` role
- Validates: `pos_session_id`, `order_type` (retail/dine_in/takeaway/delivery), `items[].product_id`, `items[].quantity`, `payment_method`, `amount_tendered`
- Defaults: `order_type` → retail, `payment_method` → cash, `amount_tendered` → 0

### `StoreProductRequest`
- Authorization: must have `admin` or `manager` role
- Validates: name, sku (unique), barcode, category, price, cost, tax_rate, inventory settings, image
- Special: `marketplace_simple` flag auto-generates SKU from product name

### `CardRequest`
- Open authorization (admin routes handle protection)
- Validates: title, description, icon, image (file), color (enum), route_name, permission, role, sort_order

### `InventoryAdjustmentRequest`
- Authorization: `admin` or `manager`
- Validates: quantity (> 0), direction (in/out), movement_type (restock/purchase/adjustment/return)

---

## 13. POS Module

The POS (Point of Sale) module enables cashier operations:

### Flow

```
1. Open Session → Enter opening cash amount
2. Browse Products → Search/filter by category
3. Add Items → Select products, set quantities
4. Checkout → Choose payment method, enter amount tendered
5. Receipt → Order number, totals, change due
6. Close Session → Enter closing cash, compare to expected
```

### Session Management
- Each user can have **only one open session** (enforced by unique partial index)
- Opening a session records the `opening_cash` amount
- Closing calculates `expected_cash` = opening_cash + sum(cash payments in session)
- Cash variance = `closing_cash - expected_cash`

### Checkout Process (`OrderService::checkout`)
1. Lock the POS session (prevents concurrent modifications)
2. Validate session is open and belongs to current user
3. Lock all referenced products
4. Calculate per-item: `line_subtotal = qty × price`, `tax = (subtotal - discount) × tax_rate`
5. Calculate order totals: subtotal, discount, tax, total
6. Determine payment status based on amount tendered vs total
7. Create payment record
8. Adjust inventory for tracked products
9. Return completed order with items and payments

### Caching
`PosCache` maintains a version-based cache key for product listings:
- Key format: `swiftpos.products.{version}.{md5(search|category)}`
- Cache TTL: 2 minutes
- Version bumped on any product/inventory change

---

## 14. Marketplace Module

The marketplace provides a card-based browsable storefront.

### Hierarchy

```
Category (e.g., "Food & Beverages")
└── Card (e.g., "Restaurants")
    └── Card (child) (e.g., "Pizza Places")
        └── Store (e.g., "Mario's Pizzeria")
            └── Product (e.g., "Margherita Pizza")
```

### Card System
- Cards are hierarchical (parent-child via `parent_id`)
- Each card can link to a `store_id` (directly or inherited from parents via `effectiveStoreId()`)
- Cards have: title, description, icon, image, color theme, route_name
- Cards can be restricted by `permission` or `role`
- Sort order is manually managed via drag-and-drop reordering

### Dashboard Integration
The dashboard dynamically builds the navigation grid from categories and cards:
1. Fetches active categories
2. For each category, loads active root cards (no parent)
3. Filters cards by user's permissions/roles
4. Renders as color-coded clickable grid

---

## 15. Cart System

### Architecture (`resources/js/stores/cart.ts`)
The cart uses a **composable reactive store** (not Pinia — simpler pattern using Vue 3 `reactive` and `ref`).

### Dual Mode
- **Authenticated users**: Cart persisted server-side via `carts`/`cart_items` tables
- **Guest users**: Cart stored in `localStorage` under key `marketplace_guest_cart`

### Key Operations

| Function | Auth Mode | Guest Mode |
|----------|-----------|------------|
| `addToCart()` | POST `/cart/items` | Write to localStorage |
| `updateQuantity()` | PATCH `/cart/items/{id}` | Update localStorage |
| `removeItem()` | DELETE `/cart/items/{id}` | Remove from localStorage |
| `checkout()` | POST `/cart/checkout` | Not available |
| `buyNowInstapay()` | POST `/instapay/initiate` | POST `/instapay/initiate` |

### Guest → Auth Merge
When a guest user logs in, `mergeGuestCart()`:
1. Reads items from localStorage
2. POSTs each item to the server cart
3. Clears localStorage
4. Fetches the merged server cart

---

## 16. InstaPay Payment Flow

InstaPay is a manual bank payment system:

### Customer Flow

```
1. Click "Buy Now" on product → POST /instapay/initiate
2. Server creates pending order with access token
3. Redirect to /instapay/{order}?token=xxx
4. Page shows: order summary, QR code (InstaPay deep link), instructions
5. Customer sends payment via InstaPay app
6. Customer clicks "I've Sent Payment" → POST /instapay/{order}/confirm
7. Optionally uploads payment screenshot → POST /instapay/{order}/screenshot
8. Order status changes to "waiting_confirmation"
```

### Admin Flow

```
1. Navigate to Admin → InstaPay → see pending payments
2. Review order details + screenshot
3. Click "Approve" → Order becomes confirmed + paid
4. Or click "Reject" → Order cancelled with reason
```

### Security
- Each order gets a unique `access_token` (40 chars)
- Guest orders use the token for access verification
- Authenticated users verified by `user_id` match
- Admin/manager can view any order
- Token comparison uses `hash_equals()` (timing-safe)
- Optional order expiration via `INSTAPAY_ORDER_EXPIRATION_MINUTES`

---

## 17. Admin Panel

### Marketplace Manager (`Admin\MarketplaceController`)
The most complex admin page — a unified view for managing:
- **Categories**: CRUD with slug generation
- **Cards**: CRUD with image upload, parent-child hierarchy, reordering
- **Stores**: CRUD with image upload, category assignment
- **Products**: CRUD with automatic store assignment via card hierarchy

### Card Management (`Admin\CardController`)
Separate full card management:
- Create/Edit with form (title, icon, image, color, route, permission, role)
- Image upload with automatic cleanup on replace/delete
- Active/inactive toggle
- Drag-and-drop reordering (saves sort_order via batch update)

### InstaPay Management (`Admin\InstaPayController`)
- Lists all InstaPay orders filtered by pending/all
- Approve: sets `payment_status` → paid, `status` → confirmed, creates payment record
- Reject: sets `payment_status` → rejected, `status` → cancelled, records reason

---

## 18. Frontend Architecture

### Inertia.js Integration
- **`app.ts`**: Bootstraps Inertia with Vue 3, `ZiggyVue` (route helper), and component resolution
- **Page resolution**: `./Pages/${name}.vue` — maps server route responses to Vue components

### Layout System

#### `AuthenticatedLayout.vue`
The main application shell:
- **Sidebar** (left): Dark gradient, collapsible on mobile, shows navigation items based on role
- **Header** (top): Quick actions (New Sale, Add Product), notifications bell, cart button, user dropdown
- **Main content**: Slot-based content area with gradient overlays
- **Cart overlay**: Slide-in panel on right showing cart items with remove buttons
- **Footer**: Professional footer with branding and links

#### `GuestLayout.vue`
Simple centered card layout for login/register with decorative gradients and footer.

### Key Vue Pages

| Page | File | Description |
|------|------|-------------|
| Dashboard | `Pages/Dashboard.vue` | Stats cards, category navigation grid, recent orders, low stock alerts |
| POS Cashier | `Pages/POS/Cashier.vue` | Product grid, cart panel, checkout form, session management |
| InstaPay | `Pages/Payment/InstaPay.vue` | Order summary, QR code, confirm/screenshot buttons |
| Admin Marketplace | `Pages/Admin/Marketplace.vue` | Full CRUD interface for categories, cards, stores, products |
| Admin InstaPay | `Pages/Admin/InstaPayPayments.vue` | Pending payments list with approve/reject actions |

### Design System (`resources/css/app.css`)
Custom component classes built on Tailwind:
- `.pos-panel` / `.pos-panel-solid` — Glass/solid card containers
- `.pos-btn-*` — Button variants (primary, secondary, outline, ghost, danger)
- `.pos-badge-*` — Status badges (success, warning, neutral, danger)
- `.pos-table` — Styled table with hover effects
- `.pos-input` — Form inputs matching the design language
- `.animate-pos-fade-in` — Entry animation for page content

---

## 19. Configuration & Environment

### Key Config Files

| File | Purpose |
|------|---------|
| `config/instapay.php` | InstaPay phone, account name, expiration, deeplink |
| `config/roles.php` | Role definitions |
| `config/permission.php` | Spatie permission config |
| `config/services.php` | Google/Facebook OAuth credentials |

### Required Environment Variables

```env
# Application
APP_NAME=SwiftPOS
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

# OAuth (optional)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=

# InstaPay
INSTAPAY_PHONE=01012345678
INSTAPAY_ACCOUNT_NAME=SwiftPOS Store
INSTAPAY_ORDER_EXPIRATION_MINUTES=30
INSTAPAY_DEEPLINK=instapay://transfer
```

---

## 20. Testing

### Test Suite

Located in `tests/`:

| Test File | Coverage |
|-----------|----------|
| `Feature/SwiftPos/PosCheckoutTest.php` | POS checkout flow, payment calculation |
| `Feature/SwiftPos/PosSessionTest.php` | Session open/close, concurrent session prevention |
| `Feature/SwiftPos/InventoryAdjustmentTest.php` | Stock adjustment, insufficient stock errors |
| `Feature/SwiftPos/RoleAccessTest.php` | Role-based route access control |
| `Feature/Auth/AuthenticationTest.php` | Login/logout flow |
| `Feature/Auth/RegistrationTest.php` | User registration |
| `Feature/Auth/SocialAuthTest.php` | OAuth flow testing |
| `Feature/Auth/PasswordResetTest.php` | Password reset flow |
| `Feature/Auth/PasswordUpdateTest.php` | Password change |
| `Feature/Auth/PasswordConfirmationTest.php` | Secure actions |
| `Feature/Auth/EmailVerificationTest.php` | Email verification |
| `Feature/ProfileTest.php` | Profile CRUD |

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=PosCheckoutTest

# Run with coverage
php artisan test --coverage
```

---

*This documentation was generated as part of a comprehensive project audit. For the latest code, always refer to the source files directly.*
