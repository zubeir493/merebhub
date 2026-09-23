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

    const subtotalRow = miniCart?.querySelector('[data-mini-cart-subtotal-row]');
    const subtotalEl = miniCart?.querySelector('[data-mini-cart-subtotal]');
    const discountRow = miniCart?.querySelector('[data-mini-cart-discount-row]');
    const discountEl = miniCart?.querySelector('[data-mini-cart-discount]');
    const couponLabel = miniCart?.querySelector('[data-mini-cart-coupon-label]');
    const couponSection = miniCart?.querySelector('[data-mini-cart-coupon-section]');
    const couponForm = miniCart?.querySelector('[data-mini-cart-coupon-form]');
    const appliedCoupon = miniCart?.querySelector('[data-mini-cart-applied-coupon]');
    const appliedCode = miniCart?.querySelector('[data-mini-cart-applied-code]');
    const couponError = miniCart?.querySelector('[data-mini-cart-coupon-error]');

    if (couponError) {
        couponError.classList.add('hidden');
        couponError.textContent = '';
    }

    if (couponSection) {
        couponSection.classList.toggle('hidden', items.length === 0);
    }

    if (payload.discount_total && payload.coupon_code) {
        if (subtotalRow && subtotalEl) {
            subtotalRow.classList.remove('hidden');
            subtotalRow.classList.add('flex');
            subtotalEl.textContent = payload.subtotal || payload.total;
        }
        if (discountRow && discountEl) {
            discountRow.classList.remove('hidden');
            discountRow.classList.add('flex');
            discountEl.textContent = `-${payload.discount_total}`;
            if (couponLabel) {
                couponLabel.textContent = `Coupon (${payload.coupon_code})`;
            }
        }
        if (couponForm) couponForm.classList.add('hidden');
        if (appliedCoupon) {
            appliedCoupon.classList.remove('hidden');
            appliedCoupon.classList.add('flex');
            if (appliedCode) appliedCode.textContent = `${payload.coupon_code} (-${payload.discount_total})`;
        }
    } else {
        if (subtotalRow) {
            subtotalRow.classList.add('hidden');
            subtotalRow.classList.remove('flex');
        }
        if (discountRow) {
            discountRow.classList.add('hidden');
            discountRow.classList.remove('flex');
        }
        if (couponForm) {
            couponForm.classList.remove('hidden');
            couponForm.reset();
        }
        if (appliedCoupon) {
            appliedCoupon.classList.add('hidden');
            appliedCoupon.classList.remove('flex');
        }
    }

    if (items.length === 0) {
        miniCartItems.innerHTML = `
            <div class="px-4 py-9 text-center">
                <div class="mx-auto grid size-12 place-items-center rounded-full bg-zinc-100 text-zinc-500">🛒</div>
            <div class="px-5 py-10 text-center">
                <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-zinc-100 text-zinc-400">
                    <svg class="size-6 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
                <p class="mt-3 text-sm font-extrabold text-zinc-900">Your cart is empty</p>
                <p class="mt-1 text-xs leading-5 text-zinc-500">Add a product and it will appear here.</p>
                <p class="mt-1 text-xs leading-5 text-zinc-500">Add software or developer tools and they will appear here.</p>
            </div>
        `;

        return;
    }

    miniCartItems.innerHTML = items.map((item) => `
        <a href="${escapeHtml(item.url)}" class="flex items-center gap-3 p-2 transition hover:bg-zinc-50">
            <span class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-lg bg-zinc-100 text-lg">
                ${item.image ? `<img src="${escapeHtml(item.image)}" alt="" class="h-full w-full object-cover">` : '🧩'}
        <a href="${escapeHtml(item.url)}" class="group flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition duration-150 hover:bg-zinc-50">
            <span class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-xl border border-zinc-200/80 bg-zinc-100 text-zinc-400 shadow-2xs">
                ${item.image ? `<img src="${escapeHtml(item.image)}" alt="" class="h-full w-full object-cover">` : `<svg class="size-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>`}
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-extrabold text-zinc-900">${escapeHtml(item.option)}</span>
                <span class="mt-0.5 block truncate text-xs font-semibold text-zinc-500">${item.quantity} × ${escapeHtml(item.unit_price)}</span>
                <span class="block truncate text-sm font-bold text-zinc-950 group-hover:text-teal-700 transition-colors">${escapeHtml(item.option)}</span>
                <span class="mt-0.5 flex items-center gap-1.5 text-xs text-zinc-500">
                    <span class="font-medium text-zinc-600">${item.quantity} ×</span>
                    <span class="tabular-nums">${escapeHtml(item.unit_price)}</span>
                </span>
            </span>
            <strong class="shrink-0 text-xs font-extrabold text-zinc-900">${escapeHtml(item.total)}</strong>
            <strong class="shrink-0 text-sm font-extrabold text-zinc-950 tabular-nums">${escapeHtml(item.total)}</strong>
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
    miniCartItems.innerHTML = '<p class="px-5 py-8 text-center text-xs font-semibold text-zinc-500">Updating your cart…</p>';

    try {
        const response = await fetch(miniCartEndpoint, { headers: { Accept: 'application/json' } });
        renderMiniCart(await response.json());
    } catch {
        miniCartItems.innerHTML = '<p class="px-4 py-8 text-center text-xs font-semibold text-rose-600">Cart details are temporarily unavailable.</p>';
        miniCartItems.innerHTML = '<p class="px-5 py-8 text-center text-xs font-semibold text-rose-600">Cart details are temporarily unavailable.</p>';
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

    const couponForm = miniCart.querySelector('[data-mini-cart-coupon-form]');
    if (couponForm) {
        couponForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitBtn = couponForm.querySelector('button[type="submit"]');
            const errorEl = miniCart.querySelector('[data-mini-cart-coupon-error]');
            const input = couponForm.querySelector('input[name="coupon_code"]');
            const code = input?.value?.trim() || '';

            if (!code) {
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
            }
            if (errorEl) {
                errorEl.classList.add('hidden');
                errorEl.textContent = '';
            }

            try {
                const response = await fetch(couponForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ coupon_code: code }),
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Invalid coupon code.');
                }

                renderMiniCart(payload);
            } catch (err) {
                if (errorEl) {
                    errorEl.textContent = err.message || 'Unable to apply coupon.';
                    errorEl.classList.remove('hidden');
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            }
        });
    }

    miniCart.addEventListener('click', async (event) => {
        const removeBtn = event.target.closest('[data-mini-cart-remove-coupon]');
        if (!removeBtn) {
            return;
        }

        event.preventDefault();
        removeBtn.disabled = true;

        try {
            const response = await fetch(removeBtn.dataset.url, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            });
            const payload = await response.json();
            if (response.ok) {
                renderMiniCart(payload);
            }
        } catch {
            // no-op
        } finally {
            removeBtn.disabled = false;
        }
    });
}
