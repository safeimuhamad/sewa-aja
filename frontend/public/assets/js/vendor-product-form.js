const VendorProductForm = (() => {
    const params = new URLSearchParams(window.location.search);
    const productId = params.get('id');
    let products = [];
    let pendingFiles = [];

    const elements = {
        title: document.getElementById('pageTitle'),
        subtitle: document.getElementById('pageSubtitle'),
        form: document.getElementById('productForm'),
        alert: document.getElementById('formAlert'),
        category: document.getElementById('category_id'),
        name: document.getElementById('name'),
        description: document.getElementById('description'),
        price: document.getElementById('price_per_day'),
        deposit: document.getElementById('deposit_amount'),
        stock: document.getElementById('stock_quantity'),
        unit: document.getElementById('unit_label'),
        status: document.getElementById('status'),
        imageUrl: document.getElementById('image_url'),
        isPrimary: document.getElementById('is_primary'),
        deleteButton: document.getElementById('deleteButton'),
        submitButton: document.getElementById('submitButton'),
        logout: document.getElementById('logoutButton'),
    };

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

    function payload() {
        return {
            category_id: elements.category.value,
            name: elements.name.value.trim(),
            description: elements.description.value.trim(),
            price_per_day: Number(elements.price.value || 0),
            deposit_amount: Number(elements.deposit.value || 0),
            stock_quantity: Number(elements.stock.value || 0),
            unit_label: elements.unit.value.trim() || 'unit',
            status: elements.status.value,
        };
    }

    function fillForm(product) {
        elements.category.value = product.category_id;
        elements.name.value = product.name;
        elements.description.value = product.description || '';
        elements.price.value = product.price_per_day;
        elements.deposit.value = product.deposit_amount;
        elements.stock.value = product.stock_quantity;
        elements.unit.value = product.unit_label || 'unit';
        elements.status.value = product.status;
    }

    function renderCategories(categories) {
        elements.category.innerHTML = '<option value="">Pilih kategori</option>' + categories.map((category) => (
            `<option value="${escapeHtml(category.id)}">${escapeHtml(category.name)}</option>`
        )).join('');
    }

    async function load() {
        const session = SewaAjaVendorApi.requireVendorSession();
        if (!session) {
            return;
        }

        try {
            const data = await SewaAjaVendorApi.products();
            products = data.products || [];
            renderCategories(data.categories || []);

            if (productId) {
                const product = products.find((item) => item.id === productId);
                if (!product) {
                    showAlert('Produk tidak ditemukan atau bukan milik vendor ini.');
                    return;
                }
                elements.title.textContent = 'Edit produk';
                elements.subtitle.textContent = 'Kelola detail, harga, stok, dan status produk sewa.';
                elements.submitButton.textContent = 'Simpan Perubahan';
                elements.deleteButton.hidden = false;
                fillForm(product);
            }
        } catch (error) {
            showAlert(error.message || 'Data produk gagal dimuat.');
        }
    }

    async function submit(event) {
        event.preventDefault();
        elements.submitButton.disabled = true;

        try {
            const data = productId
                ? await SewaAjaVendorApi.updateProduct(productId, payload())
                : await SewaAjaVendorApi.createProduct(payload());
            const id = productId || data.product.id;

            if (elements.imageUrl.value.trim()) {
                await SewaAjaVendorApi.addProductImage(id, {
                    image_url: elements.imageUrl.value.trim(),
                    alt_text: elements.name.value.trim(),
                    is_primary: elements.isPrimary.checked,
                });
            }

            for (const [index, file] of pendingFiles.entries()) {
                await SewaAjaVendorApi.uploadProductMedia(id, file, {
                    alt_text: elements.name.value.trim(),
                    sort_order: index,
                    is_primary: !elements.imageUrl.value.trim() && index === 0 && elements.isPrimary.checked ? '1' : '',
                });
            }

            await SewaAjaVendorApi.updateInventory(id, {
                stock_quantity: Number(elements.stock.value || 0),
            });

            showAlert('Produk berhasil disimpan.', 'success');
            window.setTimeout(() => {
                window.location.href = '/sewaaja/vendor-dashboard';
            }, 700);
        } catch (error) {
            const details = error.payload?.errors ? Object.values(error.payload.errors).flat().join(' ') : '';
            showAlert(`${error.message || 'Produk gagal disimpan.'} ${details}`.trim());
        } finally {
            elements.submitButton.disabled = false;
        }
    }

    async function remove() {
        if (!productId || !window.confirm('Hapus produk ini dari dashboard vendor?')) {
            return;
        }

        elements.deleteButton.disabled = true;
        try {
            await SewaAjaVendorApi.deleteProduct(productId);
            window.location.href = '/sewaaja/vendor-dashboard';
        } catch (error) {
            showAlert(error.message || 'Produk gagal dihapus.');
        } finally {
            elements.deleteButton.disabled = false;
        }
    }

    function bindEvents() {
        elements.form.addEventListener('submit', submit);
        elements.deleteButton.addEventListener('click', remove);
        elements.logout.addEventListener('click', async () => {
            await SewaAjaAuth.logout();
            window.location.href = '/sewaaja/login';
        });
    }

    function init() {
        bindEvents();
        if (window.SewaAjaMediaUploader) {
            SewaAjaMediaUploader.init({
                dropzone: '#mediaDropzone',
                input: '#mediaInput',
                preview: '#mediaPreview',
                upload: async (file) => {
                    pendingFiles.push(file);
                },
            });
        }
        load();
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', VendorProductForm.init);
