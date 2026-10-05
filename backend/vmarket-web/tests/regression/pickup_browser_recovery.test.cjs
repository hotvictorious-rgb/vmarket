/* Real headless browser DOM/network regression. Backend responses are disposable fixtures.
 * Requires Playwright (NODE_PATH or PLAYWRIGHT_MODULE_PATH) and EDGE_EXECUTABLE.
 * This does not exercise Laravel sessions, a gateway, SMS, or native secure storage.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const http = require('node:http');
const os = require('node:os');
const path = require('node:path');
const { describe, it, before, after } = require('node:test');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE_PATH || 'playwright');

describe('production storefront pickup browser recovery with fake backend', { concurrency: false }, () => {
    let server, context, page, profile, url;
    let status = { status: true, payment_status: 'unpaid', payment_request_id: null };
    let payCalls = 0;
    let errors = [];
    const root = path.resolve(__dirname, '../..');
    const executablePath = process.env.EDGE_EXECUTABLE;
    const launch = () => chromium.launchPersistentContext(profile, {
        executablePath, headless: true,
        args: ['--disable-extensions', '--no-first-run'],
        viewport: { width: 1000, height: 800 },
    });
    const messageMatches = async pattern => page.waitForFunction(source =>
        new RegExp(source).test(document.querySelector('.pickup-message').textContent), pattern.source);

    before(async () => {
        assert.ok(executablePath && fs.existsSync(executablePath), 'Set EDGE_EXECUTABLE to an installed Edge executable');
        const script = fs.readFileSync(path.join(root, 'public/assets/front-end/js/pickup-payment.js'), 'utf8');
        const blade = fs.readFileSync(path.join(root, 'resources/themes/theme_vmarket/theme-views/users-profile/pickup-reservations.blade.php'), 'utf8');
        const match = blade.match(/<section[\s\S]*?<\/section>/);
        assert.ok(match, 'Production Blade pickup section must exist');
        // Resolve template-only presentation placeholders for one accepted fixture reservation.
        // The actual production section and actual production JS remain the code under test.
        const section = match[0]
            .replace(/@if\([^\n]*\)\r?\n|@endif\r?\n/g, '')
            .replace(/data-quote-url="[^"]*"/, 'data-quote-url="/quote"')
            .replace(/data-pay-url="[^"]*"/, 'data-pay-url="/pay"')
            .replace(/data-status-url="[^"]*"/, 'data-status-url="/status"')
            .replace(/\{\{[\s\S]*?\}\}/g, 'Fixture reservation');
        const html = '<meta name="csrf-token" content="fixture-csrf"><style>.d-none{display:none}</style>' + section + '<script src="/pickup.js"></script>';
        server = http.createServer((req, res) => {
            res.setHeader('Cache-Control', 'no-store');
            if (req.url === '/') {
                res.setHeader('Content-Type', 'text/html');
                res.setHeader('Set-Cookie', 'fixture_session=disposable; Max-Age=600; SameSite=Lax; Path=/');
                return res.end(html);
            }
            if (req.url === '/pickup.js') {
                res.setHeader('Content-Type', 'application/javascript');
                return res.end(script);
            }
            res.setHeader('Content-Type', 'application/json');
            if (req.url === '/status') return res.end(JSON.stringify(status));
            let body = '';
            req.on('data', chunk => body += chunk);
            req.on('end', () => {
                try {
                    if (req.url === '/quote') {
                        assert.equal(req.headers['x-csrf-token'], 'fixture-csrf');
                        return res.end(JSON.stringify({ status: true, quote_token: 'A'.repeat(64), quote: {
                            currency: 'NGN', merchandise_subtotal: '101.01', tax_total: '7.57',
                            shipping_total: '0.00', cashback_amount: '20.00', total_amount: '88.58',
                            expires_at: 'fixture expiry',
                        } }));
                    }
                    if (req.url === '/pay') {
                        payCalls++;
                        assert.deepEqual(JSON.parse(body), { quote_token: 'A'.repeat(64), use_cashback: false });
                        status = { status: true, payment_status: 'pending', payment_request_id: 'fixture-attempt', authorization_url: 'https://example.test/payment' };
                        res.statusCode = 503;
                        return res.end(JSON.stringify({ message: 'Uncertain initialization' }));
                    }
                    res.statusCode = 404;
                    res.end('{}');
                } catch (error) {
                    errors.push(error);
                    res.statusCode = 500;
                    res.end(JSON.stringify({ message: 'Fixture assertion failed' }));
                }
            });
        });
        await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
        url = 'http://127.0.0.1:' + server.address().port;
        profile = fs.mkdtempSync(path.join(os.tmpdir(), 'v1-headless-pickup-'));
        context = await launch();
        // Prevent any external provider request, including accidental navigation.
        await context.route('**/*', route => route.request().url().startsWith(url + '/') ? route.continue() : route.abort());
        page = await context.newPage();
        await page.goto(url);
        await messageMatches(/Payment status: unpaid/);
    });

    after(async () => {
        try {
            if (context) await context.close();
        } finally {
            if (server) await new Promise(resolve => server.close(resolve));
            if (profile) {
                const resolved = path.resolve(profile);
                const temporaryRoot = path.resolve(os.tmpdir());
                assert.equal(path.dirname(resolved), temporaryRoot, 'Cleanup must stay directly inside the temporary directory');
                assert.ok(path.basename(resolved).startsWith('v1-headless-pickup-'), 'Cleanup must target this fixture profile');
                fs.rmSync(resolved, { recursive: true, force: true });
            }
        }
        assert.deepEqual(errors, []);
    });

    it('frozen quote and cancel produce zero payment calls', async () => {
        await page.locator('.pickup-review').click();
        await page.locator('.pickup-confirm').waitFor({ state: 'visible' });
        assert.match(await page.locator('.pickup-quote').innerText(), /NGN 88\.58/);
        await page.locator('.pickup-cancel').click();
        assert.equal(payCalls, 0);
    });

    it('uncertain initialization recovers authoritative pending attempt', async () => {
        await page.locator('.pickup-review').click();
        await page.locator('.pickup-confirm').click();
        await messageMatches(/Payment status: pending/);
        assert.equal(payCalls, 1);
        assert.equal(await page.locator('.pickup-continue').getAttribute('href'), 'https://example.test/payment');
        assert.doesNotMatch(await page.locator('.pickup-message').innerText(), /Pickup code|Payment verified/);
        assert.deepEqual(errors, []);
    });

    it('browser close and reopen retrieves pending attempt without another payment', async () => {
        await context.close();
        context = await launch();
        await context.route('**/*', route => route.request().url().startsWith(url + '/') ? route.continue() : route.abort());
        page = await context.newPage();
        await page.goto(url);
        await messageMatches(/Payment status: pending/);
        assert.equal(payCalls, 1);
        assert.equal(await page.locator('.pickup-continue').getAttribute('href'), 'https://example.test/payment');
    });

    it('paid status renders the dedicated pickup secret', async () => {
        status = { status: true, payment_status: 'paid', order_id: 99, pickup_verification_code: '123456' };
        await page.locator('.pickup-check').click();
        await messageMatches(/Payment verified\. Order 99\. Pickup code: 123456/);
        assert.ok(await page.locator('.pickup-review').isDisabled());
    });

    it('refunded status remains terminal across reload', async () => {
        status = { status: true, payment_status: 'refunded', order_id: 99 };
        await page.reload();
        await messageMatches(/refunded/);
        assert.ok(await page.locator('.pickup-review').isDisabled());
        assert.doesNotMatch(await page.locator('.pickup-message').innerText(), /123456/);
        assert.equal(payCalls, 1);
    });
});
