const homepageData = {
    categories: [
        { name: { id: 'Kamera & Fotografi', en: 'Camera & Photography' }, slug: 'kamera-fotografi', icon_key: 'camera', count: { id: '420 item', en: '420 items' }, accent: { id: 'Kamera, lensa, lighting', en: 'Cameras, lenses, lighting' } },
        { name: { id: 'Perlengkapan Event', en: 'Event Equipment' }, slug: 'perlengkapan-event', icon_key: 'party', count: { id: '310 item', en: '310 items' }, accent: { id: 'Tenda, kursi, panggung', en: 'Tents, chairs, stages' } },
        { name: { id: 'Kendaraan', en: 'Vehicles' }, slug: 'kendaraan', icon_key: 'car', count: { id: '180 item', en: '180 items' }, accent: { id: 'Mobil, motor, sepeda', en: 'Cars, bikes, bicycles' } },
        { name: { id: 'Perkakas & Mesin', en: 'Tools & Machines' }, slug: 'perkakas-mesin', icon_key: 'wrench', count: { id: '260 item', en: '260 items' }, accent: { id: 'Bor, genset, alat proyek', en: 'Drills, gensets, project tools' } },
    ],
    products: [
        { name: 'Sony A6400 Kit', slug: 'kamera-mirrorless-sony-a6400', category: 'Elektronik', price: 175000, location: 'Jakarta Selatan', stock: 2 },
        { name: 'Paket Kursi Event 50 Pcs', slug: 'paket-kursi-event-50-pcs', category: 'Event', price: 300000, location: 'Jakarta Selatan', stock: 3 },
        { name: 'Sound System 1000 Watt', slug: 'kamera-mirrorless-sony-a6400', category: 'Event', price: 450000, location: 'Bandung', stock: 5 },
        { name: 'Proyektor Meeting Full HD', slug: 'kamera-mirrorless-sony-a6400', category: 'Elektronik', price: 125000, location: 'Surabaya', stock: 7 },
    ],
    vendors: [
        { name: 'Budi Event Rental', location: 'Jakarta Selatan', rating: '4.9', products: 86 },
        { name: 'KameraKita Studio', location: 'Bandung', rating: '4.8', products: 42 },
        { name: 'Urban Tools Rent', location: 'Surabaya', rating: '4.7', products: 58 },
    ],
    slides: [
        {
            eyebrow: { id: 'Rekomendasi minggu ini', en: 'This week recommendation' },
            title: { id: 'Marketplace sewa untuk kebutuhan harian, event, dan bisnis.', en: 'Rental marketplace for daily needs, events, and business.' },
            description: { id: 'Temukan barang sewaan dari vendor terpercaya, bandingkan harga, cek lokasi, lalu booking dari satu tempat yang rapi.', en: 'Find rentals from trusted vendors, compare prices, check locations, and book from one polished flow.' },
            price: { id: 'Mulai Rp175K/hari', en: 'From IDR 175K/day' },
            badge: { id: 'Apapun, SewaAja', en: 'Anything, SewaAja' },
            image: 'assets/img/sewaaja-logo-wordmark.webp',
            href: '/sewaaja/product-detail?slug=kamera-mirrorless-sony-a6400',
        },
        {
            eyebrow: { id: 'Vendor populer', en: 'Popular vendors' },
            title: { id: 'Perlengkapan event untuk acara besar', en: 'Event equipment for bigger moments' },
            description: { id: 'Kursi, tenda, audio, dan kebutuhan acara dari vendor terpercaya di kotamu.', en: 'Chairs, tents, audio, and event essentials from trusted vendors in your city.' },
            price: { id: 'Paket mulai Rp300K', en: 'Packages from IDR 300K' },
            badge: { id: 'Event', en: 'Event' },
            image: 'assets/img/sewaaja-logo-wordmark.webp',
            href: '/sewaaja/products?category=event',
        },
        {
            eyebrow: { id: 'Sewa cepat', en: 'Fast rental' },
            title: { id: 'Cari barang terdekat dari lokasimu', en: 'Find rentals closest to your location' },
            description: { id: 'Filter produk berdasarkan kota, harga, ketersediaan, dan coverage vendor.', en: 'Filter products by city, price, availability, and vendor coverage area.' },
            price: { id: '18 kota layanan', en: '18 service cities' },
            badge: { id: 'Nearby', en: 'Nearby' },
            image: 'assets/img/sewaaja-logo-wordmark.webp',
            href: '/sewaaja/products',
        },
    ],
};

