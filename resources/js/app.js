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

let catalogSearchRun = 0;

function endpointWithQuery(endpoint, query) {
    const url = new URL(endpoint, window.location.origin);
    url.searchParams.set('q', query);
    return url;
}

async function requestCatalog(endpoint, query) {
    const response = await fetch(endpointWithQuery(endpoint, query), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!response.ok) throw new Error('Catalog request failed');
    return response.json();
}

function mergeCatalogResults(container, html) {
    const template = document.createElement('template');
    template.innerHTML = html;

    for (const item of template.content.querySelectorAll('[data-catalog-key]')) {
        const key = item.dataset.catalogKey;
        const existing = Array.from(container.querySelectorAll('[data-catalog-key]'))
            .find((candidate) => candidate.dataset.catalogKey === key);

        if (existing) {
            if (!existing.querySelector('input')?.checked) existing.replaceWith(item);
        } else {
            container.appendChild(item);
        }
    }
}

async function runCatalogSearch(query, includeCache = false) {
    const search = document.querySelector('[data-catalog-search]');
    if (!search) return;

    const run = ++catalogSearchRun;
    const results = search.querySelector('[data-catalog-results]');
    const loading = search.querySelector('[data-catalog-loading]');
    const loadingLabel = search.querySelector('[data-catalog-loading-label]');
    const empty = search.querySelector('[data-catalog-empty]');
    const error = search.querySelector('[data-catalog-error]');

    search.classList.remove('hidden');
    loading.classList.remove('hidden');
    empty.classList.add('hidden');
    error.classList.add('hidden');

    if (includeCache) {
        results.replaceChildren();
        loadingLabel.textContent = 'Ищем в локальном каталоге…';

        try {
            const cached = await requestCatalog(search.dataset.cacheUrl, query);
            if (run !== catalogSearchRun) return;
            results.innerHTML = cached.html;
            loadingLabel.textContent = cached.count > 0
                ? 'Показали локальные результаты. Ищем остальные…'
                : 'Локальных результатов нет. Ищем во внешнем каталоге…';
        } catch {
            if (run !== catalogSearchRun) return;
            loadingLabel.textContent = 'Ищем во внешнем каталоге…';
        }
    }

    try {
        const fresh = await requestCatalog(search.dataset.searchUrl, query);
        if (run !== catalogSearchRun) return;
        mergeCatalogResults(results, fresh.html);
        empty.classList.toggle('hidden', results.querySelector('[data-catalog-key]') !== null);
    } catch {
        if (run !== catalogSearchRun) return;
        error.classList.remove('hidden');
    } finally {
        if (run === catalogSearchRun) loading.classList.add('hidden');
    }
}

let catalogBrowserRun = 0;
let catalogBrowserInputTimer;

function catalogBrowserEndpoint(endpoint, query, page = 1) {
    const url = new URL(endpoint, window.location.origin);
    if (query) url.searchParams.set('q', query);
    url.searchParams.set('page', page);
    return url;
}

async function requestCatalogBrowser(browser, query, page = 1) {
    const response = await fetch(catalogBrowserEndpoint(browser.dataset.resultsUrl, query, page), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!response.ok) throw new Error('Catalog browser request failed');
    return response.json();
}

function renderCatalogBrowser(browser, data, replace = true) {
    const results = browser.querySelector('[data-catalog-browser-results]');
    const empty = browser.querySelector('[data-catalog-browser-empty]');
    const more = browser.querySelector('[data-catalog-browser-more]');

    if (replace) results.innerHTML = data.html;
    else results.insertAdjacentHTML('beforeend', data.html);

    const hasGames = results.querySelector('[data-catalog-browser-card]') !== null;
    empty.classList.toggle('hidden', hasGames);
    empty.classList.toggle('flex', !hasGames);
    more.classList.toggle('hidden', !data.next_page);
    browser.dataset.nextPage = data.next_page || '';
}

