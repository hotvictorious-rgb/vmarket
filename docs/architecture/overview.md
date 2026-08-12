# 🏛️ Vmarket Architecture Overview

This document provides detailed subsystem breakdowns for Victorious MARKET (Vmarket).

## 1. Unified Multi-Client Topology
Vmarket is designed as a centralized monolith backend serving multiple client interfaces:
- **Web Storefront:** Consumer-facing shopping interface with Aster & Default themes.
- **Admin Panel:** Platform administration, financial auditing, KYC approvals, order management.
- **Vendor Panel:** Merchant catalog management, order processing, wallet withdrawals.
- **Mobile Clients:** 3 Flutter native applications (Customer, Vendor, Delivery Man).

## 2. Shared Domain Models
All models live under `backend/Admin and web new install V16.1/app/Models` and are replicated as typed Dart models in the Flutter applications.
