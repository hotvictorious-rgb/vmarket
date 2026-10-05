# Reproducible local storefront browser recovery verification

Frontend-owned test: `backend/vmarket-web/tests/regression/pickup_browser_recovery.test.cjs`.

Executed from the repository root on 2026-10-05. Saved raw runner output: `V1-PICKUP-BROWSER-RECOVERY-2026-10-05.log` in this directory. Result: 5 tests passed, 1 suite, 0 failures, exit 0. Node syntax and scoped diff whitespace checks passed.

The test reads the production pickup Blade section and production pickup-payment.js. Only template presentation placeholders and endpoint URLs are substituted for one disposable fixture reservation. It serves an ephemeral loopback HTTP server, uses a new temporary headless Edge profile, blocks external requests, closes the browser/server and validates the temporary profile path before cleanup. Responses are scripted fixtures, with no Laravel database, gateway, SMS or real account mutation.

Covered scenarios: exact server quote and cancel with zero payment calls; uncertain initialization recovering an authoritative pending attempt; full browser close/reopen recovery without another payment; paid status rendering the dedicated pickup secret; refunded status remaining terminal across reload.

Dependencies are configurable: `PLAYWRIGHT_MODULE_PATH` identifies an installed Playwright package (otherwise normal Node package resolution is used), and `EDGE_EXECUTABLE` identifies an installed Edge executable. Obtain bundled runtime paths with `load_workspace_dependencies`; do not download browsers or packages for this verifier.

Exact PowerShell invocation used, expressed relative to the user profile rather than a hardcoded username:

```powershell
$env:PLAYWRIGHT_MODULE_PATH = Join-Path $env:USERPROFILE '.cache\codex-runtimes\codex-primary-runtime\dependencies\node\node_modules\playwright'
$env:EDGE_EXECUTABLE = Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe'
node --test --test-reporter=spec backend/vmarket-web/tests/regression/pickup_browser_recovery.test.cjs 2>&1 |
    Tee-Object -FilePath .ai/tickets/in-progress/V1-PICKUP-BROWSER-RECOVERY-2026-10-05.log
exit $LASTEXITCODE
```

Limits: this verifies actual browser DOM/network behavior with fake backend responses. It does not certify Laravel session persistence/revocation, real payment/provider redirects, live Firebase/SMS, Android/iOS devices or native secure storage. The disposable cookie is a fixture only.
