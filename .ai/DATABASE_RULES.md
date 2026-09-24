# Victorious MARKET — Authoritative Database Rules

> **CONTROL ZONE FILE — HUMAN OWNER ONLY**  
> AI agents are STRICTLY FORBIDDEN from editing this file. Any proposed modifications must be submitted via an AI-8 ticket accompanied by an approved Decision Record (`.ai/decisions/DECISION-XXXX.md`).

---

## 1. Authority & Ownership

1. **Sole Author**: Only **AI 1 (Backend / Database AI)** is permitted to create, edit, or propose database migrations, seeders, and model schema definitions. All other AI agents (AI 2–7) are strictly forbidden from modifying database migration files.
2. **Review & Gate Enforcement**: Every ticket with `Migration impact: yes` must be reviewed and approved by all required domain reviewers (AI 5, AI 6, AI 7) and requires explicit human sign-off before release.
3. **Immutable History**: Once a migration has been merged into `main` and executed on production/staging, its file is permanently immutable. Corrections must be applied as new subsequent forward migrations.

---

## 2. Migration Safety & Lifecycle Protocol

1. **Additive-First Schema Changes**:
   - Schema alterations must be backward-compatible and additive where possible.
   - Adding a non-nullable column to an existing table with live data is strictly forbidden without a default value or a multi-stage migration pipeline.
   - Column renames and column deletions must follow an expand-and-contract phase across at least two release cycles:
     1. Release 1: Add new column, dual-write in service layer, backfill data.
     2. Release 2: Migrate all read callers to new column.
     3. Release 3: Deprecate and drop old column.
2. **Deterministic Rollbacks**:
   - Every migration file MUST implement a complete, working `down()` method that restores the previous schema state without data loss.
   - Release gates require automated verification of `php artisan migrate` followed immediately by `php artisan migrate:rollback` in isolated test databases.
3. **No Destructive DDL in Migrations**:
   - `DROP TABLE`, `TRUNCATE`, and unbounded raw SQL deletes are prohibited in standard migrations unless approved via an explicit `DECISION-XXXX` record signed by the human operator.
4. **Verified Backup Point Requirement**:
   - No migration may be applied to production without a verified, restorable database snapshot recorded in the release manifest (`.ai/releases/RELEASE-YYYY-MM-DD-NNN.md`).

---

## 3. Data Integrity, Foreign Keys & Indexes

1. **Explicit Foreign Key Constraints**:
   - Relationships between entities (e.g., `orders`, `order_details`, `users`, `sellers`, `delivery_men`, `payment_requests`) must declare explicit foreign key constraints with appropriate `ON DELETE RESTRICT` or `ON DELETE CASCADE` semantics.
   - Financial ledger records, order lines, and payment records must NEVER use cascade deletes.
2. **Indexing Standards**:
   - Foreign key columns must be explicitly indexed.
   - Searchable fields (e.g., `slug`, `phone`, `email`, `order_status`, `created_at`) used in filters, joins, or sorting must have appropriate single or composite indexes.
   - Every index creation must verify query execution plans (`EXPLAIN`) to prevent table scans on high-traffic tables.
3. **Explicit Fillable Array (Anti-Mass-Assignment)**:
   - Every Eloquent model MUST define an explicit `$fillable` array specifying strictly permissible attributes, or an explicit `$guarded = ['id']`.
   - Never pass raw `$request->all()` into Eloquent `create()` or `update()`.

---

## 4. Concurrency & Transaction Rules

1. **Pessimistic Locks for Financial Mutations**:
   - Any read-modify-write operation on financial balances (customer wallet, vendor payout balance, rider cash collection, coupon usage counters) MUST execute inside an atomic database transaction:
     ```php
     DB::transaction(function () use ($userId, $amount) {
         $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();
         // validate balance and mutate
     });
     ```
   - Unlocked balance deductions are strictly prohibited.
2. **Atomic Row-Level Payment Locks**:
   - Payment webhook/callback processing must enforce an atomic update check:
     ```php
     $affected = PaymentRequest::where('id', $requestId)->where('is_paid', 0)->update(['is_paid' => 1]);
     if ($affected > 0) {
         // execute fulfillment hook
     }
     ```
3. **Deadlock Prevention**:
   - When acquiring locks across multiple rows or tables within a single transaction, always acquire locks in a globally consistent alphabetical or ID order to eliminate circular wait conditions.
   - Keep transactions concise; external HTTP API calls must NEVER be executed while holding database row locks.
