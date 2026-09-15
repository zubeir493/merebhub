function showCartFeedback(form, message, isError = false) {
    const feedback = form.querySelector('[data-cart-feedback]');

    if (!feedback) {
        return;
    }

    feedback.textContent = message;
    feedback.classList.remove('hidden', 'text-teal-700', 'text-rose-600');
    feedback.classList.add(isError ? 'text-rose-600' : 'text-teal-700');
}

function updateCartCount(count) {
    document.querySelectorAll('[data-cart-count]').forEach((element) => {
        element.textContent = count;
        element.classList.toggle('hidden', count < 1);
        element.classList.toggle('grid', count > 0);
        element.setAttribute('aria-label', `${count} item${count === 1 ? '' : 's'} in cart`);
    });
}

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
        showCartFeedback(form, payload.message || 'Added to your cart.');
    } catch (error) {
        showCartFeedback(form, error.message, true);
    } finally {
        button.disabled = false;
    }
});
