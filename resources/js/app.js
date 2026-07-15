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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeCatalogSearch();
        initializeCatalogBrowser();
    });
} else {
    initializeCatalogSearch();
    initializeCatalogBrowser();
}
