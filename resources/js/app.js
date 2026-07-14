document.addEventListener('click', async (event) => {
    const copyButton = event.target.closest('[data-copy]');
    if (copyButton) {
        await navigator.clipboard.writeText(copyButton.dataset.copy);
        const label = copyButton.querySelector('[data-copy-label]');
        if (label) {
            const initial = label.textContent;
            label.textContent = copyButton.dataset.copied || 'Скопировано';
            window.setTimeout(() => { label.textContent = initial; }, 1600);
        }
    }

    const confirmButton = event.target.closest('[data-confirm]');
    if (confirmButton && !window.confirm(confirmButton.dataset.confirm)) {
        event.preventDefault();
    }
});

document.addEventListener('change', (event) => {
    const result = event.target.closest('[data-catalog-result]');
    if (!result) return;

    const form = document.querySelector('[data-game-form]');
    if (!form) return;
    const data = JSON.parse(result.dataset.catalogResult);
    for (const [name, value] of Object.entries(data)) {
        const input = form.querySelector(`[name="${name}"]`);
        if (input) input.value = value ?? '';
    }

    const preview = form.querySelector('[data-cover-preview]');
    if (preview && data.catalog_cover_url) preview.src = data.catalog_cover_url;
});
