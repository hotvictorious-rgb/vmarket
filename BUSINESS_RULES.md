# 📜 Vmarket Core Business Rules

**Authoritative Domain Rules Enforced across the Vmarket Ecosystem**

---

## 1. Vendor Onboarding & Registration
* **Open Registration:** Any seller can register for free without mandatory upfront payment.
* **Status Lifecycle:** `pending` ➔ `approved` / `rejected` / `suspended`.
* **Verified Merchant Status 🛡️:** Granted only by Super Admin after inspecting NUBAN bank resolution match score and optional NIN/CAC documents.

---

## 2. Nigerian Banking & Anti-Theft Protection
* **Single Verified Account:** Withdrawals must route strictly to the vendor's linked bank account.
* **48-Hour Cooldown Timer:** Updating bank details triggers a 48-hour withdrawal lock (`bank_updated_at + 48 hours > now()`).
* **Email OTP Requirement:** If bank details are already registered, modifying them requires entering a 6-digit OTP sent to the vendor's primary email.

---

## 3. Financial Settlement & Escrow
* **Customer Payment:** All card/transfer payments via Paystack are held in platform escrow until fulfillment.
* **Order Delivery Confirmation:** Customer delivery OTP or delivery confirmation releases funds to the vendor's wallet balance.
* **Commission Split:** Platform deducts the configured percentage (e.g. 10%) before crediting vendor wallet.
* **Mandatory Payout Proof:** Admin cannot approve any withdrawal without attaching a valid payment screenshot.

---

## 4. Logistics & Delivery Handoff (Pickup OTP)
* **Secret Pickup OTP:** Generated when the order is marked `processing`.
* **Physical Handoff Guard:** The delivery rider must collect and enter the 4-digit Pickup OTP at the vendor's shop before the status advances to `out_for_delivery`.
* **Delivery Confirmation:** Rider verifies delivery using the customer's delivery OTP or collected payment.

---

## 5. Communication & Anti-Circumvention
* **Direct Buyer-Seller Chat Hard-Disabled:** Customers cannot initiate direct off-platform chat with vendors; disputes and inquiries route through official customer support tickets.
* **Rider-Vendor Coordination:** In-app voice notes and media attachments are permitted exclusively for active order logistics.
