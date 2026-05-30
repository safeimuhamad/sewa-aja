const SewaAjaNotifications = (() => {
    const apiBaseUrl = '/sewaaja/backend/services/notification-service/public/index.php';

    function mount(selector = '[data-notification-root]') {
        const root = document.querySelector(selector);

        if (!root || !window.SewaAjaAuth?.token?.()) {
            return;
        }

        root.innerHTML = `
            <div class="relative">
                <button type="button" class="flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-[#061f4f]" data-notification-toggle aria-label="Notifikasi">
                    <span class="text-lg font-black">!</span>
                </button>
                <div class="absolute right-0 top-14 z-50 hidden w-80 overflow-hidden rounded-lg border border-slate-100 bg-white shadow-[0_24px_70px_rgba(6,31,79,0.14)]" data-notification-panel>
                    <div class="border-b border-slate-100 p-4">
                        <strong class="text-sm font-black text-[#061f4f]">Notifikasi</strong>
                    </div>
                    <div class="max-h-96 overflow-auto" data-notification-list>
                        <div class="p-4 text-sm font-bold text-slate-500">Memuat notifikasi...</div>
                    </div>
                </div>
            </div>
        `;

        const panel = root.querySelector('[data-notification-panel]');
        root.querySelector('[data-notification-toggle]').addEventListener('click', async () => {
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) {
                await load(root);
            }
        });
    }

    async function load(root) {
        const list = root.querySelector('[data-notification-list]');
        const response = await fetch(`${apiBaseUrl}/notifications`, {
            headers: {
                Authorization: `Bearer ${window.SewaAjaAuth.token()}`,
                'X-Authorization': `Bearer ${window.SewaAjaAuth.token()}`,
            },
        });
        const payload = await response.json();
        const notifications = payload.data?.notifications || [];

        list.innerHTML = notifications.length ? notifications.map((item) => `
            <button type="button" class="block w-full border-b border-slate-100 p-4 text-left hover:bg-[#f8fbff]" data-notification-id="${item.id}">
                <strong class="block text-sm font-black text-[#061f4f]">${escapeHtml(item.title)}</strong>
                <span class="mt-1 block text-xs font-bold leading-5 text-slate-500">${escapeHtml(item.message)}</span>
            </button>
        `).join('') : '<div class="p-4 text-sm font-bold text-slate-500">Belum ada notifikasi.</div>';
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

    return { mount };
})();

window.SewaAjaNotifications = SewaAjaNotifications;
