const SewaAjaCustomerDashboard = (() => {
    const state = {
        active: { page: 1, per_page: 5, status_group: 'active' },
        history: { page: 1, per_page: 5, status_group: 'history' },
    };

    const elements = {
        customerName: document.getElementById('customerName'),
        customerEmail: document.getElementById('customerEmail'),
        stats: document.getElementById('customerStats'),
        activeList: document.getElementById('activeRentals'),
        historyList: document.getElementById('rentalHistory'),
        activePagination: document.getElementById('activePagination'),
        historyPagination: document.getElementById('historyPagination'),
        profileForm: document.getElementById('profileForm'),
        profileName: document.getElementById('profileName'),
        profileEmail: document.getElementById('profileEmail'),
        profilePhone: document.getElementById('profilePhone'),
        alert: document.getElementById('customerAlert'),
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

    function badge(status) {
        const map = {
            pending: 'bg-amber-50 text-amber-700 ring-amber-200',
            confirmed: 'bg-sky-50 text-sky-700 ring-sky-200',
            ongoing: 'bg-orange-50 text-orange-700 ring-orange-200',
            completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            cancelled: 'bg-rose-50 text-rose-700 ring-rose-200',
        };

        return `<span class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 ${map[status] || 'bg-slate-50 text-slate-700 ring-slate-200'}">${escapeHtml(status)}</span>`;
    }

    function showAlert(message, tone = 'error') {
        elements.alert.className = `mb-6 rounded-lg px-4 py-3 text-sm font-bold ${tone === 'error' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'}`;
        elements.alert.textContent = message;
        elements.alert.hidden = false;
    }

    function renderProfile(user) {
        elements.customerName.textContent = user.name || 'Customer SewaAja';
        elements.customerEmail.textContent = user.email || '';
        elements.profileName.value = user.name || '';
        elements.profileEmail.value = user.email || '';
        elements.profilePhone.value = user.phone || '';
    }

    function renderStats(widgets) {
        const cards = [
            ['Rental aktif', widgets.active_rentals || 0],
            ['Booking pending', widgets.pending_bookings || 0],
            ['Riwayat selesai', widgets.completed_rentals || 0],
            ['Total spend', currency(widgets.total_spend || 0)],
        ];

        elements.stats.innerHTML = cards.map(([label, value]) => `
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-black text-slate-500">${escapeHtml(label)}</p>
                <strong class="mt-3 block text-2xl font-black text-[#061f4f]">${escapeHtml(value)}</strong>
            </article>
        `).join('');
    }

    function bookingCard(booking) {
        return `
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-lg font-black text-[#061f4f]">${escapeHtml(booking.booking_code)}</h3>
                            ${badge(booking.status)}
                        </div>
                        <p class="mt-2 text-sm font-bold text-slate-500">${escapeHtml(booking.store_name)} | ${escapeHtml(booking.start_date)} s/d ${escapeHtml(booking.end_date)}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-500">${escapeHtml(booking.total_quantity || 0)} item | Payment: ${escapeHtml(booking.payment_status || 'pending')}</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <strong class="block text-xl font-black text-[#061f4f]">${currency(booking.total_amount)}</strong>
                        <a href="/sewaaja/customer-booking-detail?id=${encodeURIComponent(booking.id)}" class="mt-3 inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-black text-[#061f4f] hover:border-[#ff6a00] hover:text-[#ff6a00]">Detail</a>
                    </div>
                </div>
            </article>`;
    }

    function renderList(target, bookings) {
        target.innerHTML = bookings.length
            ? bookings.map(bookingCard).join('')
            : '<div class="rounded-lg border border-dashed border-slate-200 bg-white p-8 text-center text-sm font-semibold text-slate-500">Belum ada data rental.</div>';
    }

    function renderPagination(target, key, meta) {
        const totalPages = Math.max(1, meta.total_pages || 1);
        target.innerHTML = `
            <button class="rounded-full border border-slate-200 px-4 py-2 text-sm font-black text-[#061f4f] disabled:opacity-40" type="button" data-page-key="${key}" data-page="${state[key].page - 1}" ${state[key].page <= 1 ? 'disabled' : ''}>Sebelumnya</button>
            <span class="text-sm font-bold text-slate-500">Halaman ${meta.page} dari ${totalPages}</span>
            <button class="rounded-full border border-slate-200 px-4 py-2 text-sm font-black text-[#061f4f] disabled:opacity-40" type="button" data-page-key="${key}" data-page="${state[key].page + 1}" ${state[key].page >= totalPages ? 'disabled' : ''}>Berikutnya</button>
        `;
    }

    async function loadDashboard() {
        const data = await SewaAjaCustomerApi.dashboard();
        renderStats(data.widgets || {});
        renderList(elements.activeList, data.active_rentals || []);
        renderList(elements.historyList, data.recent_history || []);
    }

    async function loadRentals(key) {
        const data = await SewaAjaCustomerApi.rentals(state[key]);
        renderList(key === 'active' ? elements.activeList : elements.historyList, data.items || []);
        renderPagination(key === 'active' ? elements.activePagination : elements.historyPagination, key, data.meta || {});
    }

    async function loadProfile() {
        const payload = await SewaAjaCustomerApi.profile();
        renderProfile(payload.data.user);
    }

    function bindEvents() {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-page-key]');
            if (!button || button.disabled) {
                return;
            }
            const key = button.dataset.pageKey;
            state[key].page = Number(button.dataset.page);
            loadRentals(key).catch((error) => showAlert(error.message));
        });

        elements.profileForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const payload = await SewaAjaCustomerApi.updateProfile({
                    name: elements.profileName.value.trim(),
                    phone: elements.profilePhone.value.trim(),
                });
                const authState = SewaAjaAuth.getState();
                SewaAjaAuth.setState({ ...authState, user: payload.data.user });
                renderProfile(payload.data.user);
                showAlert('Profile berhasil diperbarui.', 'success');
            } catch (error) {
                showAlert(error.message || 'Profile gagal diperbarui.');
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
        renderProfile(session.user);
        bindEvents();
        try {
            await Promise.all([loadDashboard(), loadRentals('active'), loadRentals('history'), loadProfile()]);
        } catch (error) {
            showAlert(error.message || 'Dashboard customer gagal dimuat.');
        }
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', SewaAjaCustomerDashboard.init);
