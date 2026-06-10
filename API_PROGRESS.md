# API_PROGRESS.md

## How To Use
- Update this file at the end of each work session.
- Keep entries short and factual.
- If uncertain, mark as `TODO/verify`.

## Completed
- Admin authentication and registration.
- Pharmacy login.
- Pharmacy OTP login verification.
- Pharmacy profile endpoint.
- Pharmacy change password endpoint.
- Warehouse login.
- Warehouse OTP login verification.
- Warehouse profile endpoint.
- Warehouse change password endpoint.
- Admin management and reporting endpoints.
- Pharmacy inventory endpoints.
- Warehouse inventory endpoints.
- Pharmacy order cart and order flow.
- Pharmacy order assistant proposal/apply flow.
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
- Order assistant proposal and apply-to-cart behavior.
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
- **Date**: 2026-06-10
- **Worked On**: Single warehouse rating summary endpoint.
- **Files Changed**: `WarehouseRatingController`, `routes/api.php`, API docs.
- **Behavior Changes**: Added `GET /api/pharmacy/warehouses/{warehouse}/rating` to return one warehouse rating average and rating count by ID.
- **Tests Run**: `php -l app/Http/Controllers/WarehouseRatingController.php`; `php artisan route:list --path=pharmacy/warehouses`; `php artisan test` (2 passed).
- **Open Risks**: Add feature test for unrated warehouse returning count `0` and average `null`.
- **Next Step**: Run route checks/tests and provide Postman example.
- **Date**: 2026-06-10
- **Worked On**: Demo database seeding.
- **Files Changed**: `ProductsSeeder`, `DemoDataSeeder`, `DatabaseSeeder`, API docs.
- **Behavior Changes**: Product seeding now targets 150 openFDA products and demo seeding creates admins, pharmacies, warehouses, inventories, orders, ratings, expenses, sales invoices, and feedback.
- **Tests Run**: `php -l` on seeders; `php artisan migrate:fresh --seed`; seeded 25 admins, 25 pharmacies, 25 warehouses, 150 products, 330 orders, 80 ratings, 750 expenses, 375 sales invoices, 25 feedbacks; `php artisan test` (2 passed).
- **Open Risks**: Full seed depends on openFDA availability and enough returned products.
- **Next Step**: Run `migrate:fresh --seed` and verify record counts.
- **Date**: 2026-06-09
- **Worked On**: Change password endpoints for admin, pharmacy, and warehouse.
- **Files Changed**: `routes/api.php`, `PasswordController`, `ChangePasswordRequest`, API docs.
- **Behavior Changes**: Authenticated actors can change password using current password, new password, and confirmation. Incorrect current password or mismatched confirmation blocks the change.
- **Tests Run**: `php -l` on password files; `php artisan route:list --path=password`; `php artisan test` (2 passed).
- **Open Risks**: Add feature tests for old password rejection and confirmation validation.
- **Next Step**: Run route checks/tests and provide Postman examples.
- **Date**: 2026-06-07
- **Worked On**: Fixed migration freshness for duplicate `feedback` column.
- **Files Changed**: `database/migrations/2026_02_14_000019_add_feedback_to_sales_invoices.php`.
- **Behavior Changes**: `php artisan migrate:fresh` now completes with the current migration set.
- **Tests Run**: `php artisan migrate:fresh`; `php artisan test` (2 passed).
- **Open Risks**: `2026_02_14_000018_add_created_by_name_to_expense_invoices.php` is currently absent; this is fine for fresh development because `created_by_name` exists in the expense invoice create migration.
- **Next Step**: Continue with the next planned feature.
- **Date**: 2026-06-09
- **Worked On**: Pharmacy and warehouse profile endpoints.
- **Files Changed**: `routes/api.php`, `PharmacyController`, `WarehouseController`, API docs.
- **Behavior Changes**: Added authenticated `GET /api/pharmacy/me` and `GET /api/warehouse/me` endpoints returning actor profile data with region/admin relations and hidden password fields.
- **Tests Run**: `php -l` on profile controllers; `php artisan route:list --path=me`; `php artisan test` (2 passed).
- **Open Risks**: Add authorization tests for cross-role access.
- **Next Step**: Run route checks/tests and provide Postman examples.
- **Date**: 2026-06-09
- **Worked On**: Pharmacy order assistant.
- **Files Changed**: `routes/api.php`, `PharmacyOrderAssistantController`, `ApplyOrderAssistantProposalRequest`, API docs.
- **Behavior Changes**: Pharmacies can generate a suggested order from low/out-of-stock inventory, choose the cheapest single warehouse that can fulfill all suggested items, and apply the proposal to the existing order cart flow.
- **Tests Run**: `php -l` on order assistant files; `php artisan route:list --path=order-assistant`; `php artisan test` (2 passed).
- **Open Risks**: Needs feature tests for no-needed-products, no-single-warehouse, cheapest warehouse, and apply stock validation.
- **Next Step**: Run syntax checks/routes/tests and provide Postman examples.
- **Date**: 2026-06-09
- **Worked On**: Added partial fallback to pharmacy order assistant.
- **Files Changed**: `PharmacyOrderAssistantController`, API docs.
- **Behavior Changes**: If no single warehouse can fulfill all suggested items, the assistant now returns the best partial proposal from one warehouse plus `missing_items` instead of failing.
- **Tests Run**: `php -l app/Http/Controllers/PharmacyOrderAssistantController.php`; `php artisan test` (2 passed).
- **Open Risks**: Needs feature tests for partial proposal ranking by covered item count then total cost.
- **Next Step**: Run syntax checks/tests and provide updated response examples.
- **Date**: 2026-06-09
- **Worked On**: Fixed warehouse order issues route ordering.
- **Files Changed**: `routes/api.php`.
- **Behavior Changes**: `GET /api/warehouse/orders/issues` now resolves to `WarehouseOrderController@issues` instead of being captured by `/warehouse/orders/{order}` model binding.
- **Tests Run**: `php artisan route:list --path=warehouse/orders`; `php artisan test` (2 passed).
- **Open Risks**: Keep static warehouse order routes before dynamic `{order}` routes.
- **Next Step**: Re-test route list and continue order assistant work.
- **Date**: 2026-06-09
- **Worked On**: OTP login flow for pharmacies and warehouses.
- **Files Changed**: `routes/api.php`, pharmacy/warehouse auth controllers, OTP model/migration/mail/support/request files, `.env.example`, API docs.
- **Behavior Changes**: Pharmacy and warehouse login now sends a 6-digit OTP by email and does not create a token until OTP verification succeeds. OTP expires after 5 minutes, invalidates after 5 wrong attempts, and resend is rate-limited to 30 seconds.
- **Tests Run**: `php -l` on OTP files; `php artisan route:list --path=login`; `php artisan migrate`; `php artisan test` (2 passed).
- **Open Risks**: Requires Gmail SMTP credentials in `.env`; add feature tests with `Mail::fake()`.
- **Next Step**: Verify migrations/routes/tests and provide Postman examples.
- **Date**: 2026-06-09
- **Worked On**: Fixed Gmail SMTP mailer scheme configuration.
- **Files Changed**: `config/mail.php`, `.env.example`.
- **Behavior Changes**: Gmail SMTP now uses a Symfony-supported `smtp` scheme; existing `MAIL_SCHEME=tls` values are normalized to `smtp` to avoid unsupported scheme errors.
- **Tests Run**: `php -l config/mail.php`; `php artisan config:clear`; `php artisan test` (2 passed).
- **Open Risks**: Run `php artisan config:clear` after changing `.env`.
- **Next Step**: Retry pharmacy/warehouse login OTP email.
- **Date**: 2026-06-09
- **Worked On**: Simplified OTP verification payload.
- **Files Changed**: `VerifyLoginOtpRequest`, pharmacy/warehouse auth controllers, `LoginOtpService`, API docs.
- **Behavior Changes**: Pharmacy and warehouse OTP verification now requires only the 6-digit `otp`; the actor is resolved from active OTP records for the matching role.
- **Tests Run**: `php -l` on OTP request/controller/service files; `php artisan test` (2 passed).
- **Open Risks**: Active OTP codes should be treated as login credentials during their 5-minute lifetime.
- **Next Step**: Run syntax checks/tests and retry Postman verify payload.
- **Date**: 2026-06-09
- **Worked On**: Simplified OTP resend payload.
- **Files Changed**: `login_otps` migration/model, OTP resend request, OTP service, pharmacy/warehouse auth controllers.
- **Behavior Changes**: Login returns `otp_request_token`; resend OTP now uses that token and no longer requires `login` or `password`.
- **Tests Run**: `php -l` on OTP resend files; `php artisan migrate`; `php artisan test` (2 passed).
- **Open Risks**: Frontend/Postman must keep `otp_request_token` from login response until OTP verification finishes.
- **Next Step**: Run migration/checks/tests and retry resend payload.
- **Date**: 2026-06-07
- **Worked On**: Improved openFDA product seeding reliability.
- **Files Changed**: `database/seeders/ProductsSeeder.php`, `config/services.php`, `.env.example`.
- **Behavior Changes**: `ProductsSeeder` still imports only from openFDA, now with timeout/retry handling, a short pause between requests, and optional `OPENFDA_API_KEY` support.
- **Tests Run**: `php artisan migrate:fresh --seed` (seeded 11 regions and 100 products); `php artisan test` (2 passed); `php -l database/seeders/ProductsSeeder.php`; `php -l config/services.php`.
- **Open Risks**: openFDA can still rate-limit or return service errors; using `OPENFDA_API_KEY` is recommended if this becomes frequent.
- **Next Step**: Continue with the next planned feature.
