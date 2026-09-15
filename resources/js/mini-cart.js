const miniCart = document.querySelector('[data-mini-cart]');
const miniCartToggle = document.querySelector('[data-mini-cart-toggle]');
const miniCartClose = document.querySelector('[data-mini-cart-close]');
const miniCartItems = document.querySelector('[data-mini-cart-items]');
const miniCartTotal = document.querySelector('[data-mini-cart-total]');
const miniCartCountLabel = document.querySelector('[data-mini-cart-count-label]');
const miniCartEndpoint = miniCart?.dataset.endpoint || '/cart/mini';
let miniCartCloseTimer;

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function renderMiniCart(payload) {
    const items = Array.isArray(payload.items) ? payload.items : [];
    const count = Number(payload.cart_count || 0);

    miniCartCountLabel.textContent = `${count} item${count === 1 ? '' : 's'}`;
    miniCartTotal.textContent = payload.total || '0.00 ETB';

    if (items.length === 0) {
        miniCartItems.innerHTML = `
            <div class="px-4 py-9 text-center">
                <div class="mx-auto grid size-12 place-items-center rounded-full bg-zinc-100 text-zinc-500">🛒</div>
                <p class="mt-3 text-sm font-extrabold text-zinc-900">Your cart is empty</p>
                <p class="mt-1 text-xs leading-5 text-zinc-500">Add a product and it will appear here.</p>
            </div>
        `;

        return;
    }

    miniCartItems.innerHTML = items.map((item) => `
        <a href="${escapeHtml(item.url)}" class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-zinc-50">
            <span class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-lg bg-zinc-100 text-lg">
                ${item.image ? `<img src="${escapeHtml(item.image)}" alt="" class="h-full w-full object-cover">` : '🧩'}
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-extrabold text-zinc-900">${escapeHtml(item.name)}</span>
                <span class="mt-0.5 block truncate text-xs font-semibold text-zinc-500">${escapeHtml(item.option)} · ${item.quantity} × ${escapeHtml(item.unit_price)}</span>
            </span>
            <strong class="shrink-0 text-xs font-extrabold text-zinc-900">${escapeHtml(item.total)}</strong>
        </a>
    `).join('');
}

function setMiniCartOpen(isOpen) {
    if (!miniCart || !miniCartToggle) {
        return;
    }

    window.clearTimeout(miniCartCloseTimer);
    miniCartToggle.setAttribute('aria-expanded', String(isOpen));

    if (isOpen) {
        miniCart.hidden = false;
        window.requestAnimationFrame(() => {
            miniCart.classList.remove('translate-y-2', 'scale-[.98]', 'opacity-0');
            miniCart.classList.add('translate-y-0', 'scale-100', 'opacity-100');
        });

        return;
    }

    miniCart.classList.remove('translate-y-0', 'scale-100', 'opacity-100');
    miniCart.classList.add('translate-y-2', 'scale-[.98]', 'opacity-0');
    miniCartCloseTimer = window.setTimeout(() => {
        miniCart.hidden = true;
    }, 200);
}

async function refreshMiniCart() {
    miniCartItems.innerHTML = '<p class="px-4 py-8 text-center text-xs font-semibold text-zinc-500">Updating your cart…</p>';

    try {
        const response = await fetch(miniCartEndpoint, { headers: { Accept: 'application/json' } });
        renderMiniCart(await response.json());
    } catch {
        miniCartItems.innerHTML = '<p class="px-4 py-8 text-center text-xs font-semibold text-rose-600">Cart details are temporarily unavailable.</p>';
    }
}

if (miniCart && miniCartToggle) {
    miniCartToggle.addEventListener('click', (event) => {
        event.preventDefault();
        const isOpen = miniCartToggle.getAttribute('aria-expanded') === 'true';

        setMiniCartOpen(!isOpen);
        if (!isOpen) {
            refreshMiniCart();
        }
    });

    miniCartClose.addEventListener('click', () => setMiniCartOpen(false));

    document.addEventListener('click', (event) => {
        if (!miniCart.contains(event.target) && !miniCartToggle.contains(event.target)) {
            setMiniCartOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMiniCartOpen(false);
        }
    });

    window.addEventListener('merebhub:cart-updated', (event) => {
        renderMiniCart(event.detail);
        setMiniCartOpen(true);
    });

    window.addEventListener('merebhub:cart-error', (event) => {
        setMiniCartOpen(true);
        miniCartItems.innerHTML = `<p class="px-4 py-8 text-center text-sm font-semibold text-rose-600">${escapeHtml(event.detail.message)}</p>`;
    });
}
