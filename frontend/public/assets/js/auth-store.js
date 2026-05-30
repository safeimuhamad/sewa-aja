const SewaAjaAuth = (() => {
    const storageKey = 'sewaaja.auth';
    const apiBaseUrl = '/sewaaja/backend/services/auth-service/public/index.php';

    function getState() {
        try {
            return JSON.parse(localStorage.getItem(storageKey)) || null;
        } catch (error) {
            return null;
        }
    }

    function setState(state) {
        localStorage.setItem(storageKey, JSON.stringify(state));
    }

    function clearState() {
        localStorage.removeItem(storageKey);
    }

    function token() {
        return getState()?.auth?.access_token || null;
    }

    async function request(path, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        };

        const accessToken = token();
        if (accessToken) {
            headers.Authorization = `Bearer ${accessToken}`;
            headers['X-Authorization'] = `Bearer ${accessToken}`;
        }

        const response = await fetch(`${apiBaseUrl}${path}`, {
            ...options,
            headers,
        });
        const payload = await response.json().catch(() => ({
            success: false,
            message: 'Response API tidak valid.',
            errors: null,
        }));

        if (!response.ok || payload.success === false) {
            const error = new Error(payload.message || 'Request gagal.');
            error.payload = payload;
            error.status = response.status;
            throw error;
        }

        return payload;
    }

    async function login(credentials) {
        const payload = await request('/login', {
            method: 'POST',
            body: JSON.stringify(credentials),
        });
        setState(payload.data);
        return payload;
    }

    async function register(data) {
        const role = data.role || 'customer';
        const endpoint = role === 'vendor' ? '/register/vendor' : '/register/customer';
        const payload = await request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
        });
        setState(payload.data);
        return payload;
    }

    async function forgotPassword(email) {
        return request('/forgot-password', {
            method: 'POST',
            body: JSON.stringify({ email }),
        });
    }

    async function profile() {
        return request('/profile');
    }

    async function logout() {
        try {
            await request('/logout', { method: 'POST' });
        } finally {
            clearState();
        }
    }

    return {
        getState,
        setState,
        clearState,
        token,
        request,
        login,
        register,
        forgotPassword,
        profile,
        logout,
    };
})();
