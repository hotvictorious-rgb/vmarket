# Victorious MARKET Ecosystem — Universal Demo Accounts & Credentials Directory

This authoritative document details all system actors, demo accounts, roles, access levels, and credentials across the entire Victorious MARKET and Vmarket POS ecosystem.

---

## 0. Prime Directive: Super Admin Single Source of Truth
* **Single Super Admin Invariant**: Exactly **ONE (1) Super Admin** exists per system, and its credentials are strictly managed through `.env`.
* **Dynamic Role Creation**: All other accounts (Employees, Vendors, Customers, Riders, Cashiers) are created dynamically via admin panel onboarding or self-service registration.

---

## 1. 👑 Super Admin Command Center (Platform Owners)
Configured exclusively in `.env` (`SUPER_ADMIN_*`).

| Portal | Target URL | Email | Password | Access Scope |
| :--- | :--- | :--- | :--- | :--- |
| 🛍️ **Victorious MARKET Web** | [http://127.0.0.1:8000/login/admin](http://127.0.0.1:8000/login/admin) | `admin@admin.com` | `12345678` | Full Unrestricted Super Admin Command Center |
| 💻 **Vmarket POS Web** | [http://127.0.0.1:8001/login](http://127.0.0.1:8001/login) | `admin@admin.com` | `12345678` | Multi-Store POS Configuration & Shift Auditing |

---

## 2. 💼 Administrative Employees & Internal Staff
Internal staff accounts managed under **Admin Panel $\rightarrow$ Employee Setup**.

| Role Name | Portal URL | Email | Password | Permissions & Modules |
| :--- | :--- | :--- | :--- | :--- |
| **Operations Manager** | [http://127.0.0.1:8000/login/admin](http://127.0.0.1:8000/login/admin) | `manager@victorious.com` | `12345678` | Orders, Products, Vendor Approvals, POS Sync |
| **Finance & Accounts** | [http://127.0.0.1:8000/login/admin](http://127.0.0.1:8000/login/admin) | `finance@victorious.com` | `12345678` | Vendor Withdrawals, Wallet Reconciliations, Reports |
| **Customer Support** | [http://127.0.0.1:8000/login/admin](http://127.0.0.1:8000/login/admin) | `support@victorious.com` | `12345678` | Helpdesk Tickets, Live Chat, Review Moderation |

---

## 3. 🏪 Multi-Vendor Merchants (Store Owners & Sellers)
Merchants manage their inventory, order fulfillment, and earnings via the Vendor Web Panel and Vendor Mobile App.

| Account Type | Shop Name | Portal URL | Email | Password | Details & Balances |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Verified Merchant** | *Victorious Flagship Store* | [http://127.0.0.1:8000/vendor/auth/login](http://127.0.0.1:8000/vendor/auth/login) | `vendor@victorious.com` | `12345678` | **Status:** Approved<br>**Wallet:** `₦150,000.00`<br>**Commission:** `5%` |
| **Electronics Vendor** | *Victorious Gadgets Hub* | [http://127.0.0.1:8000/vendor/auth/login](http://127.0.0.1:8000/vendor/auth/login) | `gadgets@victorious.com` | `12345678` | **Status:** Approved<br>**Wallet:** `₦85,000.00` |
| **Pending Vendor** | *New Horizon Store* | [http://127.0.0.1:8000/vendor/auth/login](http://127.0.0.1:8000/vendor/auth/login) | `pending@victorious.com` | `12345678` | **Status:** Pending Approval *(For testing approval flows)* |

---

## 4. 🛍️ Online Shoppers & Customers (Storefront & Mobile App)
Shoppers browse products, manage digital wallets, track shipments, and redeem loyalty points.

| Customer Persona | Login Portal | Email / Phone | Password | Starting Balances |
| :--- | :--- | :--- | :--- | :--- |
| **VIP Customer** | Web Storefront & Customer App | `customer@victorious.com`<br>*(Phone: `08012345678`)* | `12345678` | **Wallet:** `₦50,000.00`<br>**Loyalty:** `250 Pts`<br>**Orders:** 4 Completed |
| **Standard Shopper** | Web Storefront & Customer App | `shopper@victorious.com`<br>*(Phone: `08022223333`)* | `12345678` | **Wallet:** `₦10,000.00`<br>**Loyalty:** `50 Pts` |
| **Zero-Balance User** | Web Storefront & Customer App | `newuser@victorious.com`<br>*(Phone: `08044445555`)* | `12345678` | **Wallet:** `₦0.00`<br>*(For testing checkout payment gateway redirects)* |

---

## 5. 🛵 Logistics & Delivery Riders (Delivery Man App)
Riders receive order dispatch assignments, collect Cash on Delivery (COD), and manage in-hand cash.

| Rider Name | Assignment Scope | Portal / App | Email / Phone | Password | Wallets & Cash-in-Hand |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Emeka Rider** | In-House / Admin Fleet | Delivery Man Flutter App | `rider@victorious.com`<br>*(Phone: `08098765432`)* | `12345678` | **Cash-in-Hand:** `₦4,500.00`<br>**Earnings:** `₦12,500.00` |
| **Tunde Express** | Dedicated Vendor Fleet | Delivery Man Flutter App | `rider2@victorious.com`<br>*(Phone: `08088889999`)* | `12345678` | **Cash-in-Hand:** `₦0.00`<br>**Earnings:** `₦8,000.00` |

---

## 6. 💻 Vmarket POS In-Store Staff (Terminal Roles)
In-store cashier terminals and inventory stock clerks.

| Staff Role | Login Portal | Email | Password | Allowed POS Actions |
| :--- | :--- | :--- | :--- | :--- |
| **POS Super Admin** | [http://127.0.0.1:8001/login](http://127.0.0.1:8001/login) | `admin@admin.com` | `12345678` | Full Terminal, Pricing, Shifts, Reports |
| **Head Cashier** | [http://127.0.0.1:8001/login](http://127.0.0.1:8001/login) | `cashier@victorious.com` | `12345678` | Ring Sales, Hold Carts, Drawer Reconciliation |
| **Shift Cashier 2** | [http://127.0.0.1:8001/login](http://127.0.0.1:8001/login) | `cashier2@victorious.com` | `12345678` | Ring Sales, Print Thermal Receipts |
| **Storekeeper** | [http://127.0.0.1:8001/login](http://127.0.0.1:8001/login) | `storekeeper@victorious.com` | `12345678` | Stock Inward, Batch Transfers, Inventory Counts |

---

## 7. 🧪 Testing Scenarios Matrix (End-to-End Workflows)

### Scenario A: Full E-Commerce Omnichannel Lifecycle
1. **Browse & Order**: Login as `customer@victorious.com` on [http://127.0.0.1:8000/](http://127.0.0.1:8000/) $\rightarrow$ Checkout with Wallet Balance `₦50,000.00`.
2. **Merchant Processing**: Login as `vendor@victorious.com` on [http://127.0.0.1:8000/vendor/auth/login](http://127.0.0.1:8000/vendor/auth/login) $\rightarrow$ Confirm order and assign to rider.
3. **Logistics Dispatch**: Login as `rider@victorious.com` $\rightarrow$ Accept order, mark `out_for_delivery`, and complete OTP delivery.
4. **Commission Split Verification**: Login as `admin@admin.com` on [http://127.0.0.1:8000/login/admin](http://127.0.0.1:8000/login/admin) $\rightarrow$ Verify commission credited with zero drift ($\Delta = 0.00$).

### Scenario B: In-Store POS Point-of-Sale Checkout
1. **Cashier Shift Open**: Login as `cashier@victorious.com` on [http://127.0.0.1:8001/login](http://127.0.0.1:8001/login) $\rightarrow$ Open cash drawer with `₦10,000.00` float.
2. **Barcode Scan & Split-Tender**: Add items to cart $\rightarrow$ Pay with Cash + Card split tender.
3. **Shift Reconcile**: Close drawer and verify expected vs actual cash balance.
