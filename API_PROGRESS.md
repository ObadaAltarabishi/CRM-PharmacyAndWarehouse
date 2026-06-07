# API_PROGRESS.md

## How To Use
- Update this file at the end of each work session.
- Keep entries short and factual.
- If uncertain, mark as `TODO/verify`.

## Completed
- Admin authentication and registration.
- Pharmacy login.
- Warehouse login.
- Admin management and reporting endpoints.
- Pharmacy inventory endpoints.
- Warehouse inventory endpoints.
- Pharmacy order cart and order flow.
- Warehouse order review, approval, and rejection.
- Pharmacy sales cart and checkout flow.
- Sales invoice endpoints.
- Expense invoice endpoints (pharmacy and warehouse).
- Feedback endpoints.
- Warehouse rating endpoint for pharmacies.
- Stats/summary endpoints.

## In Progress
- TODO/verify: strengthen test coverage for stock-sensitive edge cases.
- TODO/verify: confirm consistency of order lifecycle status transitions across all routes.

## Planned Next
- Add/expand tests for checkout rollback and concurrency-sensitive stock updates.
- Verify role middleware coverage endpoint-by-endpoint.
- Review response consistency for error payloads in cart/checkout/order paths.

## Endpoints / Modules To Verify
- Warehouse rating eligibility requires a received pharmacy order from the warehouse.
- Pharmacy sales checkout confirmation path (`>= 20%` discount).
- Stock decrementation safety across sales finalization.
- Order approval/rejection and subsequent pharmacy-side state handling.
- Any endpoint mutating stock without explicit transaction.

## Session Update Template
- **Date**: YYYY-MM-DD
- **Worked On**:
- **Files Changed**:
- **Behavior Changes**:
- **Tests Run**:
- **Open Risks**:
- **Next Step**:

## Notes
- Keep this list outcome-focused; detailed rationale belongs in `DECISIONS.md`.

## Session Updates
- **Date**: 2026-06-07
- **Worked On**: Warehouse ratings for pharmacies.
- **Files Changed**: `routes/api.php`, warehouse/pharmacy rating models/controllers/requests/migration, API docs.
- **Behavior Changes**: Pharmacies can rate a warehouse once after receiving an order from it; repeated rating updates the same record. Pharmacy warehouse listing now includes rating count, average, and current pharmacy rating.
- **Tests Run**: `php artisan test` (2 passed).
- **Open Risks**: Add feature tests for rating eligibility and update behavior.
- **Next Step**: Implement the next planned feature after confirming requirements.
- **Date**: 2026-06-07
- **Worked On**: Fixed migration freshness for duplicate `feedback` column.
- **Files Changed**: `database/migrations/2026_02_14_000019_add_feedback_to_sales_invoices.php`.
- **Behavior Changes**: `php artisan migrate:fresh` now completes with the current migration set.
- **Tests Run**: `php artisan migrate:fresh`; `php artisan test` (2 passed).
- **Open Risks**: `2026_02_14_000018_add_created_by_name_to_expense_invoices.php` is currently absent; this is fine for fresh development because `created_by_name` exists in the expense invoice create migration.
- **Next Step**: Continue with the next planned feature.
- **Date**: 2026-06-07
- **Worked On**: Improved openFDA product seeding reliability.
- **Files Changed**: `database/seeders/ProductsSeeder.php`, `config/services.php`, `.env.example`.
- **Behavior Changes**: `ProductsSeeder` still imports only from openFDA, now with timeout/retry handling, a short pause between requests, and optional `OPENFDA_API_KEY` support.
- **Tests Run**: `php artisan migrate:fresh --seed` (seeded 11 regions and 100 products); `php artisan test` (2 passed); `php -l database/seeders/ProductsSeeder.php`; `php -l config/services.php`.
- **Open Risks**: openFDA can still rate-limit or return service errors; using `OPENFDA_API_KEY` is recommended if this becomes frequent.
- **Next Step**: Continue with the next planned feature.
