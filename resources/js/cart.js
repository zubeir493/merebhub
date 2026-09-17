function updateCartCount(count) {
    document.querySelectorAll('[data-cart-count]').forEach((element) => {
        element.textContent = count;
        element.classList.toggle('hidden', count < 1);
        element.classList.toggle('grid', count > 0);
        element.setAttribute('aria-label', `${count} item${count === 1 ? '' : 's'} in cart`);
    });
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
            status.textContent = 'Anyone with this link can add these items to their cart for 7 days.';
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
