# 🔌 Vmarket Endpoints Summary

## REST API Version 1 (`/api/v1/`)

### Customer Endpoints:
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/register`
- `GET /api/v1/products/latest`
- `GET /api/v1/categories`
- `POST /api/v1/cart/add`
- `POST /api/v1/customer/order/place`

### Vendor Endpoints:
- `GET /api/v1/seller/banks`
- `POST /api/v1/seller/resolve-account`
- `POST /api/v1/seller/bank-info/send-otp`
- `POST /api/v1/seller/bank-info/update`
- `POST /api/v1/seller/kyc/submit`
- `POST /api/v1/seller/withdraw/request`

### Delivery Man Endpoints:
- `POST /api/v1/delivery-man/order/verify-pickup-otp`
- `POST /api/v1/delivery-man/order/verify-delivery-otp`
- `POST /api/v1/delivery-man/order/generate-paystack-link`