async function runCatalogBrowserSearch(query, localFirst = true) {
    const browser = document.querySelector('[data-catalog-browser]');
    if (!browser) return;

    const run = ++catalogBrowserRun;
    const loading = browser.querySelector('[data-catalog-browser-loading]');
    const loadingLabel = browser.querySelector('[data-catalog-browser-loading-label]');
    const error = browser.querySelector('[data-catalog-browser-error]');

    browser.dataset.query = query;
    loading.classList.remove('hidden');
    loading.classList.add('flex');
    error.classList.add('hidden');

    try {
        if (localFirst) {
            loadingLabel.textContent = 'Ищем игры…';
            const local = await requestCatalogBrowser(browser, query);
            if (run !== catalogBrowserRun) return;
            renderCatalogBrowser(browser, local);
        }

        if (query !== '') {
            loadingLabel.textContent = 'Ищем игры…';
            await requestCatalog(browser.dataset.freshUrl, query);
            if (run !== catalogBrowserRun) return;

            const refreshed = await requestCatalogBrowser(browser, query);
            if (run !== catalogBrowserRun) return;
            renderCatalogBrowser(browser, refreshed);
        }
    } catch {
        if (run === catalogBrowserRun) error.classList.remove('hidden');
    } finally {
        if (run === catalogBrowserRun) {
            loading.classList.add('hidden');
            loading.classList.remove('flex');
        }
    }
}

document.addEventListener('click', async (event) => {
    const notificationToggle = event.target.closest('[data-notification-toggle]');
    if (notificationToggle) {
        const center = notificationToggle.closest('[data-notification-center]');
        const panel = center.querySelector('[data-notification-panel]');
        const opening = panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !opening);
        notificationToggle.setAttribute('aria-expanded', String(opening));
    } else if (!event.target.closest('[data-notification-center]')) {
        document.querySelectorAll('[data-notification-panel]:not(.hidden)').forEach((panel) => {
            panel.classList.add('hidden');
            panel.closest('[data-notification-center]')?.querySelector('[data-notification-toggle]')?.setAttribute('aria-expanded', 'false');
        });
    }

    const quickAddButton = event.target.closest('[data-quick-add]');
    if (quickAddButton && !quickAddButton.disabled) {
        const icon = quickAddButton.querySelector('[data-quick-add-icon]');
        const initialTitle = quickAddButton.title;
        quickAddButton.disabled = true;
        icon.textContent = 'progress_activity';
        icon.classList.add('animate-spin');

        try {
            const response = await fetch(quickAddButton.dataset.quickAdd, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (!response.ok && response.status !== 409) throw new Error('Quick add failed');
            icon.classList.remove('animate-spin');
            icon.textContent = 'check';
            quickAddButton.title = response.status === 409 ? 'Уже в списке' : 'Добавлено в список';
            quickAddButton.classList.add('border-emerald-400/25', 'bg-emerald-950/80', 'text-emerald-300');
        } catch {
            icon.classList.remove('animate-spin');
            icon.textContent = 'error';
            quickAddButton.title = 'Не удалось добавить игру';
            window.setTimeout(() => {
                icon.textContent = 'add';
                quickAddButton.title = initialTitle;
                quickAddButton.disabled = false;
            }, 1800);
        }
    }

    const moreButton = event.target.closest('[data-catalog-browser-more]');
    if (moreButton) {
        const browser = moreButton.closest('[data-catalog-browser]');
        const page = Number(browser.dataset.nextPage || 0);
        if (!page) return;

        const label = moreButton.querySelector('[data-catalog-browser-more-label]');
        moreButton.disabled = true;
        label.textContent = 'Загружаем…';

        try {
            const data = await requestCatalogBrowser(browser, browser.dataset.query || '', page);
            renderCatalogBrowser(browser, data, false);
        } finally {
            moreButton.disabled = false;
            label.textContent = 'Показать ещё 20';
        }
    }

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

function updateNotificationCenter(center, count) {
    const badge = center.querySelector('[data-notification-badge]');
    const empty = center.querySelector('[data-notification-empty]');
    const clearButton = center.querySelector('[data-notification-clear] button');

    center.dataset.notificationCount = String(count);
    badge.textContent = count > 99 ? '99+' : String(count);
    badge.classList.toggle('hidden', count === 0);
    empty.classList.toggle('hidden', count !== 0);
    clearButton.disabled = count === 0;
}

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-notification-dismiss], [data-notification-clear]');
    if (!form) return;

    event.preventDefault();
    const center = form.closest('[data-notification-center]');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (!response.ok) throw new Error('Notification request failed');

        if (form.matches('[data-notification-clear]')) {
            center.querySelector('[data-notification-list]').replaceChildren();
            updateNotificationCenter(center, 0);
        } else {
            form.closest('[data-notification-item]')?.remove();
            const count = Math.max(0, Number(center.dataset.notificationCount || 0) - 1);
            updateNotificationCenter(center, count);
        }
    } catch {
        HTMLFormElement.prototype.submit.call(form);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    document.querySelectorAll('[data-notification-panel]:not(.hidden)').forEach((panel) => {
        panel.classList.add('hidden');
        panel.closest('[data-notification-center]')?.querySelector('[data-notification-toggle]')?.setAttribute('aria-expanded', 'false');
    });
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

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-catalog-search-form]');
    if (!form) return;

    event.preventDefault();
    const query = String(new FormData(form).get('q') || '').trim();
    const search = document.querySelector('[data-catalog-search]');
    const url = new URL(window.location.href);

    if (query === '') {
        catalogSearchRun++;
        url.searchParams.delete('q');
        window.history.replaceState({}, '', url);
        search.classList.add('hidden');
        search.querySelector('[data-catalog-results]').replaceChildren();
        return;
    }

    url.searchParams.set('q', query);
    window.history.replaceState({}, '', url);
    runCatalogSearch(query, true);
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-catalog-browser-form]');
    if (!form) return;

    event.preventDefault();
    window.clearTimeout(catalogBrowserInputTimer);
    const query = String(new FormData(form).get('q') || '').trim();
    runCatalogBrowserSearch(query);
});

