# 🔌 Vmarket Endpoints Summary

## REST API Version 1 (`/api/v1/`)

### Customer Endpoints:
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/register`
- `GET /api/v1/products/latest`
- `GET /api/v1/categories`
- `POST /api/v1/cart/add`
- `POST /api/v1/digital-payment` — Paystack payment initiation (prepaid delivery orders)
- `GET /api/v1/customer/order/cancel-order`
- `GET /api/v1/cashback` — Cashback ledger (non-withdrawable account credit; matures after 24-hour return window)

### Vendor Endpoints:
- `GET /api/v3/seller/banks`
- `POST /api/v3/seller/resolve-account`
- `POST /api/v3/seller/bank-info/send-otp`
- `PUT /api/v3/seller/seller-update` — Update bank info (with 48-hour withdrawal cooldown)
- `POST /api/v3/seller/kyc/submit`
- `POST /api/v3/seller/balance-withdraw`
- `POST /api/v3/seller/orders/verify-pickup-otp` — In-shop pickup handover OTP verification

### Delivery Man Endpoints:
- `POST /api/v1/delivery-man/order/verify-pickup-otp` — Vendor pickup OTP (rider collects from vendor)
- `POST /api/v2/delivery-man/order/verify-order-delivery-otp` — Customer delivery OTP (completes doorstep delivery)

> **V1 Decommissioned Endpoints (do not use):**
> - ~~`POST /api/v1/customer/order/place`~~ — COD/offline placement removed. Use `/api/v1/digital-payment`.
> - ~~`POST /api/v1/delivery-man/order/generate-paystack-link`~~ — V1 riders do not collect cash. Removed.
> - ~~`/api/v1/customer/wallet/*`~~ — Customer stored-value wallet decommissioned. See `/api/v1/cashback`.
> - ~~`/api/v2/delivery-man/remit-cash-paystack-init`~~ — Rider cash remittance decommissioned in V1.
