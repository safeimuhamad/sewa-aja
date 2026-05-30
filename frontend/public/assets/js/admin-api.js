const SewaAjaAdminApi = (() => {
    const baseUrl = '/sewaaja/backend/services/admin-service/public/index.php';

    function requireAdminSession() {
        const state = SewaAjaAuth.getState();
        const redirect = `${window.location.pathname}${window.location.search || ''}`;

        if (!state?.auth?.access_token || state?.user?.role !== 'admin') {
            window.location.href = `/sewaaja/login?redirect=${encodeURIComponent(redirect || '/sewaaja/admin-dashboard')}`;
            return null;
        }

        return state;
    }

    async function request(path, options = {}) {
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
        requireAdminSession,
        dashboard: () => request('/dashboard'),
        list: (resource, params) => request(`/${resource}${query(params)}`),
        save: (resource, data, id = '') => request(`/${resource}${id ? `/${encodeURIComponent(id)}` : ''}`, {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify(data),
        }),
        remove: (resource, id) => request(`/${resource}/${encodeURIComponent(id)}`, {
            method: 'DELETE',
        }),
        updateStatus: (resource, id, status) => request(`/${resource}/${encodeURIComponent(id)}/status`, {
            method: 'PUT',
            body: JSON.stringify({ status }),
        }),
    };
})();
