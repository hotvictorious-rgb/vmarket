# Review Template (Appendix B)

Ticket: VM-VEND-005
Reviewer:                 REVIEWER AI (sole gatekeeper for all backend + frontend code)
Model/tool used:          opencode / muse-spark (single-coordinator session)
Commit reviewed:          10bab123ba79d2ce1ce66ac96855c8968e4bea4f
Review cycle number:      1 (BACKEND_DONE collection; no prior cycles)
Files reviewed:
- database/migrations/2026_09_29_000001_add_vendor_entity_fields_to_sellers_table.php (new; 16 columns + backfill + full down(); style matches repo anonymous-class convention)
- app/Models/Seller.php (fillable +8: business_type, legal_name, cac_status, verification_method/by/at/notes, payout_status; nin/cac/kyc/applied/approved already present)
- app/Http/Requests/Vendor/VendorAddRequest.php (business_type required|in; legal_name/CAC conditional on type; NIN conditional on individual; TIN stays nullable; banner lines already gone via VEND-003)
- app/Services/VendorService.php (getAddData maps new fields; CAC uppercased; cac_document upload via KYC path; applied_at stamped; pending preserved)
- .ai/tickets/in-progress/VM-VEND-005.md (Status BACKEND_DONE, policy + walks + sell-gate inventory recorded)
- .ai/status/results/VM-VEND-005/10bab123ba79d2ce1ce66ac96855c8968e4bea4f.json (17/17 PASS, commit binding verified)
Tests run by reviewer:
- BACKEND_AI tree (RUNTIME FIXED: vendor junction replaced with real copy; class identity re-proven by ReflectionClass to own tree): `php -l` x3 clean; migration fresh + rollback + rerun clean (neighbor migration restored after an unrelated rollback nick); `run-all.ps1 -Ticket VM-VEND-005` full HEAD 17/17 PASS (witnessed)
- Live server :8000 (own code): BN+CAC → status 1, row id 14 fully stored (bn/BN FIX VENTURES/BN999001/doc/pending×3/applied); individual+NIN → status 1; BN w/o CAC doc → rejected; individual w/o NIN → rejected with exact message
- Human policy encoded (no unregistered selling; TIN deferred; no auto-approve); conditional rules match policy verbatim

Business-rule findings:
- No gating change (registration open; approval semantics intact). Existing sellers backfill individual/PENDING. KYC submit path (`NigerianKycService`, previously fatal on missing columns) unblocked as a side effect — its endpoints remain unwired (noted, not in scope). None blocking.
Security findings:
- Validation strictness increased (conditional requireds); password/shop/image/logo rules untouched. No new inputs beyond policy fields. File upload reuses the vetted KYC path.
Prompt-injection / untrusted-input findings:
- None. No prompt surfaces.
Frontend findings:
- None in this ticket (form fields for new inputs ship with vendor-information updates later; API accepts them now).
Backend findings:
- Ownership boundary PASS: migration + model + request + service + own ticket notes. No routes/config/other logic. SCOPE honored.
Integration findings:
- Sell-gate inventory attached (SellerMiddleware approved-only + logout; API pending 401 proven live; marketplacePurchasable approved-seller; publish scopes approved). NigerianKycService noted unwired — candidate follow-up.
Client compatibility findings:
- Additive request fields only; old payloads validate identically (individual default path).
Testing findings:
- JSON uncommitted per hook (untracked); counts in ticket History. Single-commit flow (fix+docs) with run-all AT the release HEAD — binding exact, no stale window.
- RUNTIME CAVEAT (full disclosure): pre-fix verification passes in this session ran with worktree vendor/ junctions resolving app classes to the user checkout; BACKEND_AI tree now carries a real vendor copy and all evidence above was produced post-fix. Prior tickets' gate JSONs predate this correction.
Performance findings:
- None. 16 nullable columns + backfill once.
Dependency findings:
- None. No new deps.
Privacy / data-impact findings:
- NIN/CAC document paths stored server-side, never serialized to public payloads (registration response contains no document fields — verified in walk responses). Sandbox rows only.
Design / localization findings:
- None (no messages added; existing keys reused).

Blockers:
- None.
Non-blockers:
- KYC submit endpoints unwired (service exists); vendor-information-form new inputs (account-type/CAC/NIN UI) for a frontend slice; REVIEWER_AI/FRONTEND_AI trees still carry vendor junctions (use BACKEND_AI as canonical verification tree until replaced).

Decision:                 APPROVED
