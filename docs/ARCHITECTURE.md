# SwiftPOS Architecture

## Layers
- `app/Http/Controllers`: HTTP endpoints and Inertia page orchestration.
- `app/Http/Requests`: request validation and authorization.
- `app/Services`: business workflows (`OrderService`, `InventoryService`).
- `app/Models`: domain entities and relationships.
- `database/migrations`: schema for RBAC, products, inventory, orders, payments, POS sessions.
- `resources/js/Pages`: Inertia Vue screens (Dashboard, Products, Inventory, Orders, POS).

## Authentication Entry
- `/` renders the Inertia login page (`Auth/Login.vue`) as the default landing route.
- Login supports:
  - local email/password (`AuthenticatedSessionController`)
  - OAuth providers (`SocialAuthController`) via Laravel Socialite for Google and Facebook.

## Checkout Flow
1. Cashier opens a POS session.
2. Frontend sends checkout payload (`pos_session_id`, `items`, payment data).
3. `StoreOrderRequest` validates payload.
4. `OrderService::checkout()` starts a DB transaction.
5. Products are locked (`lockForUpdate`) for stock-safe updates.
6. Order totals/taxes/discounts are calculated.
7. Inventory is deducted through `InventoryService::adjustStock()`.
8. Order, items, and payment rows are persisted.
9. Transaction commits and POS cache version is bumped.

## Inventory Consistency
- Inventory operations use row-level locking on `products`.
- Every stock change writes an `inventory_movements` audit row.
- Negative stock for tracked products is rejected with validation errors.

## Role Access
- Route middleware alias: `role`.
- Roles:
  - `admin`: full access.
  - `manager`: products, inventory, orders, POS.
  - `cashier`: POS and orders.
