# PROJECT_CONTEXT.md

## Project Summary
- This repository is a backend API for pharmacy and warehouse management.
- It supports three roles (`admin`, `pharmacy`, `warehouse`) with role-based JSON endpoints.
- The API is intended to be the operational backbone for inventory, orders, sales, expenses, and reporting.

## Business Goal
- Keep pharmacy and warehouse operations consistent and auditable.
- Track stock movement accurately across sales and order lifecycles.
- Support clear role separation and actionable operational reporting.

## Current Backend Architecture
- Laravel 12, controller-centric endpoint implementation.
- Sanctum token auth with abilities (`admin`, `pharmacy`, `warehouse`).
- MySQL persistence with Eloquent models and migrations.
- Validation primarily through `FormRequest` classes (preferred for new work).

## Main Entities / Models
- `Product`: shared catalog; external lookup by `barcode`.
- `PharmacyProduct`: pharmacy-specific stock + sell pricing.
- `WarehouseProduct`: warehouse-specific stock + cost data.
- `OrderCart` (+ items): temporary pharmacy order cart.
- `Order` (+ items): submitted pharmacy order to warehouse.
- `SalesCart` (+ items): temporary pharmacy sales cart.
- `SalesInvoice` (+ items): finalized pharmacy sale.
- `ExpenseInvoice`: expense tracking for pharmacy/warehouse.
- `Feedback`: issue/feedback records.

## Main API Role Areas
- Admin: account/admin/pharmacy/warehouse management, counts, reports.
- Pharmacy: inventory, order cart/orders, sales cart/checkout, invoices, expenses, feedback, summary.
- Warehouse: inventory, incoming order review, approval/rejection, expenses, feedback, summary.

## Completed Modules (Known)
- Admin authentication and registration.
- Pharmacy login.
- Warehouse login.
- Admin management and reporting endpoints.
- Pharmacy inventory.
- Warehouse inventory.
- Pharmacy order cart and order flow.
- Warehouse order review, approval, and rejection.
- Pharmacy sales cart and checkout flow.
- Sales invoices.
- Expense invoices (pharmacy and warehouse).
- Feedback endpoints.
- Stats/summary endpoints.

## Current Areas Needing Caution
- Stock-sensitive flows (especially concurrent updates).
- Pharmacy sales checkout (stock + totals + confirmation paths).
- Order lifecycle consistency across pharmacy/warehouse actions.

## Next Likely Improvements
- Expand automated tests for stock and lifecycle edge cases.
- Standardize response shapes and error consistency where needed.
- Incrementally improve controller readability without large refactors.
- Document key endpoint behaviors and constraints more explicitly.

## Important Constraints / Quirks
- Keep changes minimal and consistent with controller-centric architecture.
- Prefer `FormRequest` validation for new endpoints.
- Discount rule: pharmacy sales checkout discounts `>= 20%` require confirmation.
- Use transactions for flows that update stock and invoices together.
- TODO/verify: identify any schema naming quirks and legacy inconsistencies before major edits.