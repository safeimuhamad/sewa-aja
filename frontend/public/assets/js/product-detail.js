const ProductDetail = (() => {
    const apiBaseUrl = '/sewaaja/backend/services/product-service/public/index.php';
    const state = {
        product: null,
        availability: null,
    };

    const el = {
        alert: document.getElementById('detailAlert'),
        breadcrumb: document.getElementById('breadcrumbProduct'),
        mainGallery: document.getElementById('mainGallery'),
        thumbnails: document.getElementById('thumbnailList'),
        category: document.getElementById('productCategory'),
        name: document.getElementById('productName'),
        location: document.getElementById('productLocation'),
        price: document.getElementById('productPrice'),
        description: document.getElementById('productDescription'),
        stock: document.getElementById('productStock'),
        deposit: document.getElementById('productDeposit'),
        unitSummary: document.getElementById('unitSummary'),
        vendorTitle: document.getElementById('vendorTitle'),
        vendorDescription: document.getElementById('vendorDescription'),
        vendorLocation: document.getElementById('vendorLocation'),
        calendar: document.getElementById('availabilityCalendar'),
        reviews: document.getElementById('reviewList'),
        related: document.getElementById('relatedProducts'),
        form: document.getElementById('bookingForm'),
        startDate: document.getElementById('startDate'),
        endDate: document.getElementById('endDate'),
        quantity: document.getElementById('quantity'),
        duration: document.getElementById('priceDuration'),
        subtotal: document.getElementById('priceSubtotal'),
        priceDeposit: document.getElementById('priceDeposit'),
        total: document.getElementById('priceTotal'),
        bookingStatus: document.getElementById('bookingStatus'),
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

    async function api(path) {
        const response = await fetch(`${apiBaseUrl}${path}`);
        const payload = await response.json();
        if (!response.ok || payload.success === false) {
            throw new Error(payload.message || 'Request gagal.');
        }
        return payload.data;
    }

    function identifier() {
        const params = new URLSearchParams(window.location.search);
        return params.get('slug') || params.get('id') || 'kamera-mirrorless-sony-a6400';
    }

    function today(offset = 0) {
        const date = new Date();
        date.setDate(date.getDate() + offset);
        return date.toISOString().slice(0, 10);
    }

    function daysBetween(start, end) {
        const from = new Date(start);
        const to = new Date(end);
        const diff = Math.ceil((to - from) / 86400000) + 1;
        return Math.max(1, diff || 1);
    }

    function imageUrl(image) {
        return image?.image_url || 'assets/img/sewaaja-logo-wordmark.webp';
    }

    function renderGallery(product) {
        const images = product.images?.length ? product.images : [
            { image_url: 'assets/img/sewaaja-logo-wordmark.webp', alt_text: product.name },
        ];

        setMainImage(images[0], product.name);
        el.thumbnails.innerHTML = images.map((image, index) => `
            <button type="button" class="aspect-square overflow-hidden rounded-lg border border-slate-200 bg-white p-1 transition hover:border-[#ff6a00]" data-image-index="${index}">
                <img src="${escapeHtml(imageUrl(image))}" alt="${escapeHtml(image.alt_text || product.name)}" class="h-full w-full rounded-md object-contain" onerror="this.src='assets/img/sewaaja-logo-wordmark.webp'">
            </button>
        `).join('');

        el.thumbnails.addEventListener('click', (event) => {
            const button = event.target.closest('[data-image-index]');
            if (!button) {
                return;
            }
            setMainImage(images[Number(button.dataset.imageIndex)], product.name);
        }, { once: false });
    }

    function setMainImage(image, fallbackAlt) {
        el.mainGallery.innerHTML = `
            <img src="${escapeHtml(imageUrl(image))}" alt="${escapeHtml(image?.alt_text || fallbackAlt)}" class="h-full w-full rounded-lg bg-white object-contain object-center" onerror="this.src='assets/img/sewaaja-logo-wordmark.webp'">
        `;
    }

    function renderProduct(data) {
        const product = data.product;
        state.product = product;
        if (window.SewaAjaSeo) {
            window.SewaAjaSeo.product(product);
        } else {
            document.title = `${product.name} - SewaAja`;
        }
        el.breadcrumb.textContent = product.name;
        el.category.textContent = product.category_name;
        el.name.textContent = product.name;
        el.location.textContent = [product.city, product.province].filter(Boolean).join(', ') || 'Lokasi belum diisi';
        el.price.textContent = `${currency(product.price_per_day)}/hari`;
        el.description.textContent = product.description || 'Deskripsi produk belum tersedia.';
        el.stock.textContent = `${product.stock_quantity} ${product.unit_label}`;
        el.deposit.textContent = currency(product.deposit_amount);
        el.unitSummary.textContent = `${product.units?.length || 0} unit`;
        el.vendorTitle.textContent = product.store_name;
        el.vendorDescription.textContent = product.vendor_description || 'Vendor terpercaya di SewaAja.';
        el.vendorLocation.textContent = [product.city, product.province, product.postal_code].filter(Boolean).join(', ') || 'Lokasi belum diisi';
        renderGallery(product);
        renderReviews(data.reviews?.items || data.reviews || []);
        renderRelated(product.related_products || []);
        calculatePrice();
    }

    function renderReviews(reviews) {
        el.reviews.innerHTML = reviews.map((review) => `
            <article class="rounded-lg border border-slate-100 p-4">
                <div class="flex items-center justify-between gap-3">
                    <strong class="font-black text-[#061f4f]">${escapeHtml(review.customer_name || review.name || 'Customer SewaAja')}</strong>
                    <span class="rounded-full bg-[#fff7f1] px-3 py-1 text-sm font-black text-[#ff6a00]">${'★'.repeat(review.rating)}</span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600">${escapeHtml(review.comment)}</p>
                <time class="mt-3 block text-xs font-bold text-slate-400">${escapeHtml(review.created_at)}</time>
            </article>
        `).join('');
    }

    function renderRelated(products) {
        if (!products.length) {
            el.related.innerHTML = '<div class="rounded-lg border border-slate-100 bg-white p-6 text-sm font-bold text-slate-500">Belum ada produk terkait.</div>';
            return;
        }

        el.related.innerHTML = products.map((product) => `
            <a href="/sewaaja/product-detail?slug=${escapeHtml(product.slug)}" class="overflow-hidden rounded-lg border border-slate-100 bg-white shadow-[0_16px_40px_rgba(6,31,79,0.08)] transition hover:-translate-y-1">
                <div class="aspect-[4/3] bg-gradient-to-br from-[#061f4f] to-[#0b2f68] p-4">
                    <img src="${escapeHtml(product.primary_image || 'assets/img/sewaaja-logo-wordmark.webp')}" alt="${escapeHtml(product.name)}" class="h-full w-full rounded-lg bg-white object-contain" onerror="this.src='assets/img/sewaaja-logo-wordmark.webp'">
                </div>
                <div class="p-4">
                    <p class="text-xs font-bold text-slate-500">${escapeHtml(product.city || 'Lokasi')}</p>
                    <h3 class="mt-2 min-h-12 font-black leading-tight text-[#061f4f]">${escapeHtml(product.name)}</h3>
                    <strong class="mt-3 block text-[#ff6a00]">${currency(product.price_per_day)}/hari</strong>
                </div>
            </a>
        `).join('');
    }

    function renderAvailability(availability) {
        state.availability = availability;
        if (window.SewaAjaAvailabilityCalendar) {
            window.SewaAjaAvailabilityCalendar.render(el.calendar, availability, {
                startDate: el.startDate.value,
                endDate: el.endDate.value,
            });
        } else {
            el.calendar.innerHTML = availability.days.map((day) => `
                <div class="rounded-lg border p-3 ${day.is_available ? 'border-emerald-100 bg-emerald-50 text-emerald-800' : 'border-red-100 bg-red-50 text-red-700'}">
                    <time class="block text-xs font-black">${day.date.slice(5)}</time>
                    <span class="mt-1 block text-sm font-bold">${day.available_quantity} tersedia</span>
                </div>
            `).join('');
        }
        updateBookingStatus();
    }

    function calculatePrice() {
        if (!state.product) {
            return;
        }

        const duration = daysBetween(el.startDate.value, el.endDate.value);
        const quantity = Math.max(1, Number(el.quantity.value || 1));
        const subtotal = Number(state.product.price_per_day) * duration * quantity;
        const deposit = Number(state.product.deposit_amount) * quantity;
        el.duration.textContent = `${duration} hari`;
        el.subtotal.textContent = currency(subtotal);
        el.priceDeposit.textContent = currency(deposit);
        el.total.textContent = currency(subtotal + deposit);
        updateBookingStatus();
    }

    function updateBookingStatus() {
        if (!state.availability) {
            return;
        }

        const quantity = Math.max(1, Number(el.quantity.value || 1));
        const validation = window.SewaAjaAvailabilityCalendar
            ? window.SewaAjaAvailabilityCalendar.validateRange(state.availability, el.startDate.value, el.endDate.value, quantity)
            : { valid: false, message: 'Availability belum siap.' };
        const isAvailable = validation.valid;
        el.bookingStatus.className = `mt-4 rounded-lg p-3 text-sm font-bold ${isAvailable ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700'}`;
        el.bookingStatus.textContent = validation.message;
    }

    async function loadAvailability() {
        if (!state.product) {
            return;
        }

        const params = new URLSearchParams({
            start_date: el.startDate.value,
            end_date: el.endDate.value,
        });
        renderAvailability(await api(`/products/${state.product.slug}/availability?${params}`));
    }

    function bindEvents() {
        [el.startDate, el.endDate].forEach((input) => {
            input.addEventListener('change', async () => {
                if (el.endDate.value < el.startDate.value) {
                    el.endDate.value = el.startDate.value;
                }
                calculatePrice();
                await loadAvailability();
            });
        });

        el.quantity.addEventListener('input', calculatePrice);
        el.form.addEventListener('submit', (event) => {
            event.preventDefault();
            addToCart();
            window.location.href = '/sewaaja/checkout';
        });
    }

    function addToCart() {
        const cartKey = 'sewaaja.cart';
        const cart = JSON.parse(localStorage.getItem(cartKey) || '[]');
        const item = {
            product_id: state.product.id,
            slug: state.product.slug,
            name: state.product.name,
            vendor: state.product.store_name,
            price_per_day: Number(state.product.price_per_day),
            deposit_amount: Number(state.product.deposit_amount),
            quantity: Math.max(1, Number(el.quantity.value || 1)),
            start_date: el.startDate.value,
            end_date: el.endDate.value,
        };
        const existingIndex = cart.findIndex((entry) => (
            entry.product_id === item.product_id
            && entry.start_date === item.start_date
            && entry.end_date === item.end_date
        ));

        if (existingIndex >= 0) {
            cart[existingIndex].quantity += item.quantity;
        } else {
            cart.push(item);
        }

        localStorage.setItem(cartKey, JSON.stringify(cart));
    }

    async function init() {
        el.startDate.value = today(1);
        el.endDate.value = today(2);
        el.startDate.min = today();
        el.endDate.min = today();
        bindEvents();

        try {
            const data = await api(`/products/${identifier()}`);
            renderProduct(data);
            await loadAvailability();
        } catch (error) {
            el.alert.classList.remove('hidden');
            el.alert.textContent = error.message;
        }
    }

    return { init };
})();

ProductDetail.init();
