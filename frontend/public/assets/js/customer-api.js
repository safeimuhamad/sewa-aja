const SewaAjaCustomerApi = (() => {
    const bookingBaseUrl = '/sewaaja/backend/services/booking-service/public/index.php';

    function requireCustomerSession() {
        const state = SewaAjaAuth.getState();
        const redirect = `${window.location.pathname}${window.location.search || ''}`;

        if (!state?.auth?.access_token || state?.user?.role !== 'customer') {
            window.location.href = `/sewaaja/login?redirect=${encodeURIComponent(redirect || '/sewaaja/customer-dashboard')}`;
            return null;
        }

        return state;
    }

    async function request(baseUrl, path, options = {}) {
        const token = SewaAjaAuth.token();
        const response = await fetch(`${baseUrl}${path}`, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${token}`,
                'X-Authorization': `Bearer ${token}`,
                ...(options.headers || {}),
            },
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

    function query(params) {
        const search = new URLSearchParams();
        Object.entries(params || {}).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                search.set(key, value);
            }
        });

        return search.toString() ? `?${search}` : '';
    }

    return {
        requireCustomerSession,
        profile: () => SewaAjaAuth.profile(),
        updateProfile: (data) => SewaAjaAuth.request('/profile', {
            method: 'PUT',
            body: JSON.stringify(data),
        }),
        dashboard: () => request(bookingBaseUrl, '/customer/dashboard'),
        rentals: (params) => request(bookingBaseUrl, `/customer/rentals${query(params)}`),
        booking: (id) => request(bookingBaseUrl, `/customer/bookings/${encodeURIComponent(id)}`),
        cancelBooking: (id) => request(bookingBaseUrl, `/customer/bookings/${encodeURIComponent(id)}/cancel`, {
            method: 'PUT',
        }),
        invoice: (id) => request(bookingBaseUrl, `/customer/bookings/${encodeURIComponent(id)}/invoice`),
    };
})();
