# 🎨 Victorious MARKET — Complete Ecosystem Screen Mockups & UI/UX Design System

This document serves as the permanent reference guide for the **Victorious MARKET** mobile and web design system, detailing all 16 ecosystem screens in the signature **Royal Purple & Gold** brand aesthetic.

---

## 👑 1. Brand Palette & Visual Tokens

| Design Token | Hex Code | Purpose & Usage |
| :--- | :--- | :--- |
| **Primary Royal Purple** | `#6A1B9A` / `#4A148C` | Top App Bar, Primary Buttons, Active Navigation Tabs |
| **Secondary Gold Accent**| `#FFD700` / `#FFA000` | Discount Badges, VIP Highlights, Rating Stars, Handover OTP |
| **Scaffold Background** | `#F8F9FA` | Modern high-contrast clean surface |
| **Card Surface** | `#FFFFFF` | Product cards, Search containers, Cart items |
| **Text Primary** | `#1A1A1A` | Headings, Product titles |
| **Text Secondary** | `#757575` | Review counts, Category subtitles, hint text |

---

## 📑 2. Complete Screen Directory (16 Screens)

| # | Screen Name | Route / Widget File | Core Purpose & Features |
| :-: | :--- | :--- | :--- |
| **1** | **Home Screen** | `HomePage` in `home_screens.dart` | Banners, Call-to-Order pill, Search, Circular Categories, 2-Col Products |
| **2** | **Product Details** | `ProductDetails` in `product_details_screen.dart` | Image slider, Naira pricing, variation chips (Size/Color), Seller card, Buy Now |
| **3** | **Cart & Bag** | `CartScreen` in `cart_screen.dart` | Quantity steppers, coupon code field, subtotal, shipping fee calculation |
| **4** | **Checkout & Payment** | `CheckoutScreen` in `checkout_screen.dart` | Uyo delivery address, Paystack debit card, Bank transfer, Wallet checkout |
| **5** | **Order Tracking & OTP** | `OrderDetailsScreen` in `order_details_screen.dart` | **Secret Handover OTP (6-digits)**, live delivery timeline, Chat with Delivery Rider |
| **6** | **More / Account Hub** | `MoreScreen` in `more_screen_view.dart` | Profile, Digital Wallet, Orders, Wishlist, Language, Support *(5th Tab)* |
| **7** | **Inbox (Rider Chat)** | `InboxScreen` in `inbox_screen.dart` | Direct 1-on-1 messaging stream with assigned delivery riders |
| **8** | **Chat Conversation** | `ChatScreen` in `chat_screen.dart` | Real-time chat bubbles, voice notes, photo attachments with rider |
| **9** | **Search & Filters** | `SearchScreen` in `search_screen.dart` | Predictive search, price range slider, category & brand filters |
| **10** | **All Categories** | `AllCategoryScreen` in `all_category_screen.dart` | Category side-tabs and sub-category thumbnail grids |
| **11** | **Brands Showcase** | `AllBrandScreen` in `all_brand_screen.dart` | Brand directory and verified brand storefronts |
| **12** | **Flash Deals Hub** | `FlashDealScreenView` in `flash_deal_screen.dart` | Mega discount countdown timer and exclusive flash sale inventory |
| **13** | **Clearance Sale Hub** | `ClearanceSaleScreen` in `clearance_sale_screen.dart` | Discount clearance items with inventory counters |
| **14** | **Customer Digital Wallet** | `WalletScreen` in `wallet_screen.dart` | Available balance (₦), Paystack instant top-up, transaction ledger |
| **15** | **Customer Profile** | `ProfileScreen` in `profile_screen.dart` | User avatar, name, phone, email, and password security management |
| **16** | **Help & Support Tickets**| `SupportTicketScreen` in `support_ticket_screen.dart` | Create support tickets, priority status, and customer service |

---

## 📱 3. Screen Breakdown & UI Specifications

### 🛍️ Screen 1: Product Details Page
* **Visual Identity:** Royal Purple and Gold accents.
* **Top Header:** Clean translucent app bar with Back button, Favorite heart, and Cart icon with live badge counter.
* **Gallery:** Swipeable high-resolution hero product carousel with dot pagination.
* **Pricing & Discount:** Prominent Naira price (**₦45,000**), strikethrough original price, and Gold discount tag (**-18% OFF**).
* **Variation Chips:** Interactive pill selectors for Size and Color.
* **Vendor Section:** Verified Merchant badge and direct "Chat with Delivery Rider" banner.
* **Sticky Action Bar:** Two-button layout with "Add to Cart" and "Buy Now".

---

### 💳 Screen 2: Cart & Checkout Page
* **Item Management:** Card-based cart items with image thumbnails, price, and plus/minus steppers.
* **Delivery Address:** Address card with pin icon tailored for Akwa Ibom delivery zones (e.g. Shelter Afrique, Uyo).
* **Payment Gateways:** Paystack (Debit Card), Bank Transfer (with proof upload), and Victorious Digital Wallet.
* **Order Summary:** Item subtotal, shipping fee, voucher discount, and highlighted Grand Total in Gold typography.

---

### 🛵 Screen 3: Orders & Secret Handover OTP Tracking
* **Security Verification:** Prominent Gold card displaying the **Secret Handover OTP (e.g. 491820)** with security instructions: *"Give this code to rider only when package is physically inspected."*
* **Status Stepper:** Vertical 4-stage delivery timeline (*Order Confirmed ➔ Picked Up ➔ Out for Delivery ➔ Delivered*).
* **Rider Card:** Rider avatar, name, rating, call button, and purple *"Chat with Rider"* button.

---

### 👑 Screen 4: More / Account Hub (5th Navigation Tab)
* **User Profile Header:** Royal Purple gradient banner with avatar, user name, and verified badge.
* **Digital Wallet Widget:** Live available balance (**₦45,000**), Top Up button, and Gold Loyalty Points counter.
* **Quick Action Grid:** 2-column rounded cards for My Orders, Wishlist, Track Order, Chat with Delivery Rider, Address Book, Support Tickets, Currency, and Language.

---

### 🏠 Screen 5: Home Screen
* **Brand Header:** White Victorious MARKET logo + Call-to-Order pill (`📞 +2349118949035`).
* **Search Bar:** Floating pill container with circular Royal Purple search button.
* **Banners:** 3D promotional hero slider.
* **Categories:** Glossy circular category capsules with drop-shadows.
* **Product Feed:** Adaptive 2-column product grid with star ratings and Naira prices.
