# API_MAP.md

## Purpose
- Fast endpoint map for new sessions and AI agents.
- Source snapshot from `routes/api.php`.
- Keep this file short and update when routes change.

## Auth (Public)
- `POST /api/admin/register`
- `POST /api/admin/login`
- `POST /api/pharmacy/login`
- `POST /api/warehouse/login`

## Shared (Authenticated)
- Middleware: `auth:sanctum`
- `GET /api/products/barcode/{barcode}`

## Admin Area
- Middleware: `auth:sanctum` + `abilities:admin`

### Profile / Admin Management
- `GET /api/admin/me`
- `GET /api/admins`
- `GET /api/admins/count`
- `DELETE /api/admins/{admin}`
- `PATCH /api/admins/{admin}/make-super-admin`

### Pharmacies Management
- `GET /api/pharmacies`
- `GET /api/pharmacies/count`
- `POST /api/pharmacies`
- `DELETE /api/pharmacies/{pharmacy}`
- `GET /api/regions/pharmacies-count`

### Warehouses Management
- `GET /api/warehouses`
- `GET /api/warehouses/count`
- `POST /api/warehouses`
- `DELETE /api/warehouses/{warehouse}`
- `GET /api/regions/warehouses-count`

### Reporting / Feedback
- `GET /api/regions/admins-count`
- `GET /api/admins/pharmacies-count`
- `GET /api/admins/warehouses-count`
- `GET /api/feedbacks`
- `GET /api/feedbacks/{feedback}`

## Pharmacy Area
- Middleware: `auth:sanctum` + `abilities:pharmacy`

### Feedback / Reference
- `POST /api/pharmacy/feedback`
- `GET /api/pharmacy/warehouses`
- `POST /api/products` (create product)

### Inventory
- `GET /api/pharmacy/products`
- `POST /api/pharmacy/products`
- `DELETE /api/pharmacy/products/{barcode}`

### Order Cart
- `GET /api/pharmacy/order-cart`
- `POST /api/pharmacy/order-cart/items`
- `PATCH /api/pharmacy/order-cart/items/{barcode}`
- `DELETE /api/pharmacy/order-cart/items/{barcode}`
- `DELETE /api/pharmacy/order-cart`
- `POST /api/pharmacy/order-cart/checkout`

### Orders
- `GET /api/pharmacy/orders`
- `POST /api/pharmacy/orders`
- `POST /api/pharmacy/orders/{order}/receive`
- `POST /api/pharmacy/orders/{order}/issue`

### Sales Cart (High Risk)
- `GET /api/pharmacy/sales-cart`
- `POST /api/pharmacy/sales-cart/items`
- `PATCH /api/pharmacy/sales-cart/items/{barcode}`
- `DELETE /api/pharmacy/sales-cart/items/{barcode}`
- `DELETE /api/pharmacy/sales-cart`
- `POST /api/pharmacy/sales-cart/checkout`
- `POST /api/pharmacy/sales-cart/checkout/confirm`

### Sales Invoices
- `POST /api/pharmacy/sales`
- `GET /api/pharmacy/sales-invoices`
- `GET /api/pharmacy/sales-invoices/with-feedback`
- `GET /api/pharmacy/sales-invoices/with-feedback/{salesInvoice}`
- `GET /api/pharmacy/sales-invoices/{salesInvoice}`
- `PATCH /api/pharmacy/sales-invoices/{salesInvoice}`
- `PATCH /api/pharmacy/sales-invoices/{salesInvoice}/paid-total`
- `DELETE /api/pharmacy/sales-invoices/{salesInvoice}/feedback`

### Expense Invoices
- `GET /api/pharmacy/expense-invoices`
- `GET /api/pharmacy/expense-invoices/{expenseInvoice}`
- `POST /api/pharmacy/expense-invoices`
- `PUT /api/pharmacy/expense-invoices/{expenseInvoice}`
- `DELETE /api/pharmacy/expense-invoices/{expenseInvoice}`

### Stats
- `GET /api/pharmacy/stats/summary`

## Warehouse Area
- Middleware: `auth:sanctum` + `abilities:warehouse`

### Feedback / Reference
- `POST /api/warehouse/feedback`
- `POST /api/products` (create product)

### Inventory
- `GET /api/warehouse/products`
- `POST /api/warehouse/products`
- `DELETE /api/warehouse/products/{barcode}`

### Orders (High Risk)
- `GET /api/warehouse/orders`
- `GET /api/warehouse/orders/pending`
- `GET /api/warehouse/orders/issues`
- `GET /api/warehouse/orders/{order}`
- `POST /api/warehouse/orders/{order}/approve`
- `POST /api/warehouse/orders/{order}/reject`

### Expense Invoices
- `GET /api/warehouse/expense-invoices`
- `GET /api/warehouse/expense-invoices/{expenseInvoice}`
- `POST /api/warehouse/expense-invoices`
- `PUT /api/warehouse/expense-invoices/{expenseInvoice}`
- `DELETE /api/warehouse/expense-invoices/{expenseInvoice}`

### Stats
- `GET /api/warehouse/stats/summary`

## Other Route
- `GET /api/warehouses/{warehouseId}/products`
- TODO/verify: access level intended (currently outside auth/ability groups).

## High-Risk Route Groups To Double-Check Before Edits
- Pharmacy sales cart checkout + confirm.
- Warehouse order approve/reject.
- Pharmacy order receive/issue.
- Any route that mutates stock and invoice/order totals together.

## Quick Update Checklist
- Route added/removed in `routes/api.php`.
- Middleware/ability changed.
- New high-risk mutation flow added.
- Reflect change in `API_PROGRESS.md` if feature status changed.