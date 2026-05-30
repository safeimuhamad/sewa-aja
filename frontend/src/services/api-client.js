const API_BASE_URL = '../../backend/api-gateway/public';

export async function apiRequest(path, options = {}) {
    const response = await fetch(`${API_BASE_URL}${path}`, {
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
        ...options,
    });

    if (!response.ok) {
        throw new Error(`API request failed with status ${response.status}`);
    }

    return response.json();
}

