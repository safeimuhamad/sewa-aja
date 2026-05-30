const SewaAjaCheckout = (() => {
    const cartKey = 'sewaaja.cart';
    const apiBaseUrl = '/sewaaja/backend/services/booking-service/public/index.php';
    const paymentApiBaseUrl = '/sewaaja/backend/services/payment-service/public/index.php';
    const state = {
        cart: [],
        quote: null,
    };

    const el = {
        alert: document.getElementById('checkoutAlert'),
        cartItems: document.getElementById('cartItems'),
        clearCart: document.getElementById('clearCart'),
        form: document.getElementById('checkoutForm'),
        paymentMethod: document.getElementById('paymentMethod'),
        notes: document.getElementById('checkoutNotes'),
        subtotal: document.getElementById('summarySubtotal'),
        deposit: document.getElementById('summaryDeposit'),
        total: document.getElementById('summaryTotal'),
        button: document.getElementById('checkoutButton'),
        paymentActions: document.getElementById('paymentActions'),
        loginHint: document.getElementById('loginHint'),
    };

    function currency(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(value || 0));
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[character]));
    }

    function loadCart() {
        state.cart = JSON.parse(localStorage.getItem(cartKey) || '[]');
    }

    function saveCart() {
        localStorage.setItem(cartKey, JSON.stringify(state.cart));
    }

    function apiItems() {
        return state.cart.map((item) => ({
            product_id: item.product_id,
            slug: item.slug,
            quantity: Number(item.quantity || 1),
            start_date: item.start_date,
            end_date: item.end_date,
        }));
    }

    async function request(path, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        };
        const token = SewaAjaAuth.token();
        if (token) {
            headers.Authorization = `Bearer ${token}`;
            headers['X-Authorization'] = `Bearer ${token}`;
        }

        const response = await fetch(`${apiBaseUrl}${path}`, {
            ...options,
            headers,
        });
        const payload = await response.json().catch(() => ({
            success: false,
            message: 'Response API tidak valid.',
            errors: null,
        }));

        if (!response.ok || payload.success === false) {
            const error = new Error(payload.message || 'Request gagal.');
            error.payload = payload;
            throw error;
        }

        return payload.data;
    }

    async function paymentRequest(path, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        };
        const token = SewaAjaAuth.token();
        if (token) {
            headers.Authorization = `Bearer ${token}`;
            headers['X-Authorization'] = `Bearer ${token}`;
        }

        const response = await fetch(`${paymentApiBaseUrl}${path}`, {
            ...options,
            headers,
        });
        const payload = await response.json().catch(() => ({
            success: false,
            message: 'Response payment API tidak valid.',
            errors: null,
        }));

        if (!response.ok || payload.success === false) {
            const error = new Error(payload.message || 'Payment request gagal.');
            error.payload = payload;
            throw error;
        }

        return payload.data;
    }

    function showAlert(message, type = 'danger') {
        el.alert.className = `mb-5 rounded-lg border p-4 text-sm font-bold ${type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-700'}`;
        el.alert.textContent = message;
    }

    function renderCart() {
        if (!state.cart.length) {
            el.cartItems.innerHTML = `
                <div class="rounded-lg border border-dashed border-slate-200 p-8 text-center">
                    <h2 class="text-xl font-black text-[#061f4f]">Cart masih kosong</h2>
                    <p class="mt-2 text-sm font-semibold text-slate-500">Pilih produk dan tanggal sewa dari halaman detail produk.</p>
                    <a href="/sewaaja/products" class="mt-5 inline-flex rounded-full bg-[#ff6a00] px-6 py-3 text-sm font-black text-white">Jelajah Produk</a>
                </div>`;
            el.button.disabled = true;
            el.paymentActions.innerHTML = '';
            return;
        }

        el.button.disabled = false;
        el.cartItems.innerHTML = state.cart.map((item, index) => `
            <article class="rounded-lg border border-slate-100 p-4">
                <div class="flex flex-col justify-between gap-4 sm:flex-row">
                    <div>
                        <h3 class="text-lg font-black text-[#061f4f]">${escapeHtml(item.name)}</h3>
                        <p class="mt-1 text-sm font-bold text-slate-500">${escapeHtml(item.vendor || 'Vendor')}</p>
                        <p class="mt-2 text-sm font-semibold text-slate-500">${currency(item.price_per_day)}/hari · Deposit ${currency(item.deposit_amount)}</p>
                    </div>
                    <button class="self-start rounded-full border border-slate-200 px-4 py-2 text-xs font-black text-[#061f4f] transition hover:border-red-300 hover:text-red-600" type="button" data-remove="${index}">Hapus</button>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <label class="text-sm font-black text-[#061f4f]">
                        Mulai
                        <input type="date" value="${escapeHtml(item.start_date)}" data-field="start_date" data-index="${index}" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 font-semibold text-slate-600">
                    </label>
                    <label class="text-sm font-black text-[#061f4f]">
                        Selesai
                        <input type="date" value="${escapeHtml(item.end_date)}" data-field="end_date" data-index="${index}" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 font-semibold text-slate-600">
                    </label>
                    <label class="text-sm font-black text-[#061f4f]">
                        Jumlah
                        <input type="number" min="1" value="${escapeHtml(item.quantity)}" data-field="quantity" data-index="${index}" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 font-semibold text-slate-600">
                    </label>
                </div>
                <div class="mt-3 rounded-lg ${item.is_available === false ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-800'} p-3 text-sm font-bold">
                    ${item.status_text || 'Menunggu validasi stok...'}
                </div>
            </article>
        `).join('');
    }

    function renderQuote(quote) {
        state.quote = quote;
        el.subtotal.textContent = currency(quote.summary.subtotal_amount);
        el.deposit.textContent = currency(quote.summary.deposit_amount);
        el.total.textContent = currency(quote.summary.total_amount);

        state.cart = state.cart.map((cartItem) => {
            const quoted = quote.items.find((item) => item.product_id === cartItem.product_id && item.start_date === cartItem.start_date && item.end_date === cartItem.end_date);
            if (!quoted) {
                return cartItem;
            }

            return {
                ...cartItem,
                is_available: quoted.is_available,
                status_text: quoted.is_available
                    ? `${quoted.available_quantity} stok tersedia · ${quoted.duration_days} hari · Total ${currency(quoted.line_total)}`
                    : `Stok tersedia hanya ${quoted.available_quantity}`,
            };
        });
        saveCart();
        renderCart();
    }

    async function refreshQuote() {
        if (!state.cart.length) {
            el.subtotal.textContent = currency(0);
            el.deposit.textContent = currency(0);
            el.total.textContent = currency(0);
            return;
        }

        try {
            const quote = await request('/quote', {
                method: 'POST',
                body: JSON.stringify({ items: apiItems() }),
            });
            renderQuote(quote);
            el.button.disabled = !quote.summary.is_available;
        } catch (error) {
            showAlert(error.payload?.message || error.message);
            el.button.disabled = true;
        }
    }

    function bindEvents() {
        el.cartItems.addEventListener('change', (event) => {
            const input = event.target.closest('[data-field]');
            if (!input) {
                return;
            }

            const index = Number(input.dataset.index);
            const field = input.dataset.field;
            state.cart[index][field] = field === 'quantity' ? Math.max(1, Number(input.value || 1)) : input.value;

            if (state.cart[index].end_date < state.cart[index].start_date) {
                state.cart[index].end_date = state.cart[index].start_date;
            }

            saveCart();
            renderCart();
            refreshQuote();
        });

        el.cartItems.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove]');
            if (!remove) {
                return;
            }
            state.cart.splice(Number(remove.dataset.remove), 1);
            saveCart();
            renderCart();
            refreshQuote();
        });

        el.clearCart.addEventListener('click', () => {
            state.cart = [];
            saveCart();
            renderCart();
            refreshQuote();
        });

        el.form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!SewaAjaAuth.token()) {
                showAlert('Silakan login sebagai customer sebelum checkout.');
                el.loginHint.innerHTML = '<a class="font-black text-[#ff6a00]" href="/sewaaja/login">Masuk dulu</a>, lalu kembali ke checkout.';
                return;
            }

            el.button.disabled = true;
            el.button.textContent = 'Memproses...';

            try {
                const checkout = await request('/checkout', {
                    method: 'POST',
                    body: JSON.stringify({
                        items: apiItems(),
                        payment_method: 'payment_gateway',
                        notes: el.notes.value,
                    }),
                });

                const tokens = await createPaymentTokens(checkout.bookings);
                state.cart = [];
                saveCart();
                renderCart();
                renderQuote({
                    items: [],
                    summary: checkout.summary,
                });
                renderPaymentActions(tokens);
                showAlert(`Booking berhasil dibuat: ${checkout.bookings.map((booking) => booking.booking_code).join(', ')}`, 'success');
            } catch (error) {
                showAlert(error.payload?.message || error.message);
            } finally {
                el.button.disabled = state.cart.length === 0;
                el.button.textContent = 'Buat Booking';
            }
        });
    }

    async function createPaymentTokens(bookings) {
        const tokens = [];

        for (const booking of bookings) {
            const token = await paymentRequest('/midtrans/token', {
                method: 'POST',
                body: JSON.stringify({
                    payment_code: booking.payment_code,
                    channel: el.paymentMethod.value,
                }),
            });
            tokens.push({
                booking_code: booking.booking_code,
                payment_code: booking.payment_code,
                ...token,
            });
        }

        return tokens;
    }

    function renderPaymentActions(tokens) {
        el.paymentActions.innerHTML = tokens.map((token, index) => `
            <a href="${escapeHtml(token.redirect_url)}" target="_blank" rel="noopener" class="rounded-full bg-[#061f4f] px-6 py-3 text-center text-sm font-black text-white transition hover:bg-[#ff6a00]">
                Bayar ${escapeHtml(token.booking_code)}
            </a>
            ${index === 0 ? '<p class="text-center text-xs font-semibold text-slate-500">Link pembayaran Midtrans terbuka di tab baru.</p>' : ''}
        `).join('');
    }

    function init() {
        loadCart();
        bindEvents();
        renderCart();
        refreshQuote();

        if (!SewaAjaAuth.token()) {
            el.loginHint.innerHTML = '<a class="font-black text-[#ff6a00]" href="/sewaaja/login">Login</a> diperlukan untuk membuat booking.';
        }
    }

    return { init };
})();

SewaAjaCheckout.init();
