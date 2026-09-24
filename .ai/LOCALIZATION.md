# Victorious MARKET — Authoritative Localization & Internationalization Rules

> **CONTROL ZONE FILE — HUMAN OWNER ONLY**  
> AI agents are STRICTLY FORBIDDEN from editing this file. Any proposed modifications must be submitted via an AI-8 ticket accompanied by an approved Decision Record (`.ai/decisions/DECISION-XXXX.md`).

---

## 1. Supported Locales & Fallback Hierarchy

1. **Authoritative Primary Locale**:
   - `en` — English (Nigeria / Global). This is the default base locale for all platforms.
2. **Planned & Regional Locales**:
   - `pcm` — Nigerian Pidgin
   - `ha` — Hausa
   - `yo` — Yoruba
   - `ig` — Igbo
3. **Graceful Fallback**:
   - If a translation key is missing in any secondary locale, the localization engine MUST fall back to `en`.
   - Applications must never render raw translation keys (e.g. `messages.order_not_found`) to the end-user.

---

## 2. String Extraction & Key Architecture

1. **Zero Hardcoded Strings**:
   - All user-facing labels, buttons, messages, errors, and tooltips must reside in localization files.
   - Hardcoding plain text strings in Blade templates or Flutter widgets is strictly prohibited.
2. **Repository Localization Files**:
   - **Backend & Admin Web**: `backend/vmarket-web/resources/lang/en/messages.php`
   - **Customer App**: `User app/assets/language/en.json`
   - **Vendor App**: `Vendor app/assets/language/en.json`
   - **Delivery App**: `Delivery Man App/assets/language/en.json`
3. **Naming Convention**:
   - Use snake_case or hierarchical keys categorized by domain:
     - `auth.login_successful`
     - `checkout.select_delivery_lane`
     - `order.out_for_delivery_status`

---

## 3. Stable Machine-Readable API Error Codes

1. **Backend Error Code Standard**:
   - Backend APIs must return predictable, machine-readable error codes in error responses:
     ```json
     {
       "errors": [
         {
           "code": "WALLET_INSUFFICIENT_BALANCE",
           "message": "Your wallet balance is insufficient to complete this payment."
         }
       ]
     }
     ```
2. **Client Localization of Error Messages**:
   - Frontend apps (Customer, Vendor, Delivery) must match against the `code` attribute to look up localized user messages, using the backend `message` attribute solely as a fallback.

---

## 4. Currency, Number & Date Formatting

1. **Currency Representation**:
   - Currency symbol for Nigerian Naira: `₦` (Unicode `U+20A6`).
   - Standard format: `₦1,250.00` (thousands separator `,`, decimal point `.`).
   - In plain text or SMS/OTP logs: `NGN 1,250.00`.
2. **Date & Time Presentation**:
   - Date format: `DD/MM/YYYY` (e.g. `24/09/2026`) or `DD MMM YYYY` (e.g. `24 Sep 2026`).
   - Time format: 12-hour or 24-hour presentation in West Africa Time (`WAT`, UTC+1).
