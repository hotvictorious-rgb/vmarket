/* Real headless Edge CSP enforcement using the production .htaccess policy.
 * All allowed cross-origin responses are intercepted disposable fixtures.
 * No Firebase/provider requests are transmitted and no account is changed.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const http = require('node:http');
const os = require('node:os');
const path = require('node:path');
const { describe, it, before, after } = require('node:test');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE_PATH || 'playwright');

const firebaseOrigins = [
    'https://www.googleapis.com',
    'https://identitytoolkit.googleapis.com',
    'https://securetoken.googleapis.com',
];
const attackerOrigin = 'https://unrelated-attacker.example';
const root = path.resolve(__dirname, '../..');

describe('production Firebase connect-src browser enforcement', { concurrency: false }, () => {
    let server, context, page, profile, baseUrl, currentPolicy, baselinePolicy;
    const intercepted = [];

    before(async () => {
        const edge = process.env.EDGE_EXECUTABLE;
        assert.ok(edge && fs.existsSync(edge), 'Set EDGE_EXECUTABLE to installed Edge');
        const apache = fs.readFileSync(path.join(root, '.htaccess'), 'utf8');
        const policy = apache.match(/^\s*Header\s+set\s+Content-Security-Policy\s+"([^"]+)"/m);
        assert.ok(policy, 'Actual Apache CSP policy must exist');
        currentPolicy = policy[1];
        // Baseline is the exact production policy with only the three new origins removed.
        baselinePolicy = currentPolicy.split(';').map(directive => directive.trim().startsWith('connect-src ')
            ? directive.split(/\s+/).filter(value => !firebaseOrigins.includes(value)).join(' ')
            : directive.trim()).join('; ');
        const sdk = fs.readFileSync(path.join(root, 'resources/themes/theme_vmarket/public/assets/plugins/firebase/firebase.min.js'), 'utf8');
        for (const origin of firebaseOrigins) assert.ok(sdk.includes(origin), 'Production Firebase SDK references ' + origin);
        server = http.createServer((req, res) => {
            res.setHeader('Content-Type', 'text/html');
            res.setHeader('Cache-Control', 'no-store');
            res.setHeader('Content-Security-Policy', req.url === '/baseline' ? baselinePolicy : currentPolicy);
            res.end('<!doctype html><title>Disposable CSP fixture</title><p>Actual production CSP header</p>');
        });
        await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
        baseUrl = 'http://127.0.0.1:' + server.address().port;
        profile = fs.mkdtempSync(path.join(os.tmpdir(), 'v1-headless-firebase-csp-'));
        context = await chromium.launchPersistentContext(profile, {
            executablePath: edge, headless: true,
            args: ['--disable-extensions', '--no-first-run'],
        });
        await context.route('**/*', async route => {
            const target = new URL(route.request().url());
            if (target.origin === baseUrl) return route.continue();
            // Never permit a request to reach a real external service.
            intercepted.push(target.origin);
            if (firebaseOrigins.includes(target.origin)) {
                return route.fulfill({ status: 200, contentType: 'application/json',
                    headers: { 'Access-Control-Allow-Origin': baseUrl },
                    body: JSON.stringify({ fixture: true, origin: target.origin }) });
            }
            return route.abort();
        });
        page = await context.newPage();
    });

    after(async () => {
        try {
            if (context) await context.close();
        } finally {
            if (server) await new Promise(resolve => server.close(resolve));
            if (profile) {
                const resolved = path.resolve(profile);
                assert.equal(path.dirname(resolved), path.resolve(os.tmpdir()));
                assert.ok(path.basename(resolved).startsWith('v1-headless-firebase-csp-'));
                fs.rmSync(resolved, { recursive: true, force: true });
            }
        }
    });

    async function probe(policyPath, origins) {
        await page.goto(baseUrl + policyPath);
        return page.evaluate(async targets => {
            const violations = [];
            const record = event => violations.push({ blockedURI: event.blockedURI, directive: event.effectiveDirective });
            document.addEventListener('securitypolicyviolation', record);
            const results = [];
            for (const origin of targets) {
                try {
                    const response = await fetch(origin + '/disposable-csp-probe');
                    results.push({ origin, ok: response.ok, body: await response.json() });
                } catch {
                    results.push({ origin, ok: false });
                }
            }
            // CSP violation events dispatch asynchronously after a rejected fetch.
            await new Promise(resolve => setTimeout(resolve, 50));
            document.removeEventListener('securitypolicyviolation', record);
            return { results, violations };
        }, origins);
    }

    it('original connect-src blocks all three SDK origins before network transmission', async () => {
        const before = intercepted.length;
        const result = await probe('/baseline', firebaseOrigins);
        assert.equal(intercepted.length, before, 'Blocked fetches must not reach network interception');
        for (const origin of firebaseOrigins) {
            assert.equal(result.results.find(row => row.origin === origin).ok, false);
            assert.ok(result.violations.some(row => row.directive === 'connect-src' && new URL(row.blockedURI).origin === origin), origin + ' must trigger connect-src violation: ' + JSON.stringify(result.violations));
        }
    });

    it('current production connect-src permits only the three mocked Firebase origins', async () => {
        const result = await probe('/current', firebaseOrigins);
        for (const origin of firebaseOrigins) {
            const response = result.results.find(row => row.origin === origin);
            assert.equal(response.ok, true, origin + ' must be allowed by current production CSP');
            assert.deepEqual(response.body, { fixture: true, origin });
            assert.ok(intercepted.includes(origin), 'The mocked response must be intercepted');
        }
        assert.deepEqual(result.violations, []);
    });

    it('current production connect-src still blocks an unrelated attacker origin', async () => {
        const before = intercepted.length;
        const result = await probe('/current', [attackerOrigin]);
        assert.equal(result.results[0].ok, false);
        assert.equal(intercepted.length, before, 'Attacker request must be blocked before network');
        assert.ok(result.violations.some(row => row.directive === 'connect-src' && new URL(row.blockedURI).origin === attackerOrigin), JSON.stringify(result.violations));
    });
});
