# Firebase production CSP browser verification

Frontend verifier: `backend/vmarket-web/tests/regression/firebase_csp_browser.test.cjs`.

Executed locally on 2026-10-05: 3 tests passed, 1 suite, 0 failures, exit 0. Raw runner output is retained in `V1-FIREBASE-CSP-BROWSER-2026-10-05.log` beside these notes. An initial assertion expected a bare origin in `securitypolicyviolation.blockedURI`; Edge reports the requested URL. The fixture assertion now compares the parsed URL origin. No product change was needed for this fixture correction.

The test reads the actual CSP from `backend/vmarket-web/.htaccess`, serves it as a response header on disposable loopback fixture pages, and uses a dedicated temporary headless Edge profile. Cross-origin requests that CSP permits are fulfilled by Playwright with JSON/CORS fixtures; every other external request is aborted. No external Firebase/provider requests are transmitted.

Actual production SDK references are present in `resources/themes/theme_vmarket/public/assets/plugins/firebase/firebase.min.js`: lines4370–4372 contain `https://www.googleapis.com`, `https://securetoken.googleapis.com`, and `https://identitytoolkit.googleapis.com`. The verifier asserts the three strings remain in the SDK. This is a source dependency assertion, not execution of the Firebase SDK against a provider.

Three browser scenarios:

1. A baseline derived by removing only the three added origins from the actual policy rejects all three fetches before network interception and produces `connect-src` security policy violation events.
2. The actual current policy permits all three mocked HTTPS Firebase origins, returns their fixture responses and produces no CSP violation events.
3. The actual current policy rejects an unrelated attacker origin before network interception and produces a `connect-src` violation event.

Exact invocation from the repository root:

```powershell
$env:PLAYWRIGHT_MODULE_PATH = Join-Path $env:USERPROFILE '.cache\codex-runtimes\codex-primary-runtime\dependencies\node\node_modules\playwright'
$env:EDGE_EXECUTABLE = Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe'
node --test --test-reporter=spec backend/vmarket-web/tests/regression/firebase_csp_browser.test.cjs 2>&1 |
    Tee-Object -FilePath .ai/tickets/in-progress/V1-FIREBASE-CSP-BROWSER-2026-10-05.log
exit $LASTEXITCODE
```

Both dependency paths are configurable environment variables; obtain current bundled package paths through `load_workspace_dependencies`. No install/download is required on this host. The verifier closes its context/server, verifies its temporary profile stays inside the temporary directory, and removes only that profile.

Scope limitation: browser enforcement of the local source policy is verified; deployed server header configuration, real provider credentials/claims, SMS delivery, native devices and real Laravel sessions remain separate checks. No Paystack allowlist, wildcard Google host or other CSP directive is added by this test. Backend policy editing is owned by the backend implementer.
