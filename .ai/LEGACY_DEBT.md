# Victorious MARKET — Legacy Technical Debt Register (LEGACY_DEBT.md)

> **CONTROL ZONE FILE — HUMAN OWNERSHIP ONLY**  
> **Status:** Stage 1 Legacy Debt Register under Multi-AI Control System Specification (v3) §21.2 & Appendix H  
> **Rule:** An AI claiming an `N/A` test exemption on untouched legacy code MUST cite an approved `LD-NNN` ID from this register. AIs cannot invent or self-approve debt IDs.

---

## 1. Active Legacy Debt Register

| ID | Area / Subsystem | Missing Coverage or Defect Description | Risk | Owner | Target Tier | Target Date | Approved By / Date |
| :---: | :--- | :--- | :---: | :--- | :---: | :---: | :--- |
| `LD-001` | `backend/vmarket-web/app/Http/Controllers/Admin/POS/` | Stock 6valley POS engine lacks automated unit test coverage; decoupled from online checkout | **Low** | AI 1 (Backend) | Tier C | 2026-12-31 | Human / 2026-09-24 |
| `LD-002` | `backend/vmarket-web/resources/themes/theme_aster/` | Legacy alternative Aster theme templates lack automated integration tests | **Low** | AI 2 (Customer) | Tier C | 2026-11-30 | Human / 2026-09-24 |
| `LD-003` | `backend/vmarket-web/resources/themes/theme_fashion/` | Legacy alternative Fashion theme templates lack automated integration tests | **Low** | AI 2 (Customer) | Tier C | 2026-11-30 | Human / 2026-09-24 |
| `LD-004` | `User app/lib/features/auth/` | Customer mobile app legacy social authentication fallbacks have partial test coverage | **Medium** | AI 2 (Customer) | Tier B | 2026-10-31 | Human / 2026-09-24 |
| `LD-005` | `Delivery Man App/lib/view/screens/wallet/` | Rider cash-in-hand reconciliation views need server-authoritative mock tests; COD is disabled for V1 | **Medium** | AI 4 (Operations) | Tier B | 2026-10-15 | Human / 2026-09-24 |
| `LD-006` | `backend/vmarket-web/tests/Feature/DeliveryFlowLifecycleTest.php` | In-memory SQLite `:memory:` lacks baseline orders schema fixture before alter table runs | **Medium** | AI 1 (Backend) | Tier A | 2026-10-15 | Human / 2026-09-24 |

---

## 2. Debt Invariant & Ratchet Policy (§21.5)

1. **No Coverage Degradation**: A touched file must never drop below its baseline coverage.
2. **Changed Code Requires Tests**: Even for an area with an approved `LD-NNN` debt entry, any **new or modified lines of code** must have automated tests proving the change works.
3. **Debt Retirement**: When an area achieves automated test coverage, its entry in this register is marked `RETIRED` and its tier in `GATED_AREAS.md` is promoted.
