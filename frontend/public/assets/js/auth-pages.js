function formDataToObject(form) {
    return Object.fromEntries(new FormData(form).entries());
}

function showAlert(message, type = 'danger') {
    const alert = document.getElementById('formAlert');
    if (!alert) {
        return;
    }

    alert.className = `alert alert-${type}`;
    alert.textContent = message;
}

function clearErrors(form) {
    form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach((feedback) => {
        feedback.textContent = '';
    });
}

function showFieldErrors(form, errors = {}) {
    Object.entries(errors || {}).forEach(([name, messages]) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) {
            return;
        }

        field.classList.add('is-invalid');
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
        }
    });
}

function validateRequired(form, fields) {
    const errors = {};

    fields.forEach((fieldName) => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field && !field.value.trim()) {
            errors[fieldName] = ['Field wajib diisi.'];
        }
    });

    const email = form.querySelector('[name="email"]');
    if (email && email.value.trim() && !email.value.includes('@')) {
        errors.email = ['Format email tidak valid.'];
    }

    const password = form.querySelector('[name="password"]');
    if (password && password.value && password.value.length < 8) {
        errors.password = ['Minimal 8 karakter.'];
    }

    return errors;
}

function setButtonLoading(button, isLoading, text) {
    if (!button) {
        return;
    }

    if (isLoading) {
        button.dataset.originalText = button.textContent;
        button.disabled = true;
        button.textContent = text;
        return;
    }

    button.disabled = false;
    button.textContent = button.dataset.originalText || button.textContent;
}

function fallbackForRole(role) {
    return {
        admin: '/sewaaja/admin-dashboard',
        customer: '/sewaaja/customer-dashboard',
        vendor: '/sewaaja/vendor-dashboard',
    }[role] || '/sewaaja/';
}

function redirectForRole(redirect, role) {
    if (!redirect) {
        return fallbackForRole(role);
    }

    const isVendorPath = redirect.includes('/vendor-dashboard') || redirect.includes('/vendor-product-form');
    const isAdminPath = redirect.includes('/admin-dashboard');
    const isCustomerPath = redirect.includes('/customer-dashboard') || redirect.includes('/customer-booking-detail');

    if ((isVendorPath && role !== 'vendor') || (isAdminPath && role !== 'admin') || (isCustomerPath && role !== 'customer')) {
        return fallbackForRole(role);
    }

    if (redirect.startsWith('/sewaaja/')) {
        return redirect;
    }

    return `/sewaaja/${redirect.replace(/^\/+/, '')}`;
}

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(loginForm);
        const submit = loginForm.querySelector('[type="submit"]');
        const clientErrors = validateRequired(loginForm, ['email', 'password']);

        if (Object.keys(clientErrors).length > 0) {
            showFieldErrors(loginForm, clientErrors);
            showAlert('Lengkapi data login terlebih dahulu.');
            return;
        }

        setButtonLoading(submit, true, 'Memproses...');

        try {
            const payload = await SewaAjaAuth.login(formDataToObject(loginForm));
            const redirect = new URLSearchParams(window.location.search).get('redirect');
            const target = redirectForRole(redirect, payload.data?.user?.role);
            showAlert('Login berhasil. Mengarahkan...', 'success');
            setTimeout(() => {
                window.location.href = target;
            }, 700);
        } catch (error) {
            showAlert(error.payload?.message || error.message);
            showFieldErrors(loginForm, error.payload?.errors);
        } finally {
            setButtonLoading(submit, false);
        }
    });

    document.getElementById('forgotButton')?.addEventListener('click', async () => {
        const email = loginForm.querySelector('[name="email"]').value;
        clearErrors(loginForm);

        if (!email) {
            showFieldErrors(loginForm, { email: ['Isi email terlebih dahulu.'] });
            return;
        }

        try {
            const payload = await SewaAjaAuth.forgotPassword(email);
            const demoToken = payload.data?.demo_reset_token ? ` Demo token: ${payload.data.demo_reset_token}` : '';
            showAlert(`${payload.message}${demoToken}`, 'success');
        } catch (error) {
            showAlert(error.payload?.message || error.message);
            showFieldErrors(loginForm, error.payload?.errors);
        }
    });
}

const registerForm = document.getElementById('registerForm');
if (registerForm) {
    const vendorFields = document.getElementById('vendorFields');
    const roleInputs = registerForm.querySelectorAll('[name="role"]');

    function syncVendorFields() {
        const role = registerForm.querySelector('[name="role"]:checked')?.value || 'customer';
        vendorFields.classList.toggle('d-none', role !== 'vendor');
    }

    roleInputs.forEach((input) => input.addEventListener('change', syncVendorFields));

    const queryRole = new URLSearchParams(window.location.search).get('role');
    if (queryRole === 'vendor') {
        document.getElementById('roleVendor').checked = true;
    }
    syncVendorFields();

    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(registerForm);
        const submit = registerForm.querySelector('[type="submit"]');
        const data = formDataToObject(registerForm);
        const requiredFields = ['name', 'email', 'password'];

        if (data.role === 'vendor') {
            requiredFields.push('store_name');
        }

        const clientErrors = validateRequired(registerForm, requiredFields);

        if (Object.keys(clientErrors).length > 0) {
            showFieldErrors(registerForm, clientErrors);
            showAlert('Lengkapi data registrasi terlebih dahulu.');
            return;
        }

        setButtonLoading(submit, true, 'Memproses...');

        try {
            const payload = await SewaAjaAuth.register(data);
            const fallback = fallbackForRole(payload.data?.user?.role);
            showAlert('Registrasi berhasil. Mengarahkan...', 'success');
            setTimeout(() => {
                window.location.href = fallback;
            }, 700);
        } catch (error) {
            showAlert(error.payload?.message || error.message);
            showFieldErrors(registerForm, error.payload?.errors);
        } finally {
            setButtonLoading(submit, false);
        }
    });
}
