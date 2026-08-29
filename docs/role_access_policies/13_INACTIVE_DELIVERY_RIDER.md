# Role Security & Endpoint Access Policy: Inactive / Pending Deliveryman

> **Role Scope:** `Inactive / Pending Deliveryman`  
> **Total Allowed Endpoints:** `5`  
> **Total Disallowed / Blocked Endpoints:** `1578`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Logistics applicant awaiting KYC review or suspended courier.

### Core Authorized Capabilities:
- ✅ **Authentication Handshake and KYC Status Inspection**

### Strict Architectural Restrictions:
- ⛔ **Strictly BLOCKED from accepting delivery orders, collecting COD cash, or viewing customer addresses**

---

## 2. Authorized Endpoints Access Matrix (5 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/api/v1/delivery-hubs/states` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getStates` |
| 2 | `GET` | `/api/v1/delivery-hubs/cities/{state_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getCities` |
| 3 | `GET` | `/api/v1/delivery-hubs/hubs/{city_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getHubs` |
| 4 | `POST` | `/api/v1/delivery-hubs/calculate-shipping` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@calculateHubShipping` |
| 5 | `POST` | `/api/v2/delivery-man/auth/login` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\auth\LoginController@login` |

---

## 3. Disallowed & Gated Endpoints Summary (1578 Endpoints Blocked)

Attempting to access any of the 1578 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

### Sample Gated Endpoints for this Role:

| # | Method | Gated URI | Guard Interceptor | Reason for Gating |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/_debugbar/open` | `auth / rbac` | Strictly isolated outside role boundary |
| 2 | `GET` | `/_debugbar/clockwork/{id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 3 | `GET` | `/_debugbar/assets/stylesheets` | `auth / rbac` | Strictly isolated outside role boundary |
| 4 | `GET` | `/_debugbar/assets/javascript` | `auth / rbac` | Strictly isolated outside role boundary |
| 5 | `DELETE` | `/_debugbar/cache/{key}/{tags?}` | `auth / rbac` | Strictly isolated outside role boundary |
| 6 | `POST` | `/_debugbar/queries/explain` | `auth / rbac` | Strictly isolated outside role boundary |
| 7 | `POST` | `/oauth/token` | `auth / rbac` | Strictly isolated outside role boundary |
| 8 | `GET` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 9 | `POST` | `/oauth/token/refresh` | `auth / rbac` | Strictly isolated outside role boundary |
| 10 | `POST` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 11 | `DELETE` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 12 | `GET` | `/oauth/tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 13 | `DELETE` | `/oauth/tokens/{token_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 14 | `GET` | `/oauth/clients` | `auth / rbac` | Strictly isolated outside role boundary |
| 15 | `POST` | `/oauth/clients` | `auth / rbac` | Strictly isolated outside role boundary |
| 16 | `PUT` | `/oauth/clients/{client_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 17 | `DELETE` | `/oauth/clients/{client_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 18 | `GET` | `/oauth/scopes` | `auth / rbac` | Strictly isolated outside role boundary |
| 19 | `GET` | `/oauth/personal-access-tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 20 | `POST` | `/oauth/personal-access-tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 21 | `DELETE` | `/oauth/personal-access-tokens/{token_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 22 | `GET` | `/sanctum/csrf-cookie` | `auth / rbac` | Strictly isolated outside role boundary |
| 23 | `GET` | `/_ignition/health-check` | `auth / rbac` | Strictly isolated outside role boundary |
| 24 | `POST` | `/_ignition/execute-solution` | `auth / rbac` | Strictly isolated outside role boundary |
| 25 | `POST` | `/_ignition/update-config` | `auth / rbac` | Strictly isolated outside role boundary |