document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-catalog-browser-input]');
    if (!input) return;

    const browser = input.closest('[data-catalog-browser]');
    const query = input.value.trim();

    window.clearTimeout(catalogBrowserInputTimer);
    catalogBrowserRun++;
    browser.querySelector('[data-catalog-browser-loading]').classList.add('hidden');
    browser.querySelector('[data-catalog-browser-loading]').classList.remove('flex');
    browser.querySelector('[data-catalog-browser-error]').classList.add('hidden');

    catalogBrowserInputTimer = window.setTimeout(() => {
        runCatalogBrowserSearch(query);
    }, 350);
});

function initializeCatalogSearch() {
    const search = document.querySelector('[data-catalog-search]');
    if (search?.dataset.query) runCatalogSearch(search.dataset.query);
}

function initializeCatalogBrowser() {
    const browser = document.querySelector('[data-catalog-browser]');
    if (browser?.dataset.query) runCatalogBrowserSearch(browser.dataset.query, false);
}

function normalizeFavoriteSearch(value) {
    return String(value)
        .toLocaleLowerCase('ru-RU')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function favoriteGameData(picker) {
    return Array.from(picker.querySelectorAll('[data-favorite-game]'), (game) => ({
        id: game.dataset.value,
        title: game.dataset.title,
        list: game.dataset.list,
        searchTitle: normalizeFavoriteSearch(game.dataset.title),
    }));
}

function closeFavoriteCombobox(combobox) {
    combobox.querySelector('[data-favorite-results]').classList.add('hidden');
    const input = combobox.querySelector('[data-favorite-input]');
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    combobox.favoriteActiveIndex = -1;
}

function setFavoriteActiveSuggestion(combobox, index) {
    const suggestions = Array.from(combobox.querySelectorAll('[data-favorite-suggestion]'));
    if (suggestions.length === 0) return;

    const nextIndex = (index + suggestions.length) % suggestions.length;
    suggestions.forEach((suggestion, suggestionIndex) => {
        const active = suggestionIndex === nextIndex;
        suggestion.classList.toggle('bg-white/8', active);
        suggestion.setAttribute('aria-selected', String(active));
    });
    suggestions[nextIndex].scrollIntoView({ block: 'nearest' });
    combobox.querySelector('[data-favorite-input]').setAttribute('aria-activedescendant', suggestions[nextIndex].id);
    combobox.favoriteActiveIndex = nextIndex;
}

function renderFavoriteSuggestions(combobox) {
    const picker = combobox.closest('[data-favorite-picker]');
    const input = combobox.querySelector('[data-favorite-input]');
    const results = combobox.querySelector('[data-favorite-results]');
    const selectedValue = combobox.querySelector('[data-favorite-value]').value;
    const queryWords = normalizeFavoriteSearch(input.value).split(/\s+/).filter(Boolean);
    const matchingGames = favoriteGameData(picker).filter((game) => (
        queryWords.every((word) => game.searchTitle.includes(word))
    ));

    results.replaceChildren();
    combobox.favoriteActiveIndex = -1;

    if (matchingGames.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'px-3 py-4 text-center text-xs text-slate-500';
        empty.textContent = 'Игры с таким названием не найдены.';
        results.appendChild(empty);
    } else {
        matchingGames.slice(0, 20).forEach((game, index) => {
            const suggestion = document.createElement('button');
            suggestion.type = 'button';
            suggestion.id = `${results.id}_option_${index}`;
            suggestion.className = 'flex w-full cursor-pointer items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-white/8';
            suggestion.dataset.favoriteSuggestion = '';
            suggestion.dataset.value = game.id;
            suggestion.dataset.title = game.title;
            suggestion.setAttribute('role', 'option');
            suggestion.setAttribute('aria-selected', String(game.id === selectedValue));

            const title = document.createElement('span');
            title.className = 'min-w-0 truncate text-sm font-semibold text-slate-200';
            title.textContent = game.title;
            suggestion.appendChild(title);

            const list = document.createElement('span');
            list.className = 'max-w-28 shrink-0 truncate text-[10px] font-semibold text-slate-500';
            list.textContent = game.list;
            suggestion.appendChild(list);
            results.appendChild(suggestion);
        });

        if (matchingGames.length > 20) {
            const hint = document.createElement('p');
            hint.className = 'border-t border-white/8 px-3 py-2 text-center text-[10px] text-slate-500';
            hint.textContent = 'Продолжайте вводить название, чтобы сузить список.';
            results.appendChild(hint);
        }
    }

    results.classList.remove('hidden');
    input.setAttribute('aria-expanded', 'true');
    combobox.querySelector('[data-favorite-clear]').classList.toggle('hidden', input.value === '');
    combobox.querySelector('[data-favorite-clear]').classList.toggle('grid', input.value !== '');
}

function chooseFavoriteSuggestion(combobox, suggestion) {
    const input = combobox.querySelector('[data-favorite-input]');
    input.value = suggestion.dataset.title;
    input.setCustomValidity('');
    combobox.querySelector('[data-favorite-value]').value = suggestion.dataset.value;
    combobox.querySelector('[data-favorite-clear]').classList.remove('hidden');
    combobox.querySelector('[data-favorite-clear]').classList.add('grid');
    input.focus({ preventScroll: true });
    closeFavoriteCombobox(combobox);
}

document.addEventListener('focusin', (event) => {
    const input = event.target.closest('[data-favorite-input]');
    if (!input) return;

    const combobox = input.closest('[data-favorite-combobox]');
    document.querySelectorAll('[data-favorite-combobox]').forEach((otherCombobox) => {
        if (otherCombobox !== combobox) closeFavoriteCombobox(otherCombobox);
    });
    renderFavoriteSuggestions(combobox);
});

document.addEventListener('focusout', (event) => {
    const combobox = event.target.closest('[data-favorite-combobox]');
    if (!combobox) return;

    window.setTimeout(() => {
        if (!combobox.contains(document.activeElement)) closeFavoriteCombobox(combobox);
    });
});

document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-favorite-input]');
    if (!input) return;

    input.setCustomValidity('');
    input.closest('[data-favorite-combobox]').querySelector('[data-favorite-value]').value = '';
    renderFavoriteSuggestions(input.closest('[data-favorite-combobox]'));
});

