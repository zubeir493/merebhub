function updateCartCount(count) {
    document.querySelectorAll('[data-cart-count]').forEach((element) => {
        element.textContent = count;
        element.classList.toggle('hidden', count < 1);
        element.classList.toggle('grid', count > 0);
        element.setAttribute('aria-label', `${count} item${count === 1 ? '' : 's'} in cart`);
    });
}

function updateCartHeaderCount(count) {
    const headerCount = document.querySelector('[data-cart-header-count]');
    if (headerCount) {
        headerCount.textContent = `${count} ${count === 1 ? 'item' : 'items'}`;
    }
}

function updateCartSummary(payload) {
    if (!payload) return;

    if (payload.cart_count !== undefined) {
        updateCartCount(Number(payload.cart_count || 0));
        updateCartHeaderCount(Number(payload.cart_count || 0));
    }

    const subtotal = document.querySelector('[data-cart-subtotal]');
    if (subtotal && payload.subtotal) {
        subtotal.textContent = payload.subtotal;
    }

    const total = document.querySelector('[data-cart-total]');
    if (total && payload.total) {
        total.textContent = payload.total;
    }

    const discountRow = document.querySelector('[data-cart-discount-row]');
    const discountAmount = document.querySelector('[data-cart-discount]');
    const couponCode = document.querySelector('[data-cart-coupon-code]');

    if (discountRow) {
        if (payload.discount_total) {
            discountRow.classList.remove('hidden');
            if (discountAmount) discountAmount.textContent = `-${payload.discount_total}`;
            if (couponCode && payload.coupon_code) couponCode.textContent = payload.coupon_code;
        } else {
            discountRow.classList.add('hidden');
        }
    }

    if (payload.items && payload.items.length === 0) {
        const cartContent = document.querySelector('[data-cart-content]');
        const cartEmpty = document.querySelector('[data-cart-empty]');
        if (cartContent) cartContent.classList.add('hidden');
        if (cartEmpty) cartEmpty.classList.remove('hidden');
    }
}

async function copyCartLink(value) {
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(value);

        return;
    }

    const field = document.createElement('textarea');
    field.value = value;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.opacity = '0';
    document.body.appendChild(field);
    field.focus();
    field.select();

    if (!document.execCommand('copy')) {
        throw new Error('copy-failed');
    }

    field.remove();
}

// Share Cart
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-share-cart]');

    if (!button) {
        return;
    }

    const status = document.querySelector('[data-share-cart-status]');
    const originalLabel = button.innerHTML;
    button.disabled = true;
    status?.classList.add('hidden');

    try {
        const response = await fetch(button.dataset.shareCart, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Unable to create a shared cart link.');
        }

        await copyCartLink(payload.url);
        button.innerHTML = '✓ Link copied';

        if (status) {
            status.textContent = 'Share link copied! Anyone with this link can add these items to their cart.';
            status.classList.remove('hidden', 'text-rose-700');
            status.classList.add('text-emerald-700');
        }

        window.setTimeout(() => {
            button.innerHTML = originalLabel;
            status?.classList.add('hidden');
        }, 2600);
    } catch (error) {
        if (status) {
            status.textContent = error.message || 'Unable to create a shared cart link.';
            status.classList.remove('hidden', 'text-emerald-700');
            status.classList.add('text-rose-700');
        }
    } finally {
        button.disabled = false;
    }
});

// Add to Cart
document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-add-to-cart]');

    if (!form) {
        return;
    }

    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Unable to add this item to your cart.');
        }

        updateCartCount(Number(payload.cart_count || 0));
        window.dispatchEvent(new CustomEvent('merebhub:cart-updated', { detail: payload }));
    } catch (error) {
        window.dispatchEvent(new CustomEvent('merebhub:cart-error', { detail: { message: error.message } }));
    } finally {
        button.disabled = false;
    }
});

