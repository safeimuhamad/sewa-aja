const SewaAjaVendorApi = (() => {
    const productBaseUrl = '/sewaaja/backend/services/product-service/public/index.php';
    const bookingBaseUrl = '/sewaaja/backend/services/booking-service/public/index.php';

    function authState() {
        return SewaAjaAuth.getState();
    }

    function token() {
        return SewaAjaAuth.token();
    }

    function requireVendorSession() {
        const state = authState();
        const redirect = `${window.location.pathname}${window.location.search || ''}`;

        if (!state?.auth?.access_token || state?.user?.role !== 'vendor') {
            window.location.href = `/sewaaja/login?redirect=${encodeURIComponent(redirect || '/sewaaja/vendor-dashboard')}`;
            return null;
        }

        return state;
    }

    async function request(baseUrl, path, options = {}) {
        const isFormData = options.body instanceof FormData;
        const headers = {
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
            ...(options.headers || {}),
        };

        const accessToken = token();
        if (accessToken) {
            headers.Authorization = `Bearer ${accessToken}`;
            headers['X-Authorization'] = `Bearer ${accessToken}`;
        }

        const response = await fetch(`${baseUrl}${path}`, {
            ...options,
            headers,
        });
        const payload = await response.json().catch(() => ({
            success: false,
            message: 'Response API tidak valid.',
        }));

        if (!response.ok || payload.success === false) {
            const error = new Error(payload.message || 'Request gagal.');
            error.payload = payload;
            error.status = response.status;
            throw error;
        }

        return payload.data;
    }

    return {
        productBaseUrl,
        bookingBaseUrl,
        requireVendorSession,
        products: () => request(productBaseUrl, '/vendor/products'),
        createProduct: (data) => request(productBaseUrl, '/vendor/products', {
            method: 'POST',
            body: JSON.stringify(data),
        }),
        updateProduct: (id, data) => request(productBaseUrl, `/vendor/products/${encodeURIComponent(id)}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        }),
        deleteProduct: (id) => request(productBaseUrl, `/vendor/products/${encodeURIComponent(id)}`, {
            method: 'DELETE',
        }),
        addProductImage: (id, data) => request(productBaseUrl, `/vendor/products/${encodeURIComponent(id)}/images`, {
            method: 'POST',
            body: JSON.stringify(data),
        }),
        uploadProductMedia: (id, file, data = {}) => {
            const formData = new FormData();
            formData.append('image', file);
            Object.entries(data).forEach(([key, value]) => formData.append(key, value));

            return request(productBaseUrl, `/vendor/products/${encodeURIComponent(id)}/media`, {
                method: 'POST',
                body: formData,
            });
        },
        sortProductImages: (id, images) => request(productBaseUrl, `/vendor/products/${encodeURIComponent(id)}/images/sort`, {
            method: 'PUT',
            body: JSON.stringify({ images }),
        }),
        blockAvailability: (id, data) => request(productBaseUrl, `/vendor/products/${encodeURIComponent(id)}/availability-blocks`, {
            method: 'POST',
            body: JSON.stringify(data),
        }),
        updateInventory: (id, data) => request(productBaseUrl, `/vendor/products/${encodeURIComponent(id)}/inventory`, {
            method: 'PUT',
            body: JSON.stringify(data),
        }),
        finance: () => request(bookingBaseUrl, '/vendor/finance'),
        dashboard: () => request(bookingBaseUrl, '/vendor/dashboard'),
        bookings: () => request(bookingBaseUrl, '/vendor/bookings'),
        updateBookingStatus: (id, status) => request(bookingBaseUrl, `/vendor/bookings/${encodeURIComponent(id)}/status`, {
            method: 'PUT',
            body: JSON.stringify({ status }),
        }),
    };
})();
