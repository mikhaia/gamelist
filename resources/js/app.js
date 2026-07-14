async function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            return;
        } catch {
            // Use the fallback below when browser permissions deny Clipboard API.
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    const copied = document.execCommand('copy');
    textarea.remove();

    if (!copied) throw new Error('Copy failed');
}

document.addEventListener('click', async (event) => {
    const copyButton = event.target.closest('[data-copy]');
    if (copyButton) {
        const label = copyButton.querySelector('[data-copy-label]');
        const initial = label?.textContent;

        try {
            await copyText(copyButton.dataset.copy);
            if (label) label.textContent = copyButton.dataset.copied || 'Скопировано';
        } catch {
            if (label) label.textContent = 'Не удалось скопировать';
        }

        if (label) window.setTimeout(() => { label.textContent = initial; }, 1600);
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