document.addEventListener('keydown', (event) => {
    const input = event.target.closest('[data-favorite-input]');
    if (!input) return;

    const combobox = input.closest('[data-favorite-combobox]');
    const suggestions = combobox.querySelectorAll('[data-favorite-suggestion]');

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        if (combobox.querySelector('[data-favorite-results]').classList.contains('hidden')) {
            renderFavoriteSuggestions(combobox);
        }
        const direction = event.key === 'ArrowDown' ? 1 : -1;
        const suggestionCount = combobox.querySelectorAll('[data-favorite-suggestion]').length;
        const activeIndex = combobox.favoriteActiveIndex ?? -1;
        const nextIndex = activeIndex === -1 && direction === -1
            ? suggestionCount - 1
            : activeIndex + direction;
        setFavoriteActiveSuggestion(combobox, nextIndex);
    } else if (event.key === 'Enter' && (combobox.favoriteActiveIndex ?? -1) >= 0) {
        event.preventDefault();
        chooseFavoriteSuggestion(combobox, suggestions[combobox.favoriteActiveIndex]);
    } else if (event.key === 'Escape') {
        closeFavoriteCombobox(combobox);
    }
});

document.addEventListener('click', (event) => {
    const suggestion = event.target.closest('[data-favorite-suggestion]');
    if (suggestion) {
        chooseFavoriteSuggestion(suggestion.closest('[data-favorite-combobox]'), suggestion);
        return;
    }

    const clearButton = event.target.closest('[data-favorite-clear]');
    if (clearButton) {
        const combobox = clearButton.closest('[data-favorite-combobox]');
        const input = combobox.querySelector('[data-favorite-input]');
        input.value = '';
        input.setCustomValidity('');
        combobox.querySelector('[data-favorite-value]').value = '';
        input.focus();
        renderFavoriteSuggestions(combobox);
        return;
    }

    document.querySelectorAll('[data-favorite-combobox]').forEach((combobox) => {
        if (!combobox.contains(event.target)) closeFavoriteCombobox(combobox);
    });
});

