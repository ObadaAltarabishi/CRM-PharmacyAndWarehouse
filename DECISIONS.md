# DECISIONS.md

## Format
- **Decision**: what was decided
- **Status**: proposed | active | superseded
- **Reason**: why this decision exists
- **Notes**: implementation or follow-up notes

## Decision Log

### D-001 Role-based JSON API
- **Decision**: Structure backend endpoints by actor role (`admin`, `pharmacy`, `warehouse`) and return JSON-first responses.
- **Status**: active
- **Reason**: Matches business ownership boundaries and keeps client integration predictable.
- **Notes**: Keep role middleware and ability checks explicit.

### D-002 Sanctum abilities for authorization
- **Decision**: Use Sanctum tokens with abilities: `admin`, `pharmacy`, `warehouse`.
- **Status**: active
- **Reason**: Lightweight role enforcement for API access.
- **Notes**: New protected endpoints must align with ability middleware.

### D-003 Shared product catalog by barcode
- **Decision**: Keep `Product` as shared catalog entity identified externally by `barcode`.
- **Status**: active
- **Reason**: Ensures consistent product identity across pharmacy and warehouse contexts.
- **Notes**: Local stock/pricing remains actor-specific in related tables.

### D-004 Split actor-specific inventory models
- **Decision**: Keep pharmacy and warehouse stock/cost/sell data in separate models (`PharmacyProduct`, `WarehouseProduct`).
- **Status**: active
- **Reason**: Different operational semantics and pricing concerns per actor.
- **Notes**: Avoid collapsing these models without clear migration plan.

### D-005 Use temporary carts before final submission
- **Decision**: Use `OrderCart` and `SalesCart` as temporary state before creating final orders/invoices.
- **Status**: active
- **Reason**: Supports iterative item editing and explicit finalization steps.
- **Notes**: Cart cleanup/transition behavior should stay explicit.

### D-006 Sales discount confirmation threshold
- **Decision**: Require explicit confirmation when pharmacy sales checkout discount is `>= 20%`.
- **Status**: active
- **Reason**: Business safeguard against high-discount mistakes.
- **Notes**: Keep this rule visible in validation/checkout flow.

### D-007 Transactions for stock-sensitive operations
- **Decision**: Use database transactions for stock-sensitive and multi-table finalization flows.
- **Status**: active
- **Reason**: Prevent partial writes and inconsistent stock/totals.
- **Notes**: Especially important in checkout and order lifecycle actions.

### D-008 Controller-centric implementation preference
- **Decision**: Keep changes consistent with current controller-centric architecture unless refactor is explicitly scoped.
- **Status**: active
- **Reason**: Minimizes risk and preserves current project patterns.
- **Notes**: Refactor proposals should include migration and test strategy.

### D-009 Prefer FormRequest on new endpoints
- **Decision**: For new endpoints, prefer dedicated `FormRequest` classes for validation.
- **Status**: active
- **Reason**: Keeps validation reusable, testable, and consistent.
- **Notes**: Legacy inline validation can be migrated incrementally.

## Pending Decision Slots
- TODO: Decide if order/sales lifecycle state transitions should be centralized in services or remain in controllers.
- TODO: Decide minimum required test coverage gates for stock-critical merges.