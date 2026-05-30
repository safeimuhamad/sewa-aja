const apiBaseUrl = '../../backend/api-gateway/public';

async function checkGateway() {
    try {
        const response = await fetch(apiBaseUrl);
        return await response.json();
    } catch (error) {
        return { status: 'offline', message: error.message };
    }
}

