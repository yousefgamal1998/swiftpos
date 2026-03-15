# SwiftPOS

SwiftPOS is an advanced full-stack Point of Sale system for restaurants and retail shops, built with Laravel + Inertia + Vue.

## Stack
- Laravel 12
- PHP 8.3+ target (implemented and validated locally on PHP 8.2)
- MySQL 8.4 target
- Redis 7 target (cache/session/queue)
- Vue 3 + Inertia.js + Tailwind CSS + Vite

## Implemented Modules
- Authentication (Laravel Breeze, Inertia Vue, TypeScript)
- Modern default login landing at `/` with:
  - email/password authentication
  - Google OAuth login
  - Facebook OAuth login
  - social account persistence fields on `users`: `provider`, `google_id`, `avatar`
- Role-based access control (Spatie Permission): `admin`, `manager`, `cashier`
- Product management (CRUD, pricing, taxes, stock flags)
- Inventory management (stock adjustments + movement ledger)
- Order management (list/details, filters, payment status)
- Payment tracking per order
- POS cashier interface:
  - open/close cashier session
  - product search + cart
  - tax/discount calculation
  - checkout with payment method and change
  - stock deduction on sale
- Dashboard KPIs (daily/monthly sales, open sessions, low-stock alerts)

## Core Architecture
- Service layer:
  - `app/Services/OrderService.php` handles checkout transaction logic
  - `app/Services/InventoryService.php` handles atomic stock updates + movement logs
- Cache versioning for POS product lists:
  - `app/Support/PosCache.php`
- Role middleware:
  - `app/Http/Middleware/EnsureRole.php`
- Inertia shared auth/flash state:
  - `app/Http/Middleware/HandleInertiaRequests.php`
- Detailed architecture notes:
  - `docs/ARCHITECTURE.md`

## Main Routes
- `GET /` (default login page)
- `GET /auth/{provider}/redirect` and `GET /auth/{provider}/callback` for `google` / `facebook`
- `GET /dashboard`
- `GET /products`, `GET /products/create`, `GET /products/{product}/edit`
- `GET /inventory`, `POST /inventory/{product}/adjust`
- `GET /orders`, `GET /orders/{order}`
- `GET /pos`, `POST /pos/sessions/open`, `POST /pos/sessions/{session}/close`, `POST /pos/checkout`

## Setup
1. Install dependencies:
   - `composer install`
   - `npm install`
2. Configure environment:
   - `cp .env.example .env` (Windows: `copy .env.example .env`)
   - `php artisan key:generate`
   - set MySQL + Redis credentials in `.env`
   - set OAuth credentials in `.env`:
     - `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`
     - `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET`, `FACEBOOK_REDIRECT_URI`
3. Migrate and seed:
   - `php artisan migrate --seed`
4. Run app:
   - `php artisan serve`
   - `npm run dev`

## Demo Accounts
Seeded via `DatabaseSeeder`:
- `admin@swiftpos.test` / `password`
- `manager@swiftpos.test` / `password`
- `cashier@swiftpos.test` / `password`

## Testing and Quality
- Automated tests: `php artisan test`
- Code style: `vendor/bin/pint`
- Frontend build check: `npm run build`

The current implementation passes all feature/unit tests (`31 passed`) and production frontend build.
