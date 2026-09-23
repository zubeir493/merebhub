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
            <div class="px-4 py-8 text-center">
                <div class="mx-auto grid size-10 place-items-center rounded-full bg-zinc-100 text-zinc-400">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                </div>
                <p class="mt-2 text-xs font-bold text-zinc-900">Your cart is empty</p>
                <p class="mt-0.5 text-[11px] text-zinc-500">Add software to your cart to get started.</p>
            </div>
        `;

        return;
    }

    miniCartItems.innerHTML = items.map((item) => `
        <a href="${escapeHtml(item.url)}" class="flex items-center gap-3 px-4 py-2.5 transition hover:bg-zinc-50">
            <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-md border border-zinc-200 bg-zinc-50 text-xs text-zinc-400">
                ${item.image ? `<img src="${escapeHtml(item.image)}" alt="" class="h-full w-full object-cover">` : `<svg class="size-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>`}
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-xs font-bold text-zinc-900">${escapeHtml(item.option)}</span>
                <span class="mt-0.5 block truncate text-[11px] text-zinc-500">${item.quantity} × ${escapeHtml(item.unit_price)}</span>
            </span>
            <strong class="shrink-0 text-xs font-bold text-zinc-900 tabular-nums">${escapeHtml(item.total)}</strong>
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
    }, 150);
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
