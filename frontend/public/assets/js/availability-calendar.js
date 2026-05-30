const SewaAjaAvailabilityCalendar = (() => {
    function render(target, availability, options = {}) {
        if (!target || !availability?.days) {
            return;
        }

        const selectedStart = options.startDate || availability.start_date;
        const selectedEnd = options.endDate || availability.end_date;

        target.innerHTML = availability.days.map((day) => {
            const selected = day.date >= selectedStart && day.date <= selectedEnd;
            const state = day.is_available
                ? 'border-emerald-100 bg-emerald-50 text-emerald-800'
                : 'border-red-100 bg-red-50 text-red-700';
            const active = selected ? 'ring-2 ring-[#ff6a00] ring-offset-2' : '';

            return `
                <button type="button" class="rounded-lg border p-3 text-left transition ${state} ${active}" data-date="${day.date}">
                    <time class="block text-xs font-black">${day.date.slice(5)}</time>
                    <span class="mt-1 block text-sm font-bold">${day.available_quantity} tersedia</span>
                    ${day.blocked_quantity > 0 ? '<span class="mt-1 block text-xs font-bold">Diblok vendor</span>' : ''}
                </button>
            `;
        }).join('');
    }

    function validateRange(availability, startDate, endDate, quantity) {
        if (!availability?.days?.length || !startDate || !endDate || endDate < startDate) {
            return { valid: false, message: 'Rentang tanggal belum valid.' };
        }

        const requestedDays = availability.days.filter((day) => day.date >= startDate && day.date <= endDate);
        const unavailable = requestedDays.find((day) => day.available_quantity < quantity);

        if (!requestedDays.length) {
            return { valid: false, message: 'Tanggal belum masuk kalender availability.' };
        }

        if (unavailable) {
            return {
                valid: false,
                message: `Stok pada ${unavailable.date} hanya ${unavailable.available_quantity}.`,
                date: unavailable.date,
            };
        }

        return { valid: true, message: 'Produk tersedia untuk tanggal dan jumlah yang dipilih.' };
    }

    return { render, validateRange };
})();

window.SewaAjaAvailabilityCalendar = SewaAjaAvailabilityCalendar;
