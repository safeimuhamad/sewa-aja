const ProductCatalog = (() => {
    const apiBaseUrl = '/sewaaja/backend/services/product-service/public/index.php';
    const state = {
        q: '',
        category: '',
        location: '',
        min_price: '',
        max_price: '',
        sort: 'newest',
        page: 1,
        per_page: 12,
    };

    const elements = {
        searchForm: document.getElementById('searchForm'),
        searchInput: document.getElementById('searchInput'),
        category: document.getElementById('categoryFilter'),
        location: document.getElementById('locationFilter'),
        minPrice: document.getElementById('minPriceFilter'),
        maxPrice: document.getElementById('maxPriceFilter'),
        sort: document.getElementById('sortFilter'),
        perPage: document.getElementById('perPageFilter'),
        grid: document.getElementById('productGrid'),
        pagination: document.getElementById('pagination'),
        resultCount: document.getElementById('resultCount'),
        activeFilters: document.getElementById('activeFilters'),
        reset: document.getElementById('resetFilters'),
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

    function categoryIcon(key) {
        const paths = {
            camera: '<path d="M4 7h3l1-2h8l1 2h3v11H4z"/><circle cx="12" cy="12" r="3"/>',
            car: '<path d="M5 13l2-5h10l2 5"/><path d="M5 13h14v5H5z"/><circle cx="8" cy="18" r="1"/><circle cx="16" cy="18" r="1"/>',
            party: '<path d="M5 19l4-14 10 10z"/><path d="M13 5l1-2M18 8l2-1M16 13l3 1"/>',
            monitor: '<rect x="4" y="5" width="16" height="11" rx="2"/><path d="M9 20h6M12 16v4"/>',
            wrench: '<path d="M14 6a4 4 0 0 0 5 5l-8 8-4-4z"/><path d="M7 15l2 2"/>',
            home: '<path d="M4 11l8-7 8 7"/><path d="M6 10v10h12V10"/>',
            tent: '<path d="M3 20l9-16 9 16z"/><path d="M12 4v16M9 20l3-7 3 7"/>',
            building: '<path d="M5 20V4h14v16"/><path d="M8 8h2M14 8h2M8 12h2M14 12h2M8 16h2M14 16h2"/>',
            music: '<path d="M9 18V5l10-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="16" cy="16" r="3"/>',
            leaf: '<path d="M5 19c10 0 14-7 14-14-8 0-14 4-14 14z"/><path d="M5 19c3-5 7-8 12-10"/>',
        };
        const path = paths[key] || '<path d="M4 7h16v12H4z"/><path d="M8 7V5h8v2"/>';
        return `<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;
    }

    function params() {
        const query = new URLSearchParams();
        Object.entries(state).forEach(([key, value]) => {
            if (value !== '') {
                query.set(key, value);
            }
        });
        return query.toString();
    }

    async function api(path) {
        const response = await fetch(`${apiBaseUrl}${path}`);
        const payload = await response.json();

        if (!response.ok || payload.success === false) {
            throw new Error(payload.message || 'Gagal memuat data.');
        }

        return payload.data;
    }

    function showLoading() {
        elements.grid.innerHTML = Array.from({ length: 6 }).map(() => (
            `<article class="product-card skeleton-card">
                <div class="product-image"></div>
                <div class="product-body">
                    <span></span>
                    <strong></strong>
                    <p></p>
                    <small></small>
                </div>
            </article>`
        )).join('');
    }

    function renderFilters(data) {
        data.categories.forEach((category) => {
            elements.category.insertAdjacentHTML(
                'beforeend',
                `<option value="${escapeHtml(category.slug)}">${escapeHtml(category.name)}</option>`
            );
        });

        data.locations.forEach((location) => {
            const label = [location.city, location.province].filter(Boolean).join(', ');
            elements.location.insertAdjacentHTML(
                'beforeend',
                `<option value="${escapeHtml(location.city)}">${escapeHtml(label)}</option>`
            );
        });
    }

    function imageMarkup(product) {
        if (product.primary_image) {
            return `<img src="${escapeHtml(product.primary_image)}" alt="${escapeHtml(product.name)}" loading="lazy" onerror="this.parentElement.classList.add('image-fallback'); this.remove();">`;
        }

        return '';
    }

    function renderProducts(products) {
        if (products.length === 0) {
            elements.grid.innerHTML = `
                <div class="empty-state">
                    <h2>Produk belum ditemukan</h2>
                    <p>Coba ubah kata kunci, lokasi, kategori, atau rentang harga.</p>
                </div>`;
            return;
        }

        elements.grid.innerHTML = products.map((product) => `
            <article class="product-card">
                <div class="product-image ${product.primary_image ? '' : 'image-fallback'}">
                    ${imageMarkup(product)}
                    <span>${categoryIcon(product.category_icon_key)} ${escapeHtml(product.category_name)}</span>
                </div>
                <div class="product-body">
                    <div class="product-meta">
                        <span>${escapeHtml(product.city || 'Lokasi belum diisi')}</span>
                        <span>Stok ${escapeHtml(product.stock_quantity)} ${escapeHtml(product.unit_label)}</span>
                    </div>
                    <h2>${escapeHtml(product.name)}</h2>
                    <p>${escapeHtml(product.description || 'Deskripsi produk belum tersedia.')}</p>
                    <div class="product-footer">
                        <strong>${currency(product.price_per_day)}<small>/hari</small></strong>
                        <a class="btn btn-outline-brand btn-sm" href="/sewaaja/product-detail?slug=${escapeHtml(product.slug)}">Detail</a>
                    </div>
                    <div class="vendor-name">${escapeHtml(product.store_name)}</div>
                </div>
            </article>
        `).join('');
    }

    function renderPagination(meta) {
        const totalPages = Math.max(1, meta.total_pages || 1);
        const current = meta.page || 1;
        const pages = [];
        const start = Math.max(1, current - 2);
        const end = Math.min(totalPages, current + 2);

        pages.push(pageItem('Sebelumnya', current - 1, current === 1));
        for (let page = start; page <= end; page += 1) {
            pages.push(pageItem(page, page, false, page === current));
        }
        pages.push(pageItem('Berikutnya', current + 1, current === totalPages));

        elements.pagination.innerHTML = pages.join('');
    }

    function pageItem(label, page, disabled = false, active = false) {
        return `
            <li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
                <button class="page-link" type="button" data-page="${page}" ${disabled ? 'disabled' : ''}>${label}</button>
            </li>`;
    }

    function renderMeta(meta) {
        elements.resultCount.textContent = `${meta.total} produk ditemukan`;
        const chips = [
            state.q ? `Cari: ${state.q}` : '',
            state.category ? `Kategori aktif` : '',
            state.location ? `Lokasi: ${state.location}` : '',
        ].filter(Boolean);
        elements.activeFilters.textContent = chips.length ? chips.join(' - ') : 'Semua produk aktif';
    }

    async function loadProducts() {
        showLoading();
        try {
            const data = await api(`/products?${params()}`);
            renderProducts(data.items);
            renderPagination(data.meta);
            renderMeta(data.meta);
        } catch (error) {
            elements.resultCount.textContent = 'Gagal memuat produk';
            elements.grid.innerHTML = `<div class="empty-state"><h2>API belum siap</h2><p>${error.message}</p></div>`;
            elements.pagination.innerHTML = '';
        }
    }

    function bindEvents() {
        elements.searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            state.q = elements.searchInput.value.trim();
            state.page = 1;
            loadProducts();
        });

        [elements.category, elements.location, elements.sort, elements.perPage].forEach((element) => {
            element.addEventListener('change', () => {
                state.category = elements.category.value;
                state.location = elements.location.value;
                state.sort = elements.sort.value;
                state.per_page = Number(elements.perPage.value);
                state.page = 1;
                loadProducts();
            });
        });

        [elements.minPrice, elements.maxPrice].forEach((element) => {
            element.addEventListener('input', debounce(() => {
                state.min_price = elements.minPrice.value;
                state.max_price = elements.maxPrice.value;
                state.page = 1;
                loadProducts();
            }, 450));
        });

        elements.pagination.addEventListener('click', (event) => {
            const button = event.target.closest('[data-page]');
            if (!button || button.disabled) {
                return;
            }
            state.page = Number(button.dataset.page);
            loadProducts();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        elements.reset.addEventListener('click', () => {
            state.q = '';
            state.category = '';
            state.location = '';
            state.min_price = '';
            state.max_price = '';
            state.sort = 'newest';
            state.page = 1;
            state.per_page = 12;
            elements.searchInput.value = '';
            elements.category.value = '';
            elements.location.value = '';
            elements.minPrice.value = '';
            elements.maxPrice.value = '';
            elements.sort.value = 'newest';
            elements.perPage.value = '12';
            loadProducts();
        });
    }

    function debounce(callback, delay) {
        let timer = null;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => callback(...args), delay);
        };
    }

    async function init() {
        bindEvents();
        showLoading();

        try {
            renderFilters(await api('/products/filters'));
        } catch (error) {
            console.warn(error.message);
        }

        loadProducts();
    }

    return { init };
})();

ProductCatalog.init();