const sliderState = {
    index: 0,
    timer: null,
};
const langKey = 'sewaaja.lang';
const productApiBaseUrl = '/sewaaja/backend/services/product-service/public/index.php';

const translations = {
    id: {
        pageTitle: 'SewaAja - Apapun, SewaAja',
        pageDescription: 'SewaAja adalah marketplace sewa untuk menemukan barang, perlengkapan event, elektronik, kendaraan, dan kebutuhan harian dari vendor terpercaya.',
        nav: { catalog: 'Katalog', categories: 'Kategori', vendors: 'Vendor', how: 'Cara Sewa', login: 'Masuk', register: 'Daftar', dashboard: 'Dashboard', logout: 'Keluar', openMenu: 'Buka menu' },
        search: { what: 'Mau sewa apa?', placeholder: 'Cari kamera, tenda, mobil...', category: 'Kategori', location: 'Lokasi', button: 'Cari Sekarang' },
        common: { all: 'Semua' },
        category: { electronics: 'Elektronik', transport: 'Transportasi' },
        metrics: { products: 'produk siap disewa', vendors: 'vendor aktif terverifikasi', cities: 'kota layanan rental' },
        sections: {
            categories: { eyebrow: 'Kategori', title: 'Mulai dari kebutuhanmu', link: 'Lihat semua kategori' },
            featured: { eyebrow: 'Produk unggulan', title: 'Sering dicari minggu ini', link: 'Jelajah katalog' },
            vendors: { eyebrow: 'Vendor populer', title: 'Partner rental terpercaya', copy: 'Vendor dengan katalog aktif, response cepat, dan rating tinggi di komunitas SewaAja.' },
            how: { eyebrow: 'Cara sewa', title: 'Dari cari barang sampai dipakai, alurnya singkat.' },
        },
        how: {
            search: { title: 'Cari', copy: 'Pilih kategori, lokasi, harga, dan vendor yang paling cocok.' },
            booking: { title: 'Booking', copy: 'Ajukan jadwal sewa dan tunggu konfirmasi ketersediaan barang.' },
            use: { title: 'Pakai', copy: 'Ambil atau kirim barang sesuai kesepakatan dengan vendor.' },
        },
        cta: { eyebrow: 'Gabung sekarang', title: 'Punya barang produktif? Sewakan di SewaAja.', copy: 'Bangun toko rental digital, kelola produk, dan jangkau customer yang memang sedang mencari barang sewaan.', vendor: 'Daftar Vendor', catalog: 'Lihat Katalog' },
        footer: { copy: 'SewaAja membantu customer menemukan barang sewaan dan membantu vendor mengelola katalog rental secara modern.', account: 'Akun', registerCustomer: 'Daftar Customer', registerVendor: 'Daftar Vendor', contact: 'Kontak', rights: '© 2026 SewaAja. Semua hak dilindungi.' },
        cards: { view: 'Lihat', detail: 'Detail', stock: 'Stok', perDay: '/hari', activeProducts: 'produk aktif' },
        slider: { start: 'Mulai Sewa', vendor: 'Jadi Vendor', highlight: 'Highlight', realtime: 'Stok real-time' },
    },
    en: {
        pageTitle: 'SewaAja - Anything, Rent It',
        pageDescription: 'SewaAja is a rental marketplace for event equipment, electronics, vehicles, and daily needs from trusted vendors.',
        nav: { catalog: 'Catalog', categories: 'Categories', vendors: 'Vendors', how: 'How It Works', login: 'Login', register: 'Register', dashboard: 'Dashboard', logout: 'Logout', openMenu: 'Open menu' },
        search: { what: 'What do you want to rent?', placeholder: 'Search cameras, tents, cars...', category: 'Category', location: 'Location', button: 'Search Now' },
        common: { all: 'All' },
        category: { electronics: 'Electronics', transport: 'Transportation' },
        metrics: { products: 'rental-ready products', vendors: 'verified active vendors', cities: 'rental service cities' },
        sections: {
            categories: { eyebrow: 'Categories', title: 'Start from what you need', link: 'View all categories' },
            featured: { eyebrow: 'Featured products', title: 'Trending rentals this week', link: 'Explore catalog' },
            vendors: { eyebrow: 'Popular vendors', title: 'Trusted rental partners', copy: 'Vendors with active catalogs, fast response, and strong ratings in the SewaAja community.' },
            how: { eyebrow: 'How it works', title: 'From search to use, the flow is simple.' },
        },
        how: {
            search: { title: 'Search', copy: 'Choose category, location, price, and the most suitable vendor.' },
            booking: { title: 'Book', copy: 'Submit your rental schedule and wait for availability confirmation.' },
            use: { title: 'Use', copy: 'Pick up or receive the item based on your agreement with the vendor.' },
        },
        cta: { eyebrow: 'Join now', title: 'Have productive assets? Rent them on SewaAja.', copy: 'Build a digital rental store, manage products, and reach customers who are actively looking for rentals.', vendor: 'Register Vendor', catalog: 'View Catalog' },
        footer: { copy: 'SewaAja helps customers find rentals and helps vendors manage rental catalogs in a modern way.', account: 'Account', registerCustomer: 'Register Customer', registerVendor: 'Register Vendor', contact: 'Contact', rights: '© 2026 SewaAja. All rights reserved.' },
        cards: { view: 'View', detail: 'Details', stock: 'Stock', perDay: '/day', activeProducts: 'active products' },
        slider: { start: 'Start Renting', vendor: 'Become a Vendor', highlight: 'Highlight', realtime: 'Real-time stock' },
    },
};

