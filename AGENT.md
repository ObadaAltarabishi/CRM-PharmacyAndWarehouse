# AGENT.md

## Project Overview
- Backend API for pharmacy and warehouse management.
- JSON-first, role-based API.
- Primary actors: `admin`, `pharmacy`, `warehouse`.

## Stack
- PHP 8.2+
- Laravel 12
- Laravel Sanctum (token abilities)
- MySQL
- Pest

## Main Domain Actors
- `admin`: manages admins, pharmacies, warehouses, counts, and reports.
- `pharmacy`: manages local inventory, order cart, orders, sales cart, sales invoices, expense invoices, and feedback.
- `warehouse`: manages warehouse inventory, pharmacy orders, approvals/rejections, warehouse expense invoices, and feedback.

## Architecture Rules
- Keep current controller-centric structure unless explicitly refactoring.
- Keep responses JSON-only and consistent.
- Keep role boundaries explicit in routes/middleware.
- Use Sanctum token abilities: `admin`, `pharmacy`, `warehouse`.
- Use `Product` as shared catalog entity, identified externally by `barcode`.
- Use `PharmacyProduct` for pharmacy-specific stock/sell price.
- Use `WarehouseProduct` for warehouse-specific stock/cost data.
- Treat `OrderCart` and `SalesCart` as temporary pre-submission carts.

## Coding Preferences
- Prefer minimal safe changes over broad rewrites.
- For new endpoints, prefer dedicated `FormRequest` validation.
- Use DB transactions for stock-sensitive multi-write flows.
- Validate stock and totals explicitly; fail clearly on invalid states.
- Keep naming/style consistent with nearby code.

## What To Avoid
- Do not add unnecessary frameworks/scripts/tooling.
- Do not move business rules into unrelated layers without clear need.
- Do not bypass role ability checks.
- Do not make risky schema changes casually in inventory/order/invoice areas.
- Do not claim feature completion without verification.

## Safe Change Workflow
1. Read related route, controller, request, and model first.
2. Identify stock, totals, and permission impact.
3. Implement smallest coherent change.
4. Add/update tests (especially for behavior changes).
5. Verify rollback/error paths for stock-sensitive flows.
6. Record status in `API_PROGRESS.md` and decisions in `DECISIONS.md` when needed.

## Response Style For Future Agents
- Keep explanations concise and practical.
- Start with what changed and why.
- Call out assumptions and TODO/verify items explicitly.
- Include file references when explaining non-trivial behavior.

## Current High-Risk / High-Focus Areas
- Sales checkout flow (stock mutation + invoice totals).
- Discount confirmation logic (`>= 20%` requires confirmation).
- Order lifecycle transitions (submit, approve/reject, receive/issue handling).
- Any stock-sensitive flow touching inventory quantities or reserved amounts.

## Quick Reality Snapshot (Verified/Expected)
- Completed: admin auth/registration, pharmacy login, warehouse login, admin management/reporting, pharmacy + warehouse inventory, pharmacy order cart/order flow, warehouse order review/approve/reject, pharmacy sales cart + checkout, sales invoices, expense invoices, feedback, stats/summary endpoints.
- TODO/verify: confirm edge-case behavior and test coverage for stock race conditions, checkout failure rollbacks, and order lifecycle consistency.