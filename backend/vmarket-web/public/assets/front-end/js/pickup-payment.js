/* Frozen quotes are displayed verbatim. Only the authenticated status endpoint confirms payment. */
(() => {
    document.querySelectorAll('.pickup-payment').forEach(section => {
        const find = selector => section.querySelector(selector);
        const message = find('.pickup-message');
        let quoteToken = null;
        let quotedCashback = false;
        let busy = false;
        async function request(url, payload) {
            const response = await fetch(url, {method: payload ? 'POST' : 'GET', credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                ...(payload ? {body: JSON.stringify(payload)} : {})});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Unable to verify payment. Please retry.');
            return data;
        }
        function hideQuote() {
            quoteToken = null;
            ['.pickup-quote', '.pickup-confirm', '.pickup-cancel'].forEach(selector => find(selector).classList.add('d-none'));
        }
        async function check() {
            try {
                const data = await request(section.dataset.statusUrl);
                const continuation = find('.pickup-continue');
                continuation.classList.add('d-none');
                if (data.payment_status === 'paid' && data.order_id && /^\d{6}$/.test(String(data.pickup_verification_code || ''))) {
                    message.textContent = `Payment verified. Order ${data.order_id}. Pickup code: ${data.pickup_verification_code}`;
                    hideQuote();
                    find('.pickup-review')?.setAttribute('disabled', 'disabled');
                } else {
                    message.textContent = data.payment_status === 'reconciliation_required'
                        ? 'Your payment is being reviewed. Check again before starting another payment.'
                        : `Payment status: ${data.payment_status}. Check again if you have already paid.`;
                    if (data.payment_status === 'pending' && data.authorization_url) {
                        const url = new URL(data.authorization_url);
                        if (url.protocol === 'https:') {
                            continuation.href = url.href;
                            continuation.classList.remove('d-none');
                        }
                    }
                }
            } catch (error) { message.textContent = error.message; }
        }
        find('.pickup-review')?.addEventListener('click', async () => {
            if (busy) return; busy = true; hideQuote();
            try {
                quotedCashback = find('.pickup-cashback').checked;
                const data = await request(section.dataset.quoteUrl, {use_cashback: quotedCashback});
                if (!data.quote_token || !data.quote) throw new Error('Unable to prepare a final quote.');
                quoteToken = data.quote_token;
                const quote = data.quote;
                find('.pickup-quote').textContent = `Merchandise: ${quote.currency} ${quote.merchandise_subtotal}; Tax: ${quote.currency} ${quote.tax_total}; Shipping: ${quote.currency} ${quote.shipping_total}; Cashback applied: ${quote.currency} ${quote.cashback_amount}; Total to pay: ${quote.currency} ${quote.total_amount}. Expires: ${quote.expires_at}`;
                ['.pickup-quote', '.pickup-confirm', '.pickup-cancel'].forEach(selector => find(selector).classList.remove('d-none'));
                message.textContent = 'Confirm this quote to begin payment.';
            } catch (error) { message.textContent = error.message; }
            finally { busy = false; }
        });
        find('.pickup-cancel').addEventListener('click', hideQuote);
        find('.pickup-cashback').addEventListener('change', hideQuote);
        find('.pickup-confirm').addEventListener('click', async () => {
            if (busy || !quoteToken) return; busy = true;
            try {
                const data = await request(section.dataset.payUrl, {quote_token: quoteToken, use_cashback: quotedCashback});
                hideQuote();
                if (data.authorization_url && new URL(data.authorization_url).protocol === 'https:') window.location.assign(data.authorization_url);
                else await check();
            } catch (error) { message.textContent = error.message; await check(); }
            finally { busy = false; }
        });
        find('.pickup-check').addEventListener('click', check);
        check();
    });
})();
