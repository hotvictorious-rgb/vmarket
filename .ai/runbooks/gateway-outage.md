# Runbook: Payment Gateway & SMS Provider Outage Protocol

> **CONTROL ZONE RUNBOOK — HUMAN OPERATOR ONLY**

---

## 1. Gateway Failover Strategy
Victorious MARKET integrates redundant payment gateways:
- Primary: Paystack
- Secondary: Flutterwave
- Alternative / Offline: Cash On Delivery (COD) / Direct Bank Transfer

---

## 2. Dynamic Failover Procedure
1. If Paystack error rate $> 5\%$ over 5 consecutive minutes:
   - Navigate to Admin Panel $\rightarrow$ Business Settings $\rightarrow$ Payment Methods.
   - Toggle Flutterwave as the prioritized primary digital gateway.
   - Alternatively, toggle COD as available for eligible directional lanes.
2. In-flight orders awaiting confirmation:
   - Keep `payment_requests` in pending state with a 60-minute TTL.
   - Execute manual verification cron:
     ```bash
     php artisan payment:reconcile-pending
     ```
