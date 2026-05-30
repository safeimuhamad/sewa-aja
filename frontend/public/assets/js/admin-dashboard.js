const SewaAjaAdminPanel = (() => {
    const resources = {
        vendors: {
            label: 'Vendor Approval',
            status: ['pending', 'active', 'suspended'],
            columns: ['store_name', 'owner_email', 'city', 'status', 'created_at'],
        },
        products: {
            label: 'Product Moderation',
            status: ['draft', 'active', 'inactive'],
            columns: ['name', 'store_name', 'category_name', 'price_per_day', 'status'],
        },
        bookings: {
            label: 'Booking Management',
            status: ['pending', 'confirmed', 'ongoing', 'completed', 'cancelled'],
            columns: ['booking_code', 'customer_name', 'store_name', 'total_amount', 'status'],
        },
        payments: {
            label: 'Payment Monitoring',
            status: ['pending', 'paid', 'failed', 'refunded', 'expired'],
            columns: ['payment_code', 'booking_code', 'method', 'amount', 'status'],
        },
        users: {
            label: 'User Management',
            status: ['active', 'inactive', 'suspended'],
            columns: ['name', 'email', 'role', 'status', 'created_at'],
        },
        categories: {
            label: 'CMS Kategori',
            status: ['active', 'inactive'],
            columns: ['name', 'slug', 'icon_key', 'is_active', 'sort_order'],
            cms: true,
        },
        locations: {
            label: 'CMS Kota',
            status: ['active', 'inactive'],
            columns: ['name', 'city', 'type', 'province', 'is_active'],
            cms: true,
        },
    };

    const state = {
        resource: 'vendors',
        page: 1,
        per_page: 10,
        q: '',
        status: '',
        items: [],
    };

    const elements = {
        adminName: document.getElementById('adminName'),
        stats: document.getElementById('adminStats'),
        sales: document.getElementById('adminSales'),
        tabs: document.getElementById('adminTabs'),
        tableTitle: document.getElementById('tableTitle'),
        tableHead: document.getElementById('tableHead'),
        tableBody: document.getElementById('tableBody'),
        pagination: document.getElementById('adminPagination'),
        search: document.getElementById('adminSearch'),
        status: document.getElementById('adminStatus'),
        perPage: document.getElementById('adminPerPage'),
        alert: document.getElementById('adminAlert'),
        logout: document.getElementById('logoutButton'),
        cmsForm: document.getElementById('cmsForm'),
        cmsId: document.getElementById('cmsId'),
        cmsName: document.getElementById('cmsName'),
        cmsSlug: document.getElementById('cmsSlug'),
        cmsIcon: document.getElementById('cmsIcon'),
        cmsExtra: document.getElementById('cmsExtra'),
        cmsDescription: document.getElementById('cmsDescription'),
        cmsReset: document.getElementById('cmsReset'),
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

    function renderStats(data) {
        const widgets = data.widgets || {};
        const cards = [
            ['Total user', widgets.users || 0],
            ['Vendor pending', widgets.vendors_pending || 0],
            ['Produk aktif', widgets.products_active || 0],
            ['Booking aktif', widgets.bookings_active || 0],
            ['Payment pending', widgets.payments_pending || 0],
            ['Revenue paid', currency(widgets.revenue_paid || 0)],
        ];

        elements.stats.innerHTML = cards.map(([label, value]) => `
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-black text-slate-500">${escapeHtml(label)}</p>
                <strong class="mt-3 block text-2xl font-black text-[#061f4f]">${escapeHtml(value)}</strong>
            </article>
        `).join('');

        elements.sales.innerHTML = [
            ['Booking status', data.booking_status || []],
            ['Payment status', data.payment_status || []],
        ].map(([title, rows]) => `
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-[#061f4f]">${escapeHtml(title)}</h2>
                <div class="mt-3 grid gap-3">
                    ${rows.length ? rows.map((row) => `
                        <div class="flex items-center justify-between rounded-lg bg-[#f8fbff] px-4 py-3 text-sm font-bold">
                            <span class="text-slate-600">${escapeHtml(row.label)}</span>
                            <strong class="text-[#061f4f]">${escapeHtml(row.total)}</strong>
                        </div>
                    `).join('') : '<p class="text-sm font-semibold text-slate-500">Belum ada data.</p>'}
                </div>
            </section>
        `).join('');
    }

    function renderTabs() {
        elements.tabs.innerHTML = Object.entries(resources).map(([key, item]) => `
            <button class="rounded-full px-4 py-2 text-sm font-black ${state.resource === key ? 'bg-[#ff6a00] text-white' : 'bg-white text-[#061f4f] ring-1 ring-slate-200'}" type="button" data-resource="${key}">
                ${escapeHtml(item.label)}
            </button>
        `).join('');
    }

    function renderStatusOptions() {
        const options = resources[state.resource].status;
        elements.status.innerHTML = '<option value="">Semua status</option>' + options.map((status) => (
            `<option value="${escapeHtml(status)}">${escapeHtml(status)}</option>`
        )).join('');
        elements.status.value = state.status;
        syncCmsForm();
    }

    function formatCell(key, value) {
        if (key === 'is_active') {
            return Number(value) === 1 ? 'active' : 'inactive';
        }

        if (key.includes('amount') || key.includes('price')) {
            return currency(value);
        }

        return escapeHtml(value || '-');
    }

    function itemStatus(item) {
        return Number(item.is_active) === 1 ? 'active' : 'inactive';
    }

    function renderTable(data) {
        const resource = resources[state.resource];
        elements.tableTitle.textContent = resource.label;
        elements.tableHead.innerHTML = `
            <tr>
                ${resource.columns.map((column) => `<th class="px-4 py-3">${escapeHtml(column.replaceAll('_', ' '))}</th>`).join('')}
                <th class="px-4 py-3">${resource.cms ? 'Aksi' : 'Update status'}</th>
            </tr>`;

        if (!data.items.length) {
            elements.tableBody.innerHTML = `<tr><td colspan="${resource.columns.length + 1}" class="px-4 py-8 text-center text-sm font-semibold text-slate-500">Data belum tersedia.</td></tr>`;
            return;
        }

        elements.tableBody.innerHTML = data.items.map((item) => `
            <tr class="border-b border-slate-100">
                ${resource.columns.map((column) => `<td class="px-4 py-4 text-sm font-bold text-slate-600">${formatCell(column, item[column])}</td>`).join('')}
                <td class="px-4 py-4">
                    ${resource.cms ? `
                        <div class="flex flex-wrap gap-2">
                            <button class="rounded-full border border-slate-200 px-3 py-2 text-xs font-black text-[#061f4f]" type="button" data-edit-id="${escapeHtml(item.id)}">Edit</button>
                            <button class="rounded-full bg-rose-50 px-3 py-2 text-xs font-black text-rose-700" type="button" data-delete-id="${escapeHtml(item.id)}">Nonaktifkan</button>
                        </div>
                    ` : `
                        <select class="h-10 rounded-lg border border-slate-200 px-3 text-xs font-black text-[#061f4f]" data-status-id="${escapeHtml(item.id)}">
                            ${resource.status.map((status) => `<option value="${status}" ${status === item.status ? 'selected' : ''}>${status}</option>`).join('')}
                        </select>
                    `}
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(meta) {
        const totalPages = Math.max(1, meta.total_pages || 1);
        elements.pagination.innerHTML = `
            <button class="rounded-full border border-slate-200 px-4 py-2 text-sm font-black text-[#061f4f] disabled:opacity-40" type="button" data-page="${state.page - 1}" ${state.page <= 1 ? 'disabled' : ''}>Sebelumnya</button>
            <span class="text-sm font-bold text-slate-500">Halaman ${meta.page} dari ${totalPages} | ${meta.total} data</span>
            <button class="rounded-full border border-slate-200 px-4 py-2 text-sm font-black text-[#061f4f] disabled:opacity-40" type="button" data-page="${state.page + 1}" ${state.page >= totalPages ? 'disabled' : ''}>Berikutnya</button>
        `;
    }

    async function loadDashboard() {
        try {
            renderStats(await SewaAjaAdminApi.dashboard());
        } catch (error) {
            showAlert(error.message || 'Dashboard admin gagal dimuat.');
        }
    }

    async function loadList() {
        try {
            const data = await SewaAjaAdminApi.list(state.resource, state);
            state.items = data.items || [];
            renderTable(data);
            renderPagination(data.meta || { page: 1, total: 0, total_pages: 1 });
        } catch (error) {
            showAlert(error.message || 'Tabel admin gagal dimuat.');
        }
    }

    function syncCmsForm() {
        const resource = resources[state.resource];
        elements.cmsForm.classList.toggle('hidden', !resource.cms);
        if (!resource.cms) {
            return;
        }

        const isLocation = state.resource === 'locations';
        setLabel(elements.cmsName, isLocation ? 'Kota/Kabupaten' : 'Nama kategori');
        setLabel(elements.cmsSlug, isLocation ? 'Kode wilayah' : 'Slug');
        setLabel(elements.cmsIcon, isLocation ? 'Tipe' : 'Icon key');
        setLabel(elements.cmsExtra, isLocation ? 'Provinsi' : 'Urutan');
        elements.cmsDescription.parentElement.classList.toggle('hidden', isLocation);
        elements.cmsIcon.placeholder = isLocation ? 'Kota atau Kabupaten' : 'camera, car, tent...';
        elements.cmsExtra.placeholder = isLocation ? 'Jawa Barat' : '10';
    }

    function setLabel(input, text) {
        const node = [...input.parentElement.childNodes].find((child) => child.nodeType === Node.TEXT_NODE && child.textContent.trim());
        if (node) {
            node.textContent = `\n                            ${text}\n                            `;
        }
    }

    function resetCmsForm() {
        elements.cmsForm.reset();
        elements.cmsId.value = '';
        syncCmsForm();
    }

    function cmsPayload() {
        if (state.resource === 'locations') {
            const type = elements.cmsIcon.value.trim() || 'Kota';
            const city = elements.cmsName.value.replace(/^(Kota|Kabupaten)\s+/i, '').trim();
            return {
                region_code: elements.cmsSlug.value.trim(),
                city,
                name: `${type} ${city}`.trim(),
                type,
                province: elements.cmsExtra.value.trim(),
                is_active: true,
            };
        }

        return {
            name: elements.cmsName.value.trim(),
            slug: elements.cmsSlug.value.trim(),
            icon_key: elements.cmsIcon.value.trim() || 'box',
            sort_order: Number(elements.cmsExtra.value || 0),
            description: elements.cmsDescription.value.trim(),
            is_active: true,
        };
    }

    function fillCmsForm(item) {
        elements.cmsId.value = item.id;
        if (state.resource === 'locations') {
            elements.cmsName.value = item.city || '';
            elements.cmsSlug.value = item.region_code || '';
            elements.cmsIcon.value = item.type || 'Kota';
            elements.cmsExtra.value = item.province || '';
            elements.cmsDescription.value = '';
            return;
        }

        elements.cmsName.value = item.name || '';
        elements.cmsSlug.value = item.slug || '';
        elements.cmsIcon.value = item.icon_key || '';
        elements.cmsExtra.value = item.sort_order || 0;
        elements.cmsDescription.value = item.description || '';
    }

    function bindEvents() {
        elements.tabs.addEventListener('click', (event) => {
            const button = event.target.closest('[data-resource]');
            if (!button) {
                return;
            }
            state.resource = button.dataset.resource;
            state.page = 1;
            state.status = '';
            renderTabs();
            renderStatusOptions();
            resetCmsForm();
            loadList();
        });

        elements.search.addEventListener('input', debounce(() => {
            state.q = elements.search.value.trim();
            state.page = 1;
            loadList();
        }, 350));

        elements.status.addEventListener('change', () => {
            state.status = elements.status.value;
            state.page = 1;
            loadList();
        });

        elements.perPage.addEventListener('change', () => {
            state.per_page = Number(elements.perPage.value);
            state.page = 1;
            loadList();
        });

        elements.pagination.addEventListener('click', (event) => {
            const button = event.target.closest('[data-page]');
            if (!button || button.disabled) {
                return;
            }
            state.page = Number(button.dataset.page);
            loadList();
        });

        elements.tableBody.addEventListener('change', async (event) => {
            const select = event.target.closest('[data-status-id]');
            if (!select) {
                return;
            }
            select.disabled = true;
            try {
                await SewaAjaAdminApi.updateStatus(state.resource, select.dataset.statusId, select.value);
                showAlert('Status berhasil diperbarui.', 'success');
                await Promise.all([loadDashboard(), loadList()]);
            } catch (error) {
                showAlert(error.message || 'Status gagal diperbarui.');
            } finally {
                select.disabled = false;
            }
        });

        elements.tableBody.addEventListener('click', async (event) => {
            const edit = event.target.closest('[data-edit-id]');
            const remove = event.target.closest('[data-delete-id]');

            if (edit) {
                const item = state.items.find((entry) => entry.id === edit.dataset.editId);
                if (item) {
                    fillCmsForm(item);
                    elements.cmsName.focus();
                }
                return;
            }

            if (remove) {
                try {
                    await SewaAjaAdminApi.remove(state.resource, remove.dataset.deleteId);
                    showAlert('Data berhasil dinonaktifkan.', 'success');
                    resetCmsForm();
                    await Promise.all([loadDashboard(), loadList()]);
                } catch (error) {
                    showAlert(error.message || 'Data gagal dinonaktifkan.');
                }
            }
        });

        elements.cmsForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                await SewaAjaAdminApi.save(state.resource, cmsPayload(), elements.cmsId.value);
                showAlert('Data CMS berhasil disimpan.', 'success');
                resetCmsForm();
                await Promise.all([loadDashboard(), loadList()]);
            } catch (error) {
                showAlert(error.message || 'Data CMS gagal disimpan.');
            }
        });

        elements.cmsReset.addEventListener('click', resetCmsForm);

        elements.logout.addEventListener('click', async () => {
            await SewaAjaAuth.logout();
            window.location.href = '/sewaaja/login';
        });
    }

    function debounce(callback, delay) {
        let timer = null;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => callback(...args), delay);
        };
    }

    function init() {
        const session = SewaAjaAdminApi.requireAdminSession();
        if (!session) {
            return;
        }
        elements.adminName.textContent = session.user?.name || 'Admin SewaAja';
        bindEvents();
        renderTabs();
        renderStatusOptions();
        loadDashboard();
        loadList();
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', SewaAjaAdminPanel.init);