if (window.location.pathname.endsWith('/frontend/public/index.html')) {
    window.history.replaceState(null, '', `/sewaaja/${window.location.hash || ''}`);
}

function currentLang() {
    const stored = localStorage.getItem(langKey);
    return stored === 'en' ? 'en' : 'id';
}

function trans(path) {
    return path.split('.').reduce((value, key) => value?.[key], translations[currentLang()]) ?? path;
}

function localized(value) {
    if (value && typeof value === 'object') {
        return value[currentLang()] ?? value.id ?? value.en ?? '';
    }

    return value ?? '';
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

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function dashboardForRole(role) {
    return {
        admin: { href: '/sewaaja/admin-dashboard', label: trans('nav.dashboard') },
        vendor: { href: '/sewaaja/vendor-dashboard', label: trans('nav.dashboard') },
        customer: { href: '/sewaaja/customer-dashboard', label: trans('nav.dashboard') },
    }[role] || null;
}

function syncNavigation() {
    const state = window.SewaAjaAuth?.getState?.();
    const dashboard = dashboardForRole(state?.user?.role);
    const desktopMenu = document.getElementById('desktopMenu');
    const desktopAuthMenu = document.getElementById('desktopAuthMenu');
    const mobileAuthMenu = document.getElementById('mobileAuthMenu');

    if (!desktopMenu || !desktopAuthMenu || !mobileAuthMenu) {
        return;
    }

    desktopMenu.querySelector('[data-role-dashboard]')?.remove();

    if (!dashboard) {
        desktopAuthMenu.innerHTML = `
            <a href="/sewaaja/login" class="rounded-full px-5 py-3 text-sm font-extrabold text-[#061f4f] transition hover:text-[#ff6a00]" data-i18n="nav.login">${trans('nav.login')}</a>
            <a href="/sewaaja/register" class="rounded-full bg-[#ff6a00] px-6 py-3 text-sm font-extrabold text-white shadow-[0_14px_28px_rgba(255,106,0,0.22)] transition hover:bg-[#e95d00]" data-i18n="nav.register">${trans('nav.register')}</a>
        `;
        mobileAuthMenu.className = 'grid grid-cols-2 gap-3 pt-2';
        mobileAuthMenu.innerHTML = `
            <a href="/sewaaja/login" class="rounded-full border border-slate-200 px-5 py-3 text-center text-sm font-extrabold text-[#061f4f]" data-i18n="nav.login">${trans('nav.login')}</a>
            <a href="/sewaaja/register" class="rounded-full bg-[#ff6a00] px-5 py-3 text-center text-sm font-extrabold text-white" data-i18n="nav.register">${trans('nav.register')}</a>
        `;
        return;
    }

    desktopMenu.insertAdjacentHTML('beforeend', `
        <a href="${dashboard.href}" class="text-sm font-bold text-[#061f4f] transition hover:text-[#ff6a00]" data-role-dashboard>${dashboard.label}</a>
    `);
    desktopAuthMenu.innerHTML = `
        <span class="max-w-40 truncate text-sm font-black text-[#061f4f]">${escapeHtml(state.user?.name || 'Akun')}</span>
        <button id="desktopLogoutButton" class="rounded-full bg-[#ff6a00] px-5 py-3 text-sm font-extrabold text-white shadow-[0_14px_28px_rgba(255,106,0,0.22)] transition hover:bg-[#e95d00]" type="button">${trans('nav.logout')}</button>
    `;
    mobileAuthMenu.className = 'grid gap-3 pt-2';
    mobileAuthMenu.innerHTML = `
        <a href="${dashboard.href}" class="rounded-lg bg-[#fff7f1] px-3 py-3 text-sm font-black text-[#061f4f]">${trans('nav.dashboard')}</a>
        <button id="mobileLogoutButton" class="rounded-full bg-[#ff6a00] px-5 py-3 text-center text-sm font-extrabold text-white" type="button">${trans('nav.logout')}</button>
    `;

    document.getElementById('desktopLogoutButton')?.addEventListener('click', logout);
    document.getElementById('mobileLogoutButton')?.addEventListener('click', logout);
}

async function logout() {
    await window.SewaAjaAuth?.logout?.();
    window.location.href = '/sewaaja/';
}

function categoryCard(category) {
    return `
        <a href="/sewaaja/products?category=${category.slug}" class="group rounded-lg border border-slate-100 bg-white p-5 shadow-[0_16px_40px_rgba(6,31,79,0.08)] transition hover:-translate-y-1 hover:border-[#ff6a00]/30">
            <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-lg bg-[#ff6a00]/10 text-[#ff6a00] ring-1 ring-[#ff6a00]/20">${categoryIcon(category.icon_key)}</div>
            <h3 class="text-xl font-black text-[#061f4f] group-hover:text-[#ff6a00]">${escapeHtml(localized(category.name))}</h3>
            <p class="mt-2 text-sm font-semibold text-slate-500">${escapeHtml(localized(category.accent))}</p>
            <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                <span class="text-sm font-black text-[#061f4f]">${escapeHtml(localized(category.count))}</span>
                <span class="text-sm font-black text-[#ff6a00]">${trans('cards.view')}</span>
            </div>
        </a>`;
}

function productCard(product) {
    return `
        <article class="overflow-hidden rounded-lg border border-slate-100 bg-white shadow-[0_16px_40px_rgba(6,31,79,0.08)]">
            <div class="relative aspect-[4/3] bg-gradient-to-br from-[#061f4f] to-[#0b2f68] p-5">
                <img src="assets/img/sewaaja-logo-wordmark.webp" alt="SewaAja" class="h-full w-full rounded-lg bg-white object-contain object-center opacity-95">
                <span class="absolute bottom-4 left-4 rounded-full bg-white px-3 py-1 text-xs font-black text-[#061f4f]">${product.category}</span>
            </div>
            <div class="p-5">
                <div class="flex items-center justify-between gap-3 text-xs font-bold text-slate-500">
                    <span>${product.location}</span>
                    <span>${trans('cards.stock')} ${product.stock}</span>
                </div>
                <h3 class="mt-3 min-h-14 text-lg font-black leading-tight text-[#061f4f]">${product.name}</h3>
                <div class="mt-5 flex items-center justify-between gap-3">
                    <strong class="text-lg font-black text-[#ff6a00]">${formatRupiah(product.price)}<span class="text-xs text-slate-500">${trans('cards.perDay')}</span></strong>
                    <a href="/sewaaja/product-detail?slug=${product.slug}" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-black text-[#061f4f] transition hover:border-[#ff6a00] hover:text-[#ff6a00]">${trans('cards.detail')}</a>
                </div>
            </div>
        </article>`;
}

function categoryIcon(key) {
    const paths = {
        camera: '<path d="M4 7h3l1-2h8l1 2h3v11H4z"/><circle cx="12" cy="12" r="3"/>',
        car: '<path d="M5 13l2-5h10l2 5"/><path d="M5 13h14v5H5z"/><circle cx="8" cy="18" r="1"/><circle cx="16" cy="18" r="1"/>',
        party: '<path d="M5 19l4-14 10 10z"/><path d="M13 5l1-2M18 8l2-1M16 13l3 1"/>',
        wrench: '<path d="M14 6a4 4 0 0 0 5 5l-8 8-4-4z"/><path d="M7 15l2 2"/>',
    };
    const path = paths[key] || '<path d="M4 7h16v12H4z"/><path d="M8 7V5h8v2"/>';
    return `<svg viewBox="0 0 24 24" aria-hidden="true" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;
}

function vendorCard(vendor) {
    return `
        <article class="rounded-lg border border-slate-100 bg-white p-6 shadow-[0_16px_40px_rgba(6,31,79,0.08)]">
            <div class="flex items-start justify-between gap-4">
                <div class="h-14 w-14 rounded-lg bg-gradient-to-br from-[#ff6a00] to-[#e95d00]"></div>
                <span class="rounded-full bg-[#fff7f1] px-3 py-1 text-sm font-black text-[#ff6a00]">${vendor.rating}</span>
            </div>
            <h3 class="mt-5 text-xl font-black text-[#061f4f]">${vendor.name}</h3>
            <p class="mt-2 text-sm font-semibold text-slate-500">${vendor.location}</p>
            <div class="mt-5 border-t border-slate-100 pt-4 text-sm font-black text-[#061f4f]">${vendor.products} ${trans('cards.activeProducts')}</div>
        </article>`;
}

function slideTemplate(slide, index) {
    return `
        <article class="${index === sliderState.index ? 'grid' : 'hidden'} min-h-[620px] overflow-hidden bg-[linear-gradient(120deg,#061f4f_0%,#0b2f68_48%,#fff7f1_48%,#fff_100%)] px-4 pb-32 pt-14 sm:min-h-[680px] sm:px-6 sm:pt-20 lg:min-h-[720px] lg:px-8" data-slide="${index}">
            <div class="mx-auto grid w-full max-w-7xl items-center gap-10 lg:grid-cols-[0.95fr_1.05fr]">
                <div class="max-w-3xl">
                    <span class="inline-flex rounded-full bg-white/95 px-4 py-2 text-xs font-black uppercase tracking-[0.14em] text-[#ff6a00]">${escapeHtml(localized(slide.badge))}</span>
                    <p class="mt-8 text-xs font-black uppercase tracking-[0.16em] text-white/75">${escapeHtml(localized(slide.eyebrow))}</p>
                    <h1 class="mt-3 text-4xl font-black leading-[0.96] text-white sm:text-6xl lg:text-7xl">${escapeHtml(localized(slide.title))}</h1>
                    <p class="mt-6 max-w-2xl text-base font-semibold leading-8 text-white/75 sm:text-lg">${escapeHtml(localized(slide.description))}</p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="${escapeHtml(slide.href)}" class="rounded-full bg-[#ff6a00] px-7 py-4 text-center text-sm font-black text-white shadow-[0_14px_28px_rgba(255,106,0,0.24)] transition hover:bg-[#e95d00]">${trans('slider.start')}</a>
                        <a href="/sewaaja/register?role=vendor" class="rounded-full border border-white/25 bg-white/10 px-7 py-4 text-center text-sm font-black text-white transition hover:bg-white hover:text-[#061f4f]">${trans('slider.vendor')}</a>
                    </div>
                </div>
                <div class="relative min-h-[280px] lg:min-h-[460px]">
                    <div class="absolute inset-x-6 top-10 h-64 rounded-lg bg-[#ff6a00]/20 blur-3xl lg:h-96"></div>
                    <div class="relative ml-auto max-w-xl rounded-lg border border-slate-100 bg-white p-5 shadow-[0_24px_70px_rgba(6,31,79,0.18)]">
                        <img src="${escapeHtml(slide.image)}" alt="${escapeHtml(slide.title)}" class="h-52 w-full rounded-lg bg-white object-contain sm:h-72 lg:h-80">
                        <div class="mt-5 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <span class="text-xs font-black uppercase tracking-[0.14em] text-[#ff6a00]">${trans('slider.highlight')}</span>
                                <strong class="mt-1 block text-2xl font-black text-[#061f4f]">${escapeHtml(localized(slide.price))}</strong>
                            </div>
                            <div class="rounded-lg bg-[#f8fbff] px-5 py-4 text-sm font-black text-[#061f4f]">
                                ${trans('slider.realtime')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    `;
}

function renderSlider() {
    const slideTarget = document.getElementById('homepageSlides');
    const dotsTarget = document.getElementById('sliderDots');

    if (!slideTarget || !dotsTarget) {
        return;
    }

    slideTarget.innerHTML = homepageData.slides.map(slideTemplate).join('');
    dotsTarget.innerHTML = homepageData.slides.map((slide, index) => `
        <button type="button" class="h-2.5 rounded-full transition ${index === sliderState.index ? 'w-8 bg-[#ff6a00]' : 'w-2.5 bg-white/70'}" data-slider-dot="${index}" aria-label="Tampilkan slide ${index + 1}"></button>
    `).join('');
}

function goToSlide(index) {
    sliderState.index = (index + homepageData.slides.length) % homepageData.slides.length;
    renderSlider();
}

function startSlider() {
    const slider = document.getElementById('homepageSlider');

    if (!slider || homepageData.slides.length <= 1) {
        return;
    }

    document.getElementById('sliderPrev')?.addEventListener('click', () => goToSlide(sliderState.index - 1));
    document.getElementById('sliderNext')?.addEventListener('click', () => goToSlide(sliderState.index + 1));
    document.getElementById('sliderDots')?.addEventListener('click', (event) => {
        const dot = event.target.closest('[data-slider-dot]');
        if (dot) {
            goToSlide(Number(dot.dataset.sliderDot));
        }
    });

    slider.addEventListener('mouseenter', () => clearInterval(sliderState.timer));
    slider.addEventListener('mouseleave', scheduleSlider);
    scheduleSlider();
}

function scheduleSlider() {
    clearInterval(sliderState.timer);
    sliderState.timer = setInterval(() => goToSlide(sliderState.index + 1), 5000);
}

function renderList(id, items, renderer) {
    const target = document.getElementById(id);
    if (!target) {
        return;
    }
    target.innerHTML = items.map(renderer).join('');
}

function applyStaticTranslations() {
    const lang = currentLang();

    document.documentElement.lang = lang;
    document.title = trans('pageTitle');
    document.querySelector('meta[name="description"]')?.setAttribute('content', trans('pageDescription'));
    document.querySelector('meta[property="og:title"]')?.setAttribute('content', trans('pageTitle'));
    document.querySelector('meta[property="og:description"]')?.setAttribute('content', trans('pageDescription'));

    document.querySelectorAll('[data-i18n]').forEach((element) => {
        element.textContent = trans(element.dataset.i18n);
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
        element.setAttribute('placeholder', trans(element.dataset.i18nPlaceholder));
    });

    document.querySelectorAll('[data-lang-option]').forEach((button) => {
        button.dataset.active = String(button.dataset.langOption === lang);
    });
}

function renderLocalizedContent() {
    applyStaticTranslations();
    renderList('categoryGrid', homepageData.categories, categoryCard);
    renderList('featuredGrid', homepageData.products, productCard);
    renderList('vendorGrid', homepageData.vendors, vendorCard);
    renderSlider();
    syncNavigation();
}

function setLanguage(lang) {
    localStorage.setItem(langKey, lang === 'en' ? 'en' : 'id');
    renderLocalizedContent();
    loadQuickFilters();
}

function bindLanguageSwitcher() {
    document.querySelectorAll('[data-lang-option]').forEach((button) => {
        button.addEventListener('click', () => setLanguage(button.dataset.langOption));
    });
}

async function loadQuickFilters() {
    const categorySelect = document.getElementById('quickCategory');
    const locationSelect = document.getElementById('quickLocation');

    if (!categorySelect || !locationSelect) {
        return;
    }

    try {
        const response = await fetch(`${productApiBaseUrl}/products/filters`);
        const payload = await response.json();
        if (!response.ok || payload.success === false) {
            return;
        }

        categorySelect.innerHTML = `<option value="">${trans('common.all')}</option>` + (payload.data.categories || []).map((category) => (
            `<option value="${escapeHtml(category.slug)}">${escapeHtml(category.name)}</option>`
        )).join('');
        locationSelect.innerHTML = `<option value="">${trans('common.all')}</option>` + (payload.data.locations || []).map((location) => {
            const label = [location.city, location.province].filter(Boolean).join(', ');
            return `<option value="${escapeHtml(location.city)}">${escapeHtml(label)}</option>`;
        }).join('');
    } catch (error) {
        console.warn(error.message);
    }
}

function bindHomepageActions() {
    const mobileButton = document.getElementById('mobileMenuButton');
    const mobileMenu = document.getElementById('mobileMenu');

    mobileButton?.addEventListener('click', () => {
        const isOpen = !mobileMenu.classList.contains('hidden');
        mobileMenu.classList.toggle('hidden', isOpen);
        mobileButton.setAttribute('aria-expanded', String(!isOpen));
    });

    document.getElementById('quickSearchButton')?.addEventListener('click', () => {
        const params = new URLSearchParams();
        const keyword = (document.getElementById('heroSearch')?.value || '').trim();
        const category = document.getElementById('quickCategory').value;
        const location = document.getElementById('quickLocation').value;

        if (keyword) {
            params.set('q', keyword);
        }
        if (category) {
            params.set('category', category);
        }
        if (location) {
            params.set('location', location);
        }

        window.location.href = `/sewaaja/products${params.toString() ? `?${params}` : ''}`;
    });
}

renderLocalizedContent();
startSlider();
loadQuickFilters();
bindLanguageSwitcher();
bindHomepageActions();
