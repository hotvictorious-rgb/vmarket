const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const test = require('node:test');
test('storefront frozen quote precedes pay and status alone controls pickup secret', async () => {
    const elements = new Map();
    for (const selector of ['.pickup-message', '.pickup-continue', '.pickup-review', '.pickup-check', '.pickup-quote', '.pickup-confirm', '.pickup-cancel', '.pickup-cashback']) {
        elements.set(selector, {checked: false, textContent: '', classList: {add() {}, remove() {}}, setAttribute() {}, addEventListener(event, handler) {this.handler = handler;}});
    }
    const section = {dataset: {statusUrl: '/status', quoteUrl: '/quote', payUrl: '/pay'}, querySelector: selector => elements.get(selector)};
    const calls = [];
    const quote = {currency: 'NGN', merchandise_subtotal: '101.01', tax_total: '7.57', shipping_total: '0.00', cashback_amount: '20.00', total_amount: '88.58', expires_at: '2026-10-05'};
    const context = {document: {querySelectorAll: () => [section], querySelector: () => ({content: 'csrf'})}, URL,
        window: {location: {assign() {throw new Error('No URL should authorize success');}}},
        fetch: async (url, options) => {
            calls.push({url, options});
            return {ok: true, json: async () => url === '/quote' ? {status: true, quote_token: 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', quote} :
                {status: true, payment_status: 'pending', order_id: 55, pickup_verification_code: '123456'}};
        }};
    vm.runInNewContext(fs.readFileSync('public/assets/front-end/js/pickup-payment.js', 'utf8'), context);
    await new Promise(resolve => setImmediate(resolve));
    await elements.get('.pickup-review').handler();
    assert.equal(calls.filter(call => call.url === '/pay').length, 0);
    assert.match(elements.get('.pickup-quote').textContent, /NGN 88\.58/);
    elements.get('.pickup-cancel').handler();
    await elements.get('.pickup-confirm').handler();
    assert.equal(calls.filter(call => call.url === '/pay').length, 0);
    await elements.get('.pickup-review').handler();
    await elements.get('.pickup-confirm').handler();
    const paid = calls.filter(call => call.url === '/pay');
    assert.equal(paid.length, 1);
    assert.deepEqual(JSON.parse(paid[0].options.body), {quote_token: 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', use_cashback: false});
    assert.equal(paid[0].options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.doesNotMatch(elements.get('.pickup-message').textContent, /123456|Payment verified/);
});
