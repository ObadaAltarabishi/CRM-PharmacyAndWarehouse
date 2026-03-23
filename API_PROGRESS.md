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
- Stats/summary endpoints.

## In Progress
- TODO/verify: strengthen test coverage for stock-sensitive edge cases.
- TODO/verify: confirm consistency of order lifecycle status transitions across all routes.

## Planned Next
- Add/expand tests for checkout rollback and concurrency-sensitive stock updates.
- Verify role middleware coverage endpoint-by-endpoint.
- Review response consistency for error payloads in cart/checkout/order paths.

## Endpoints / Modules To Verify
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