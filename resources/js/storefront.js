const isMac = /Mac|iPhone|iPad|iPod/.test(navigator.platform) || navigator.userAgent.includes('Macintosh');
const shortcutLabel = isMac ? '⌘K' : 'Ctrl+K';

document.querySelectorAll('[id^="search-shortcut-"]').forEach((badge) => {
    badge.textContent = shortcutLabel;
    const input = document.getElementById(badge.id.replace('shortcut', 'input'));

    const focusSearch = (event) => {
        event.preventDefault();
        input?.focus();
    };

    badge.addEventListener('click', focusSearch);
    badge.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            focusSearch(event);
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key.toLowerCase() === 'k' && (isMac ? event.metaKey : event.ctrlKey)) {
        event.preventDefault();
        (document.getElementById('search-input-desktop') || document.getElementById('search-input-mobile'))?.focus();
    }
});

document.addEventListener('error', (event) => {
    if (event.target.matches?.('img[data-product-image]')) {
        event.target.remove();
    }
}, true);

let catalogRequest;

function formUrl(form) {
    const url = new URL(form.action, window.location.origin);

    new FormData(form).forEach((value, key) => {
        const normalizedValue = String(value).trim();

        if (normalizedValue === '') {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, normalizedValue);
        }
    });

    return url;
}

async function refreshCatalog(url, updateHistory = true) {
    const catalog = document.querySelector('[data-store-catalog]');

    if (!catalog) {
        window.location.assign(url);

        return;
    }

    const mobileFiltersWereOpen = document.querySelector('[data-mobile-catalog-filters]')?.open ?? false;

    catalogRequest?.abort();
    catalogRequest = new AbortController();
    catalog.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: catalogRequest.signal,
        });

        if (!response.ok) {
            throw new Error('Unable to refresh the catalog.');
        }

        const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
        const nextCatalog = nextDocument.querySelector('[data-store-catalog]');

        if (!nextCatalog) {
            throw new Error('The catalog response was incomplete.');
        }

        if (mobileFiltersWereOpen) {
            nextCatalog.querySelector('[data-mobile-catalog-filters]')?.setAttribute('open', '');
        }

        nextCatalog.dataset.storeEntering = 'true';
        catalog.replaceWith(nextCatalog);
        window.requestAnimationFrame(() => delete nextCatalog.dataset.storeEntering);

        if (updateHistory) {
            window.history.pushState({}, '', url);
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            catalog.removeAttribute('aria-busy');
            catalog.querySelector('[data-store-error]')?.remove();
            catalog.insertAdjacentHTML('afterbegin', '<p data-store-error role="alert" class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">The catalog could not refresh. Check your connection and try again.</p>');
        }
    }
}

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-catalog-filter-form], [data-catalog-sort-form]');

    if (!form) {
        return;
    }

    event.preventDefault();
    refreshCatalog(formUrl(form));
});

document.addEventListener('change', (event) => {
    const form = event.target.closest('[data-catalog-filter-form], [data-catalog-sort-form]');

    if (form && event.target.matches('input[type="radio"], select')) {
        refreshCatalog(formUrl(form));
    }
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-catalog-reset], [data-store-catalog] nav a[href]');

    if (!link || new URL(link.href).origin !== window.location.origin) {
        return;
    }

    event.preventDefault();
    refreshCatalog(link.href);
});

window.addEventListener('popstate', () => {
    if (document.querySelector('[data-store-catalog]')) {
        refreshCatalog(window.location.href, false);
    }
});