document.addEventListener('submit', (event) => {
    const picker = event.target.closest('[data-favorite-picker]');
    if (!picker) return;

    const incompleteInput = Array.from(picker.querySelectorAll('[data-favorite-combobox]')).find((combobox) => (
        combobox.querySelector('[data-favorite-input]').value.trim() !== ''
        && combobox.querySelector('[data-favorite-value]').value === ''
    ));
    if (!incompleteInput) return;

    event.preventDefault();
    const input = incompleteInput.querySelector('[data-favorite-input]');
    input.setCustomValidity('Выберите игру из подсказок или очистите поле.');
    input.reportValidity();
    input.focus();
    renderFavoriteSuggestions(incompleteInput);
});

function setMarkdownEditorMode(editor, preview) {
    const writeButton = editor.querySelector('[data-markdown-write]');
    const previewButton = editor.querySelector('[data-markdown-preview]');

    editor.querySelector('[data-markdown-write-panel]').classList.toggle('hidden', preview);
    editor.querySelector('[data-markdown-preview-panel]').classList.toggle('hidden', !preview);
    writeButton.classList.toggle('border-violet-400', !preview);
    writeButton.classList.toggle('border-transparent', preview);
    writeButton.classList.toggle('text-white', !preview);
    writeButton.classList.toggle('text-slate-500', preview);
    previewButton.classList.toggle('border-violet-400', preview);
    previewButton.classList.toggle('border-transparent', !preview);
    previewButton.classList.toggle('text-white', preview);
    previewButton.classList.toggle('text-slate-500', !preview);
}

document.addEventListener('click', async (event) => {
    const writeButton = event.target.closest('[data-markdown-write]');
    if (writeButton) {
        setMarkdownEditorMode(writeButton.closest('[data-markdown-editor]'), false);
        return;
    }

    const previewButton = event.target.closest('[data-markdown-preview]');
    if (!previewButton) return;

    const editor = previewButton.closest('[data-markdown-editor]');
    const previewPanel = editor.querySelector('[data-markdown-preview-panel]');
    const formData = new FormData();
    formData.set('body', editor.querySelector('[data-markdown-input], textarea[name="body"]').value);
    setMarkdownEditorMode(editor, true);
    previewPanel.innerHTML = '<p class="text-slate-500">Создаём предпросмотр…</p>';

    try {
        const response = await fetch(editor.dataset.previewUrl, {
            method: 'POST',
            body: formData,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        if (!response.ok) throw new Error('Markdown preview failed');
        const data = await response.json();
        previewPanel.innerHTML = data.html || '<p class="text-slate-500">Введите текст, чтобы увидеть предпросмотр.</p>';
    } catch {
        previewPanel.innerHTML = '<p class="text-red-300">Не удалось создать предпросмотр.</p>';
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeCatalogSearch();
        initializeCatalogBrowser();
    });
} else {
    initializeCatalogSearch();
    initializeCatalogBrowser();
}
