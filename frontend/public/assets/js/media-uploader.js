const SewaAjaMediaUploader = (() => {
    function init(options) {
        const dropzone = document.querySelector(options.dropzone);
        const input = document.querySelector(options.input);
        const preview = document.querySelector(options.preview);

        if (!dropzone || !input || !preview) {
            return;
        }

        const maxSize = options.maxSize || 5 * 1024 * 1024;
        const accept = ['image/jpeg', 'image/png', 'image/webp'];

        dropzone.addEventListener('click', () => input.click());
        dropzone.addEventListener('dragover', (event) => {
            event.preventDefault();
            dropzone.classList.add('border-[#ff6a00]', 'bg-[#fff7f1]');
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('border-[#ff6a00]', 'bg-[#fff7f1]');
        });
        dropzone.addEventListener('drop', (event) => {
            event.preventDefault();
            dropzone.classList.remove('border-[#ff6a00]', 'bg-[#fff7f1]');
            handleFiles([...event.dataTransfer.files]);
        });
        input.addEventListener('change', () => handleFiles([...input.files]));

        async function handleFiles(files) {
            const validFiles = files.filter((file) => accept.includes(file.type) && file.size <= maxSize);
            preview.innerHTML = validFiles.map((file) => `
                <div class="rounded-lg border border-slate-100 bg-white p-2">
                    <img src="${URL.createObjectURL(file)}" alt="${escapeHtml(file.name)}" class="aspect-square w-full rounded-md object-cover" loading="lazy">
                    <p class="mt-2 truncate text-xs font-bold text-slate-500">${escapeHtml(file.name)}</p>
                </div>
            `).join('');

            if (options.upload && validFiles.length) {
                for (const file of validFiles) {
                    await options.upload(file);
                }
            }
        }
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

    return { init };
})();

window.SewaAjaMediaUploader = SewaAjaMediaUploader;
