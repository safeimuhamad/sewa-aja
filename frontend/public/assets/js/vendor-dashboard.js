const VendorDashboard = (() => {
    const elements = {
        vendorName: document.getElementById('vendorName'),
        vendorLocation: document.getElementById('vendorLocation'),
        statsGrid: document.getElementById('statsGrid'),
        salesSummary: document.getElementById('salesSummary'),
        productTable: document.getElementById('productTable'),
        bookingTable: document.getElementById('bookingTable'),
        alert: document.getElementById('dashboardAlert'),
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

    function statusClass(status) {
        return {
            active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            draft: 'bg-slate-50 text-slate-700 ring-slate-200',
            inactive: 'bg-amber-50 text-amber-700 ring-amber-200',
            pending: 'bg-amber-50 text-amber-700 ring-amber-200',
            confirmed: 'bg-sky-50 text-sky-700 ring-sky-200',
            ongoing: 'bg-orange-50 text-orange-700 ring-orange-200',
            completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            cancelled: 'bg-rose-50 text-rose-700 ring-rose-200',
        }[status] || 'bg-slate-50 text-slate-700 ring-slate-200';
    }

    function badge(status) {
        return `<span class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 ${statusClass(status)}">${escapeHtml(status)}</span>`;
    }

    function showAlert(message, tone = 'error') {
        elements.alert.className = `mb-6 rounded-lg px-4 py-3 text-sm font-bold ${tone === 'error' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'}`;
        elements.alert.textContent = message;
        elements.alert.hidden = false;
    }

    function renderVendor(vendor) {
        elements.vendorName.textContent = vendor?.store_name || 'Vendor SewaAja';
        elements.vendorLocation.textContent = [vendor?.city, vendor?.province].filter(Boolean).join(', ') || 'Lokasi belum diisi';
    }

    function renderStats(summary) {
        const widgets = summary.widgets || {};
        const stats = [
            ['Produk aktif', widgets.active_products || 0],
            ['Total produk', widgets.total_products || 0],
            ['Booking pending', widgets.pending_bookings || 0],
            ['Rental berjalan', widgets.active_rentals || 0],
        ];

        elements.statsGrid.innerHTML = stats.map(([label, value]) => `
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-black text-slate-500">${escapeHtml(label)}</p>
                <strong class="mt-3 block text-3xl font-black text-[#061f4f]">${escapeHtml(value)}</strong>
            </article>
        `).join('');
    }

    function renderSales(summary) {
        const sales = summary.sales || {};
        const items = [
            ['Revenue paid', currency(sales.paid_revenue)],
            ['Revenue bulan ini', currency(sales.this_month_revenue)],
            ['Menunggu pembayaran', currency(sales.pending_amount)],
        ];

        elements.salesSummary.innerHTML = items.map(([label, value]) => `
            <div class="flex items-center justify-between border-b border-slate-100 py-4 last:border-b-0">
                <span class="text-sm font-bold text-slate-500">${escapeHtml(label)}</span>
                <strong class="text-base font-black text-[#061f4f]">${escapeHtml(value)}</strong>
            </div>
        `).join('');
    }

    function renderProducts(products) {
        if (!products.length) {
            elements.productTable.innerHTML = '<tr><td class="px-4 py-6 text-center text-sm text-slate-500" colspan="6">Belum ada produk.</td></tr>';
            return;
        }

        elements.productTable.innerHTML = products.map((product) => `
            <tr class="border-b border-slate-100">
                <td class="px-4 py-4">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 overflow-hidden rounded-lg bg-slate-100">
                            ${product.primary_image ? `<img src="${escapeHtml(product.primary_image)}" alt="${escapeHtml(product.name)}" class="h-full w-full object-cover">` : ''}
                        </div>
                        <div>
                            <a href="/sewaaja/vendor-product-form?id=${encodeURIComponent(product.id)}" class="font-black text-[#061f4f] hover:text-[#ff6a00]">${escapeHtml(product.name)}</a>
                            <p class="text-xs font-semibold text-slate-500">${escapeHtml(product.category_name || '-')}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-4 text-sm font-bold text-slate-600">${currency(product.price_per_day)}/hari</td>
                <td class="px-4 py-4 text-sm font-bold text-slate-600">${escapeHtml(product.stock_quantity)} ${escapeHtml(product.unit_label)}</td>
                <td class="px-4 py-4">${badge(product.status)}</td>
                <td class="px-4 py-4 text-right">
                    <a href="/sewaaja/vendor-product-form?id=${encodeURIComponent(product.id)}" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-black text-[#061f4f] hover:border-[#ff6a00] hover:text-[#ff6a00]">Edit</a>
                </td>
            </tr>
        `).join('');
    }

    function renderBookings(bookings) {
        if (!bookings.length) {
            elements.bookingTable.innerHTML = '<tr><td class="px-4 py-6 text-center text-sm text-slate-500" colspan="7">Belum ada booking masuk.</td></tr>';
            return;
        }

        elements.bookingTable.innerHTML = bookings.map((booking) => `
            <tr class="border-b border-slate-100">
                <td class="px-4 py-4">
                    <strong class="block text-sm font-black text-[#061f4f]">${escapeHtml(booking.booking_code)}</strong>
                    <span class="text-xs font-semibold text-slate-500">${escapeHtml(booking.created_at)}</span>
                </td>
                <td class="px-4 py-4 text-sm font-bold text-slate-600">${escapeHtml(booking.customer_name || '-')}</td>
                <td class="px-4 py-4 text-sm font-bold text-slate-600">${escapeHtml(booking.start_date)} s/d ${escapeHtml(booking.end_date)}</td>
                <td class="px-4 py-4 text-sm font-bold text-slate-600">${escapeHtml(booking.total_quantity)} item</td>
                <td class="px-4 py-4 text-sm font-black text-[#061f4f]">${currency(booking.total_amount)}</td>
                <td class="px-4 py-4">${badge(booking.status)}</td>
                <td class="px-4 py-4">
                    <select data-booking-status="${escapeHtml(booking.id)}" class="h-10 rounded-lg border border-slate-200 px-3 text-xs font-black text-[#061f4f]">
                        ${['pending', 'confirmed', 'ongoing', 'completed', 'cancelled'].map((status) => `<option value="${status}" ${status === booking.status ? 'selected' : ''}>${status}</option>`).join('')}
                    </select>
                </td>
            </tr>
        `).join('');
    }

    async function load() {
        const session = SewaAjaVendorApi.requireVendorSession();
        if (!session) {
            return;
        }

        try {
            const [dashboard, products, bookings] = await Promise.all([
                SewaAjaVendorApi.dashboard(),
                SewaAjaVendorApi.products(),
                SewaAjaVendorApi.bookings(),
            ]);

            renderVendor(dashboard.vendor || products.vendor || session.vendor);
            renderStats(dashboard.summary || {});
            renderSales(dashboard.summary || {});
            renderProducts(products.products || []);
            renderBookings(bookings.bookings || []);
        } catch (error) {
            showAlert(error.message || 'Dashboard gagal dimuat.');
        }
    }

    function bindEvents() {
        elements.bookingTable.addEventListener('change', async (event) => {
            const select = event.target.closest('[data-booking-status]');
            if (!select) {
                return;
            }

            select.disabled = true;
            try {
                await SewaAjaVendorApi.updateBookingStatus(select.dataset.bookingStatus, select.value);
                showAlert('Status booking berhasil diperbarui.', 'success');
                await load();
            } catch (error) {
                showAlert(error.message || 'Status booking gagal diperbarui.');
            } finally {
                select.disabled = false;
            }
        });

        elements.logout.addEventListener('click', async () => {
            await SewaAjaAuth.logout();
            window.location.href = '/sewaaja/login';
        });
    }

    function init() {
        bindEvents();
        load();
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', VendorDashboard.init);
