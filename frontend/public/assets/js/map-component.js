const SewaAjaMap = (() => {
    function render(target, location) {
        const element = typeof target === 'string' ? document.querySelector(target) : target;

        if (!element) {
            return;
        }

        const hasCoordinate = location?.latitude && location?.longitude;
        const query = hasCoordinate
            ? `${location.latitude},${location.longitude}`
            : [location?.address, location?.city, location?.province].filter(Boolean).join(', ');

        if (!query) {
            element.innerHTML = '<div class="rounded-lg border border-slate-100 bg-[#f8fbff] p-4 text-sm font-bold text-slate-500">Lokasi belum tersedia.</div>';
            return;
        }

        element.innerHTML = `
            <iframe
                title="Lokasi vendor"
                class="h-72 w-full rounded-lg border border-slate-100"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=${encodeURIComponent(query)}&output=embed">
            </iframe>
        `;
    }

    function currentPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation tidak didukung browser.'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                }),
                reject,
                { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
            );
        });
    }

    return { render, currentPosition };
})();

window.SewaAjaMap = SewaAjaMap;
