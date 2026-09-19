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

const storeForm = document.querySelector('[data-store-filters]');
let storeRequest;
let searchTimer;

async function refreshStore(url, updateHistory = true) {
    const results = document.querySelector('[data-store-results]');

    if (!storeForm || !results) {
        return;
    }

    storeRequest?.abort();
    storeRequest = new AbortController();
    results.setAttribute('aria-busy', 'true');
    results.dataset.storePending = 'true';

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: storeRequest.signal,
        });

        if (!response.ok) {
            throw new Error('Unable to refresh the catalog.');
        }

        const documentFragment = new DOMParser().parseFromString(await response.text(), 'text/html');
        const nextResults = documentFragment.querySelector('[data-store-results]');
        const nextForm = documentFragment.querySelector('[data-store-filters]');

        if (!nextResults) {
            throw new Error('The catalog response was incomplete.');
        }

        if (nextForm) {
            storeForm.querySelectorAll('input, select').forEach((control) => {
                if (!control.name) {
                    return;
                }

                const matchingControls = Array.from(nextForm.elements).filter((candidate) => candidate.name === control.name);

                if (control.type === 'radio' || control.type === 'checkbox') {
                    control.checked = matchingControls.some((candidate) => candidate.value === control.value && candidate.checked);
                } else if (matchingControls[0]) {
                    control.value = matchingControls[0].value;
                }
            });
        }

        nextResults.dataset.storeEntering = 'true';
        results.replaceWith(nextResults);
        window.requestAnimationFrame(() => delete nextResults.dataset.storeEntering);

        if (updateHistory) {
            window.history.pushState({}, '', url);
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            results.removeAttribute('aria-busy');
            delete results.dataset.storePending;
            results.querySelector('[data-store-error]')?.remove();
            results.insertAdjacentHTML('afterbegin', '<p data-store-error role="alert" class="mb-5 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">The catalog could not refresh. Your current results are still here.</p>');
        }
    }
}

function storeUrl() {
    const url = new URL(storeForm.action);
    const formData = new FormData(storeForm);

    formData.delete('category_mobile');
    formData.delete('platform_mobile');

    formData.forEach((value, key) => {
        if (String(value).trim() !== '') {
            url.searchParams.set(key, value);
        }
    });

    if (window.matchMedia('(max-width: 1023px)').matches) {
        storeForm.querySelectorAll('[data-store-param]').forEach((control) => {
            if (String(control.value).trim() === '') {
                url.searchParams.delete(control.dataset.storeParam);

                return;
            }

            url.searchParams.set(control.dataset.storeParam, control.value);
        });
    }

    return url;
}

if (storeForm) {
    storeForm.addEventListener('submit', (event) => {
        event.preventDefault();
        refreshStore(storeUrl());
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-store-select]') && event.target.form === storeForm) {
            refreshStore(storeUrl());
        }
    });

    storeForm.addEventListener('input', (event) => {
        if (!event.target.matches('[data-store-search]')) {
            return;
        }

        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => refreshStore(storeUrl()), 350);
    });

    document.addEventListener('click', async (event) => {
        const paginationLink = event.target.closest('[data-store-pagination] a[href], [data-store-ajax-link]');

        if (paginationLink && new URL(paginationLink.href).origin === window.location.origin) {
            event.preventDefault();
            await refreshStore(paginationLink.href);
            document.querySelector('[data-store-results]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    window.addEventListener('popstate', () => refreshStore(window.location.href, false));
}
