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

function catalogBrowserEndpoint(browser, endpoint, query, page = null) {
    const url = new URL(endpoint, window.location.origin);
    if (query) url.searchParams.set('q', query);
    if (browser.dataset.genre) {
        url.searchParams.set('genre', browser.dataset.genre);
        if (browser.dataset.genreName) url.searchParams.set('genre_name', browser.dataset.genreName);
    }
    if (browser.dataset.platform) {
        url.searchParams.set('platform', browser.dataset.platform);
        if (browser.dataset.platformName) url.searchParams.set('platform_name', browser.dataset.platformName);
    }
    if (page !== null) url.searchParams.set('page', page);
    return url;
}

async function requestCatalogBrowser(browser, query, page = 1) {
    const response = await fetch(catalogBrowserEndpoint(browser, browser.dataset.resultsUrl, query, page), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!response.ok) throw new Error('Catalog browser request failed');
    return response.json();
}

async function requestRawgCatalog(browser, query) {
    const response = await fetch(catalogBrowserEndpoint(browser, browser.dataset.rawgUrl, query), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!response.ok) throw new Error('RAWG catalog request failed');
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

function closeCatalogListDialog(dialog) {
    if (!dialog) return;

    dialog.classList.add('hidden');
    dialog.classList.remove('flex');
    dialog.catalogTrigger?.focus();
}

function closeGameDuplicateDialog(dialog) {
    if (!dialog) return;

    dialog.classList.add('hidden');
    dialog.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

function openGameDuplicateDialog(duplicate, retry) {
    const dialog = document.querySelector('[data-game-duplicate-dialog][data-ajax]');
    if (!dialog) return;

    dialog.querySelector('[data-game-duplicate-title]').textContent = duplicate.title;
    dialog.querySelector('[data-game-duplicate-list]').textContent = duplicate.list;
    dialog.querySelector('[data-game-duplicate-edit]').href = duplicate.edit_url;
    dialog.duplicateRetry = retry;
    dialog.classList.remove('hidden');
    dialog.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    dialog.querySelector('[data-game-duplicate-edit]')?.focus();
}

async function requestCatalogAdd(endpoint, allowDuplicate = false) {
    const body = new URLSearchParams();
    if (allowDuplicate) body.set('allow_duplicate', '1');

    const response = await fetch(endpoint, {
        method: 'POST',
        body,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    });
    const data = await response.json();

    return { response, data };
}

function screenshotTriggers() {
    return Array.from(document.querySelectorAll('[data-screenshot-open]'));
}

function showScreenshot(modal, index) {
    const triggers = screenshotTriggers();
    if (triggers.length === 0) return;

    const normalizedIndex = (index + triggers.length) % triggers.length;
    const trigger = triggers[normalizedIndex];
    const image = modal.querySelector('[data-screenshot-modal-image]');

    modal.dataset.screenshotIndex = String(normalizedIndex);
    image.src = trigger.dataset.screenshotUrl;
    image.alt = trigger.dataset.screenshotAlt;
    modal.querySelector('[data-screenshot-caption]').textContent = trigger.dataset.screenshotAlt;
    modal.querySelector('[data-screenshot-counter]').textContent = `${normalizedIndex + 1} / ${triggers.length}`;
    modal.querySelector('[data-screenshot-previous]').classList.toggle('hidden', triggers.length < 2);
    modal.querySelector('[data-screenshot-next]').classList.toggle('hidden', triggers.length < 2);
}

function openScreenshotModal(trigger) {
    const modal = document.querySelector('[data-screenshot-modal]');
    if (!modal) return;

    modal.screenshotTrigger = trigger;
    showScreenshot(modal, Number(trigger.dataset.screenshotIndex));
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
    modal.querySelector('[data-screenshot-close]:not(.absolute.inset-0)')?.focus();
}

function closeScreenshotModal(modal) {
    if (!modal || modal.classList.contains('hidden')) return;

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
    modal.querySelector('[data-screenshot-modal-image]').removeAttribute('src');
    modal.screenshotTrigger?.focus();
}

function resetCatalogListOptions(dialog) {
    dialog.querySelectorAll('[data-catalog-list-option]').forEach((option) => {
        option.disabled = false;
        option.classList.add('border-white/8', 'bg-white/[.025]');
        option.classList.remove('border-emerald-400/25', 'bg-emerald-950/40');
        const icon = option.querySelector('[data-catalog-list-option-icon]');
        icon.textContent = 'add';
        icon.classList.remove('animate-spin', 'text-emerald-300', 'text-red-300');
    });
}

async function runCatalogBrowserSearch(query, localFirst = true) {
    const browser = document.querySelector('[data-catalog-browser]');
    if (!browser) return;

    const run = ++catalogBrowserRun;
    const loading = browser.querySelector('[data-catalog-browser-loading]');
    const loadingLabel = browser.querySelector('[data-catalog-browser-loading-label]');
    const error = browser.querySelector('[data-catalog-browser-error]');
    const hasRawgFilters = Boolean(browser.dataset.genre || browser.dataset.platform);

    browser.dataset.query = query;
    const browserUrl = new URL(window.location.href);
    if (query) browserUrl.searchParams.set('q', query);
    else browserUrl.searchParams.delete('q');
    if (browser.dataset.genre) {
        browserUrl.searchParams.set('genre', browser.dataset.genre);
        browserUrl.searchParams.set('genre_name', browser.dataset.genreName);
    } else {
        browserUrl.searchParams.delete('genre');
        browserUrl.searchParams.delete('genre_name');
    }
    if (browser.dataset.platform) {
        browserUrl.searchParams.set('platform', browser.dataset.platform);
        browserUrl.searchParams.set('platform_name', browser.dataset.platformName);
    } else {
        browserUrl.searchParams.delete('platform');
        browserUrl.searchParams.delete('platform_name');
    }
    browserUrl.searchParams.delete('page');
    window.history.replaceState({}, '', browserUrl);
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

        if (query !== '' || hasRawgFilters) {
            loadingLabel.textContent = hasRawgFilters ? 'Ищем игры в RAWG…' : 'Ищем игры…';
            if (hasRawgFilters) await requestRawgCatalog(browser, query);
            else await requestCatalog(browser.dataset.freshUrl, query);
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

function syncCatalogBrowserFormFilters(form) {
    const browser = form.closest('[data-catalog-browser]');
    const genre = form.querySelector('[data-catalog-browser-genre]');
    const platform = form.querySelector('[data-catalog-browser-platform]');

    browser.dataset.genre = genre?.value || '';
    browser.dataset.genreName = genre?.value ? genre.selectedOptions[0].textContent.trim() : '';
    browser.dataset.platform = platform?.value || '';
    browser.dataset.platformName = platform?.value ? platform.selectedOptions[0].textContent.trim() : '';
}

document.addEventListener('click', async (event) => {
    const duplicateAllow = event.target.closest('[data-game-duplicate-dialog][data-ajax] [data-game-duplicate-allow]');
    if (duplicateAllow) {
        const dialog = duplicateAllow.closest('[data-game-duplicate-dialog]');
        duplicateAllow.disabled = true;

        try {
            await dialog.duplicateRetry?.();
            closeGameDuplicateDialog(dialog);
        } catch {
            duplicateAllow.classList.add('border-red-300/40', 'text-red-200');
            window.setTimeout(() => duplicateAllow.classList.remove('border-red-300/40', 'text-red-200'), 1800);
        } finally {
            duplicateAllow.disabled = false;
        }
        return;
    }

    const duplicateClose = event.target.closest('[data-game-duplicate-close]');
    if (duplicateClose) {
        closeGameDuplicateDialog(duplicateClose.closest('[data-game-duplicate-dialog]'));
        return;
    }

    const screenshotOpen = event.target.closest('[data-screenshot-open]');
    if (screenshotOpen) {
        openScreenshotModal(screenshotOpen);
        return;
    }

    const screenshotClose = event.target.closest('[data-screenshot-close]');
    if (screenshotClose) {
        closeScreenshotModal(screenshotClose.closest('[data-screenshot-modal]'));
        return;
    }

    const screenshotDirection = event.target.closest('[data-screenshot-previous], [data-screenshot-next]');
    if (screenshotDirection) {
        const modal = screenshotDirection.closest('[data-screenshot-modal]');
        const direction = screenshotDirection.matches('[data-screenshot-next]') ? 1 : -1;
        showScreenshot(modal, Number(modal.dataset.screenshotIndex) + direction);
        return;
    }

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

    const listPicker = event.target.closest('[data-catalog-list-picker]');
    if (listPicker) {
        if (listPicker.dataset.loginUrl) {
            window.location.assign(listPicker.dataset.loginUrl);
            return;
        }

        const dialog = document.querySelector('[data-catalog-list-dialog]');
        if (!dialog) return;
        if (dialog.dataset.catalogId !== listPicker.dataset.catalogId) resetCatalogListOptions(dialog);
        dialog.dataset.catalogId = listPicker.dataset.catalogId;
        dialog.querySelector('[data-catalog-list-game-title]').textContent = listPicker.dataset.catalogTitle;
        dialog.catalogTrigger = listPicker;
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        dialog.querySelector('[data-catalog-list-option], [data-catalog-list-close]')?.focus();
        return;
    }

    const listDialogClose = event.target.closest('[data-catalog-list-close]');
    if (listDialogClose) {
        closeCatalogListDialog(listDialogClose.closest('[data-catalog-list-dialog]'));
        return;
    }

    const listOption = event.target.closest('[data-catalog-list-option]');
    if (listOption && !listOption.disabled) {
        const dialog = listOption.closest('[data-catalog-list-dialog]');
        const icon = listOption.querySelector('[data-catalog-list-option-icon]');
        const endpoint = listOption.dataset.addUrlTemplate.replace('CATALOG_GAME_ID', dialog.dataset.catalogId);
        const add = async (allowDuplicate = false) => {
            listOption.disabled = true;
            icon.textContent = 'progress_activity';
            icon.classList.add('animate-spin');

            const { response, data } = await requestCatalogAdd(endpoint, allowDuplicate);
            if (response.status === 409 && data.duplicate && !allowDuplicate) {
                icon.classList.remove('animate-spin');
                icon.textContent = 'add';
                listOption.disabled = false;
                openGameDuplicateDialog(data.duplicate, () => add(true));
                return;
            }
            if (!response.ok) throw new Error('List add failed');

            icon.classList.remove('animate-spin');
            icon.classList.add('text-emerald-300');
            icon.textContent = 'check';
            listOption.classList.remove('border-white/8', 'bg-white/[.025]');
            listOption.classList.add('border-emerald-400/25', 'bg-emerald-950/40');
        };

        try {
            await add();
        } catch {
            icon.classList.remove('animate-spin');
            icon.classList.add('text-red-300');
            icon.textContent = 'error';
            window.setTimeout(() => {
                icon.classList.remove('text-red-300');
                icon.textContent = 'add';
                listOption.disabled = false;
            }, 1800);
        }
        return;
    }

    const quickAddButton = event.target.closest('[data-quick-add]');
    if (quickAddButton && !quickAddButton.disabled) {
        const icon = quickAddButton.querySelector('[data-quick-add-icon]');
        const initialTitle = quickAddButton.title;
        quickAddButton.disabled = true;
        icon.textContent = 'progress_activity';
        icon.classList.add('animate-spin');

        const add = async (allowDuplicate = false) => {
            quickAddButton.disabled = true;
            icon.textContent = 'progress_activity';
            icon.classList.add('animate-spin');
            const { response, data } = await requestCatalogAdd(quickAddButton.dataset.quickAdd, allowDuplicate);
            if (response.status === 409 && data.duplicate && !allowDuplicate) {
                icon.classList.remove('animate-spin');
                icon.textContent = 'add';
                quickAddButton.disabled = false;
                openGameDuplicateDialog(data.duplicate, () => add(true));
                return;
            }
            if (!response.ok) throw new Error('Quick add failed');

            icon.classList.remove('animate-spin');
            icon.textContent = 'check';
            quickAddButton.title = 'Добавлено в список';
            quickAddButton.classList.remove('border-amber-400/25', 'bg-amber-950/80', 'text-amber-300');
            quickAddButton.classList.add('border-emerald-400/25', 'bg-emerald-950/80', 'text-emerald-300');
        };

        try {
            await add();
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

function compareGameItems(first, second) {
    const sort = new URLSearchParams(window.location.search).get('sort');

    if (sort === 'completed_at') {
        const completedDifference = String(second.dataset.completedAt || '')
            .localeCompare(String(first.dataset.completedAt || ''));
        if (completedDifference !== 0) return completedDifference;
    } else {
        const sortOrderDifference = Number(first.dataset.sortOrder || 0) - Number(second.dataset.sortOrder || 0);
        if (sortOrderDifference !== 0) return sortOrderDifference;
    }

    const createdDifference = String(second.dataset.createdAt || '')
        .localeCompare(String(first.dataset.createdAt || ''));
    if (createdDifference !== 0) return createdDifference;

    return Number(second.dataset.gameId || 0) - Number(first.dataset.gameId || 0);
}

function sortGameItems(container) {
    if (!container) return;

    Array.from(container.children)
        .filter((item) => item.matches('[data-game-item]'))
        .sort(compareGameItems)
        .forEach((item) => container.appendChild(item));
}

function updateBoardColumn(section) {
    if (!section) return;

    const container = section.querySelector('[data-board-games]');
    if (!container) return;

    const count = container.querySelectorAll(':scope > [data-game-item]').length;
    const countBadge = section.querySelector('[data-board-count]');
    if (countBadge) countBadge.textContent = String(count);

    let empty = container.querySelector('[data-board-empty]');
    if (!empty && count === 0) {
        empty = document.createElement('div');
        empty.className = 'grid min-h-28 place-items-center rounded-2xl border border-dashed border-white/8 px-4 text-center text-xs text-slate-600';
        empty.dataset.boardEmpty = '';
        empty.textContent = 'В этой колонке пока нет игр';
        container.appendChild(empty);
    }

    empty?.classList.toggle('hidden', count > 0);
}

function updateVisibleGameList() {
    const items = Array.from(document.querySelectorAll('[data-game-item]'));
    const count = items.length;
    const countBadge = document.querySelector('[data-list-game-count]');
    const copyButton = document.querySelector('[data-list-copy]');

    if (countBadge) {
        const total = Number(countBadge.dataset.totalGames || count);
        countBadge.textContent = countBadge.dataset.filtered === 'true'
            ? `${count} из ${total} игр`
            : `${total} игр`;
    }

    if (copyButton) {
        copyButton.dataset.copy = items.map((item) => `- ${item.dataset.gameTitle}`).join('\n');
        copyButton.disabled = count === 0;
        const label = copyButton.querySelector('[data-copy-label]');
        if (label) label.textContent = `Скопировать список (${count})`;
    }

    if (count === 0) {
        document.querySelector('[data-game-list-items]')?.classList.add('hidden');
        const empty = document.querySelector('[data-game-list-client-empty]');
        empty?.classList.remove('hidden');
        empty?.classList.add('flex');
    }
}

function formatGameDate(date) {
    return date ? date.split('-').reverse().join('.') : '';
}

function updateGameDates(item, data) {
    for (const [selector, value] of [
        ['[data-game-started-date]', data.started_at],
        ['[data-game-completed-date]', data.completed_at],
    ]) {
        const date = item.querySelector(selector);
        if (!date) continue;
        date.classList.toggle('invisible', !value);
        const dateValue = date.querySelector('[data-game-date-value]');
        if (dateValue) dateValue.textContent = formatGameDate(value);
    }
}

function updateGameStatusItem(form, data) {
    const item = form.closest('[data-game-item]');
    if (!item) return;

    item.dataset.completedAt = data.completed_at || '';

    const badge = item.querySelector('[data-game-status-badge]');
    if (badge) badge.title = data.label;
    const icon = item.querySelector('[data-game-status-icon]');
    if (icon) icon.textContent = data.icon;
    updateGameDates(item, data);

    const visibleStatuses = String(form.dataset.visibleStatuses || '').split(',').filter(Boolean);
    const previousColumn = item.closest('[data-board-status]');

    if (visibleStatuses.length > 0 && !visibleStatuses.includes(data.status)) {
        item.remove();
        updateBoardColumn(previousColumn);
        updateVisibleGameList();
        return;
    }

    const targetColumn = Array.from(document.querySelectorAll('[data-board-status]'))
        .find((section) => section.dataset.boardStatus === data.status);
    if (targetColumn && targetColumn !== previousColumn) {
        const targetContainer = targetColumn.querySelector('[data-board-games]');
        targetContainer?.appendChild(item);
        sortGameItems(targetContainer);
        updateBoardColumn(previousColumn);
        updateBoardColumn(targetColumn);
    } else if (targetColumn) {
        sortGameItems(targetColumn.querySelector('[data-board-games]'));
        updateBoardColumn(targetColumn);
    } else {
        sortGameItems(item.parentElement);
    }

    updateVisibleGameList();
}

document.addEventListener('change', async (event) => {
    const select = event.target.closest('[data-game-status-select]');
    if (!select) return;

    const form = select.closest('[data-game-status-form]');
    const previousStatus = form.dataset.currentStatus;
    const initialTitle = select.title;
    const formData = new FormData(form);
    formData.set('status', select.value);
    select.disabled = true;
    select.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (!response.ok) throw new Error('Status update failed');

        const data = await response.json();
        form.dataset.currentStatus = data.status;
        updateGameStatusItem(form, data);
        select.classList.add('border-emerald-400/60');
        window.setTimeout(() => select.classList.remove('border-emerald-400/60'), 900);
    } catch {
        select.value = previousStatus;
        select.title = 'Не удалось изменить статус. Попробуйте ещё раз.';
        select.classList.add('border-red-400/60');
        window.setTimeout(() => {
            select.title = initialTitle;
            select.classList.remove('border-red-400/60');
        }, 1800);
    } finally {
        select.disabled = false;
        select.removeAttribute('aria-busy');
    }
});

document.addEventListener('keydown', (event) => {
    const screenshotModal = document.querySelector('[data-screenshot-modal]:not(.hidden)');
    if (screenshotModal && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
        event.preventDefault();
        showScreenshot(
            screenshotModal,
            Number(screenshotModal.dataset.screenshotIndex) + (event.key === 'ArrowRight' ? 1 : -1),
        );
        return;
    }

    if (event.key !== 'Escape') return;

    closeScreenshotModal(screenshotModal);

    closeCatalogListDialog(document.querySelector('[data-catalog-list-dialog]:not(.hidden)'));

    closeGameDuplicateDialog(document.querySelector('[data-game-duplicate-dialog]:not(.hidden)'));

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
    syncCatalogBrowserFormFilters(form);
    runCatalogBrowserSearch(query);
});

document.addEventListener('change', (event) => {
    const select = event.target.closest('[data-catalog-browser-genre], [data-catalog-browser-platform]');
    if (!select) return;

    syncCatalogBrowserFormFilters(select.form);
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
    if (browser && (browser.dataset.query || browser.dataset.genre || browser.dataset.platform)) {
        runCatalogBrowserSearch(browser.dataset.query || '', false);
    }
}

function updateGameLibraryStatusOptions(listSelect) {
    const form = listSelect.closest('[data-game-library-add]');
    const statusSelect = form?.querySelector('[data-game-library-status]');
    const selectedList = listSelect.selectedOptions[0];
    if (!statusSelect || !selectedList) return;

    const statuses = selectedList.dataset.statuses.split(',').filter(Boolean);
    const labels = JSON.parse(form.dataset.statusLabels || '{}');
    const selectedStatus = statuses.includes(statusSelect.value)
        ? statusSelect.value
        : selectedList.dataset.defaultStatus;

    statusSelect.replaceChildren(...statuses.map((status) => {
        const option = document.createElement('option');
        option.value = status;
        option.textContent = labels[status] || status;
        option.selected = status === selectedStatus;
        return option;
    }));
}

document.addEventListener('change', (event) => {
    const listSelect = event.target.closest('[data-game-library-list]');
    if (listSelect) updateGameLibraryStatusOptions(listSelect);
});

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
