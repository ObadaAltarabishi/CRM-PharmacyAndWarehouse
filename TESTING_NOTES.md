# TESTING_NOTES.md

## General Testing Approach
- Prioritize feature tests for role authorization, stock mutation, and lifecycle flows.
- For high-risk changes, test both success and failure/rollback paths.
- Prefer small, focused tests around one behavior per scenario.

## Auth And Role Testing Notes
- Verify login/auth per actor (`admin`, `pharmacy`, `warehouse`).
- Verify Sanctum ability protection on each role route group.
- Verify cross-role access is denied (e.g., pharmacy token on warehouse-only route).
- Verify pharmacy and warehouse login sends OTP but does not create a token before OTP verification.
- Verify valid OTP creates a token with the correct Sanctum ability.
- Verify OTP verification requires only `otp` and resolves the actor by active OTP for the matching role.
- Verify expired OTP, invalid OTP, and 5 invalid attempts block token creation.
- Verify resend requires `otp_request_token` from the login response and enforces the 30-second cooldown.
- Verify pharmacy/warehouse profile endpoints return the current actor and never expose password fields.
- Verify admin/pharmacy/warehouse password change requires correct current password and matching confirmation.

## Example Route Patterns (Placeholders)
- `POST /api/admin/login`
- `POST /api/pharmacy/login`
- `POST /api/warehouse/login`
- `GET|POST|PUT|DELETE /api/admin/...`
- `GET|POST|PUT|DELETE /api/pharmacy/...`
- `GET|POST|PUT|DELETE /api/warehouse/...`
- TODO/verify: replace with exact high-priority routes from `routes/api.php` during test authoring.

## Common Things To Verify
- Validation failures return consistent JSON errors.
- Actor can only access own scope/data.
- Totals and quantities are correct after create/update/delete operations.
- Idempotency expectations for repeated actions (where applicable).
- Pharmacy creation stores optional `latitude` and `longitude` when provided by the frontend.
- Guest public product search returns product identity fields only and does not require authentication.
- Guest product availability returns only pharmacies with positive stock and sorts by `distance_km` when user coordinates are provided.

## Demo Seed Data Checks
- `php artisan migrate:fresh --seed` completes with products imported from openFDA.
- Seeded users use realistic names, not placeholder `Demo...` labels.
- All 25 pharmacies have orders, expense invoices, and sales invoices.
- All 25 warehouses have incoming orders and expense invoices.
- Ratings are attached only to warehouses that have received pharmacy orders.
- Feedback records cover both pharmacy and warehouse operational cases.

## Warehouse Rating Checks
- Pharmacy can rate a warehouse only after a `received` order from that warehouse.
- Re-rating the same warehouse updates the existing pharmacy/warehouse rating instead of creating a duplicate.
- Pharmacy warehouse listing returns `ratings_count`, `rating_average`, and `my_rating`.
- Cross-role access is denied for the pharmacy-only rating route.

## Inventory / Stock Validation Checks
- Stock cannot go negative.
- Insufficient stock fails with clear error and no partial writes.
- Concurrent-like updates do not produce inconsistent quantities (TODO/verify with targeted tests).
- Stock mutations that span multiple writes are wrapped in transactions.

## Sales Checkout And Order Workflow Checks
- Sales cart item add accepts either `barcode` or `product_id`, but not both in the same request.
- Sales cart item add by `product_id` follows the same pharmacy stock checks as barcode scanning.
- Sales cart checkout creates invoice and updates stock atomically.
- Discount `>= 20%` requires explicit confirmation.
- Confirmed high-discount checkout uses expected pending values.
- Order flow transitions are valid: cart -> submitted -> approved/rejected -> received/issue handling.
- Rejected/invalid transitions are blocked with clear responses.

## Order Assistant Checks
- Products with quantity `0` are suggested with quantity `10`.
- Products with quantity below `5` are suggested with quantity `5`.
- Warehouses missing any suggested product or quantity are excluded.
- Cheapest complete warehouse is selected by total invoice cost.
- If no complete warehouse exists, best partial proposal is selected by covered item count, then total cost, and missing items are returned.
- Applying a proposal writes items into `OrderCart` and keeps checkout in the existing order-cart flow.
- Applying validates warehouse stock again and returns clear errors for missing/insufficient products.

## Known Bug Patterns / Debugging Notes
- Partial updates when transaction boundaries are missing.
- Status transitions not guarded tightly enough.
- Totals drift due to stale cart values or rounding mismatch.
- TODO/verify: capture concrete historical bugs as they appear.

## Sample Request/Response Placeholders
- Keep realistic examples here once verified from live routes/tests.

```json
{
  "todo": "add sample request payload for pharmacy sales checkout"
}
```

```json
{
  "todo": "add sample response payload for high-discount confirmation required"
}
```

## Quick Test Commands
```bash
php artisan test
composer test
```

```powershell
php artisan test --filter=Sales
php artisan test --filter=Order
```
