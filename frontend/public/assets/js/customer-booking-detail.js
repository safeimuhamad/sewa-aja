const SewaAjaBookingDetail = (() => {
    const bookingId = new URLSearchParams(window.location.search).get('id');
    let currentBooking = null;

    const elements = {
        title: document.getElementById('bookingTitle'),
        status: document.getElementById('bookingStatus'),
        meta: document.getElementById('bookingMeta'),
        summary: document.getElementById('bookingSummary'),
        items: document.getElementById('bookingItems'),
        payment: document.getElementById('paymentInfo'),
        alert: document.getElementById('detailAlert'),
        cancel: document.getElementById('cancelBooking'),
        invoice: document.getElementById('downloadInvoice'),
        logout: document.getElementById('logoutButton'),
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

    function showAlert(message, tone = 'error') {
        elements.alert.className = `mb-6 rounded-lg px-4 py-3 text-sm font-bold ${tone === 'error' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'}`;
        elements.alert.textContent = message;
        elements.alert.hidden = false;
    }

    function render(booking) {
        currentBooking = booking;
        elements.title.textContent = booking.booking_code;
        elements.status.textContent = booking.status;
        elements.status.className = 'rounded-full bg-[#fff7f1] px-4 py-2 text-sm font-black text-[#ff6a00]';
        elements.meta.textContent = `${booking.store_name} | ${booking.start_date} s/d ${booking.end_date}`;
        elements.cancel.hidden = !['pending', 'confirmed'].includes(booking.status);

        elements.summary.innerHTML = [
            ['Subtotal', currency(booking.subtotal_amount)],
            ['Deposit', currency(booking.deposit_amount)],
            ['Total', currency(booking.total_amount)],
        ].map(([label, value]) => `
            <div class="flex items-center justify-between border-b border-slate-100 py-3 last:border-b-0">
                <span class="text-sm font-bold text-slate-500">${escapeHtml(label)}</span>
                <strong class="text-[#061f4f]">${escapeHtml(value)}</strong>
            </div>
        `).join('');

        elements.items.innerHTML = booking.items.map((item) => `
            <article class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex flex-col justify-between gap-3 sm:flex-row">
                    <div>
                        <h3 class="font-black text-[#061f4f]">${escapeHtml(item.product_name)}</h3>
                        <p class="mt-1 text-sm font-semibold text-slate-500">${escapeHtml(item.start_date)} s/d ${escapeHtml(item.end_date)} | Qty ${escapeHtml(item.quantity)}</p>
                    </div>
                    <strong class="text-[#061f4f]">${currency(item.line_total)}</strong>
                </div>
            </article>
        `).join('');

        elements.payment.innerHTML = `
            <div class="rounded-lg bg-[#f8fbff] p-4">
                <p class="text-sm font-black text-slate-500">Kode payment</p>
                <strong class="mt-1 block text-[#061f4f]">${escapeHtml(booking.payment_code || '-')}</strong>
            </div>
            <div class="rounded-lg bg-[#f8fbff] p-4">
                <p class="text-sm font-black text-slate-500">Metode</p>
                <strong class="mt-1 block text-[#061f4f]">${escapeHtml(booking.payment_method || '-')}</strong>
            </div>
            <div class="rounded-lg bg-[#f8fbff] p-4">
                <p class="text-sm font-black text-slate-500">Status</p>
                <strong class="mt-1 block text-[#061f4f]">${escapeHtml(booking.payment_status || 'pending')}</strong>
            </div>`;
    }

    function invoiceHtml(invoice) {
        const booking = invoice.booking;
        const rows = booking.items.map((item) => `
            <tr>
                <td>${escapeHtml(item.product_name)}</td>
                <td>${escapeHtml(item.quantity)}</td>
                <td>${currency(item.price_per_day)}</td>
                <td>${currency(item.line_total)}</td>
            </tr>
        `).join('');

        return `<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>${escapeHtml(invoice.invoice_number)}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #071a3d; margin: 40px; }
        h1 { color: #061f4f; }
        table { border-collapse: collapse; width: 100%; margin-top: 24px; }
        th, td { border-bottom: 1px solid #e8edf5; padding: 12px; text-align: left; }
        th { background: #f8fbff; }
        .total { margin-top: 24px; text-align: right; font-size: 20px; font-weight: 800; }
    </style>
</head>
<body>
    <h1>${escapeHtml(invoice.invoice_number)}</h1>
    <p>Booking: ${escapeHtml(booking.booking_code)}</p>
    <p>Customer: ${escapeHtml(booking.customer_name)} (${escapeHtml(booking.customer_email)})</p>
    <p>Vendor: ${escapeHtml(booking.store_name)}</p>
    <p>Tanggal: ${escapeHtml(booking.start_date)} s/d ${escapeHtml(booking.end_date)}</p>
    <table>
        <thead><tr><th>Produk</th><th>Qty</th><th>Harga/hari</th><th>Total</th></tr></thead>
        <tbody>${rows}</tbody>
    </table>
    <p class="total">Total: ${currency(booking.total_amount)}</p>
</body>
</html>`;
    }

    function downloadFile(filename, content) {
        const blob = new Blob([content], { type: 'text/html;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();
        URL.revokeObjectURL(url);
    }

    async function load() {
        if (!bookingId) {
            showAlert('ID booking tidak ditemukan.');
            return;
        }

        const data = await SewaAjaCustomerApi.booking(bookingId);
        render(data.booking);
    }

    function bindEvents() {
        elements.cancel.addEventListener('click', async () => {
            if (!currentBooking || !window.confirm('Batalkan booking ini?')) {
                return;
            }
            try {
                const data = await SewaAjaCustomerApi.cancelBooking(currentBooking.id);
                render(data.booking);
                showAlert('Booking berhasil dibatalkan.', 'success');
            } catch (error) {
                showAlert(error.message || 'Booking gagal dibatalkan.');
            }
        });

        elements.invoice.addEventListener('click', async () => {
            try {
                const data = await SewaAjaCustomerApi.invoice(currentBooking.id);
                downloadFile(`${data.invoice.invoice_number}.html`, invoiceHtml(data.invoice));
            } catch (error) {
                showAlert(error.message || 'Invoice gagal dibuat.');
            }
        });

        elements.logout.addEventListener('click', async () => {
            await SewaAjaAuth.logout();
            window.location.href = '/sewaaja/login';
        });
    }

    async function init() {
        const session = SewaAjaCustomerApi.requireCustomerSession();
        if (!session) {
            return;
        }
        bindEvents();
        try {
            await load();
        } catch (error) {
            showAlert(error.message || 'Detail booking gagal dimuat.');
        }
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', SewaAjaBookingDetail.init);