// Quantity Steppers
document.addEventListener('click', async (event) => {
    const qtyBtn = event.target.closest('[data-cart-qty-btn]');
    if (!qtyBtn) return;

    const lineId = qtyBtn.dataset.lineId;
    const url = qtyBtn.dataset.url;
    const type = qtyBtn.dataset.cartQtyBtn;
    const display = document.querySelector(`[data-cart-qty-display="${lineId}"]`);
    if (!display || !url) return;

    const currentQty = parseInt(display.textContent, 10) || 1;
    const newQty = type === 'inc' ? currentQty + 1 : currentQty - 1;

    if (newQty < 1 || newQty > 10) return;

    const parentRow = qtyBtn.closest('[data-cart-row]');
    const allRowBtns = parentRow ? parentRow.querySelectorAll('[data-cart-qty-btn], [data-cart-remove]') : [];
    allRowBtns.forEach((b) => (b.disabled = true));

    try {
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ quantity: newQty }),
        });

        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.message || 'Unable to update cart.');
        }

        display.textContent = newQty;

        const decBtn = parentRow?.querySelector('[data-cart-qty-btn="dec"]');
        const incBtn = parentRow?.querySelector('[data-cart-qty-btn="inc"]');
        if (decBtn) decBtn.disabled = newQty <= 1;
        if (incBtn) incBtn.disabled = newQty >= 10;

        updateCartSummary(payload);
        window.dispatchEvent(new CustomEvent('merebhub:cart-updated', { detail: payload }));
    } catch (error) {
        console.error('Failed to update cart quantity:', error);
    } finally {
        allRowBtns.forEach((b) => {
            if (b.dataset.cartQtyBtn === 'dec') {
                const q = parseInt(display.textContent, 10) || 1;
                b.disabled = q <= 1;
            } else if (b.dataset.cartQtyBtn === 'inc') {
                const q = parseInt(display.textContent, 10) || 1;
                b.disabled = q >= 10;
            } else {
                b.disabled = false;
            }
        });
    }
});

// Remove Item from Cart
document.addEventListener('click', async (event) => {
    const removeBtn = event.target.closest('[data-cart-remove]');
    if (!removeBtn) return;

    const lineId = removeBtn.dataset.cartRemove;
    const url = removeBtn.dataset.url;
    const row = document.querySelector(`[data-cart-row="${lineId}"]`);
    if (!url) return;

    removeBtn.disabled = true;

    try {
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        });

        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.message || 'Unable to remove item.');
        }

        if (row) {
            row.remove();
        }

        updateCartSummary(payload);
        window.dispatchEvent(new CustomEvent('merebhub:cart-updated', { detail: payload }));
    } catch (error) {
        console.error('Failed to remove cart item:', error);
        removeBtn.disabled = false;
    }
});

// Apply Coupon
document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-cart-coupon-form]');
    if (!form) return;

    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    const input = form.querySelector('input[name="coupon_code"]');
    const errorEl = document.querySelector('[data-cart-coupon-error]');

    if (!input || !input.value.trim()) return;

    if (button) button.disabled = true;
    if (errorEl) {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ coupon_code: input.value.trim() }),
        });

        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Unable to apply coupon.');
        }

        input.value = '';
        updateCartSummary(payload);
        window.dispatchEvent(new CustomEvent('merebhub:cart-updated', { detail: payload }));
    } catch (error) {
        if (errorEl) {
            errorEl.textContent = error.message;
            errorEl.classList.remove('hidden');
        }
    } finally {
        if (button) button.disabled = false;
    }
});

// Remove Coupon
document.addEventListener('click', async (event) => {
    const removeCouponBtn = event.target.closest('[data-remove-coupon]');
    if (!removeCouponBtn) return;

    const url = removeCouponBtn.dataset.url;
    if (!url) return;

    removeCouponBtn.disabled = true;

    try {
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        });

        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.message || 'Unable to remove coupon.');
        }

        updateCartSummary(payload);
        window.dispatchEvent(new CustomEvent('merebhub:cart-updated', { detail: payload }));
    } catch (error) {
        console.error('Failed to remove coupon:', error);
    } finally {
        removeCouponBtn.disabled = false;
    }
});
