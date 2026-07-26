document.addEventListener('DOMContentLoaded', () => {
    window.lucide?.createIcons({
        attrs: {
            'stroke-width': 1.8,
        },
    });

    const menuToggle = document.querySelector('.mh-menu-toggle');
    const mobileNav = document.querySelector('.mh-mobile-nav');
    const accountSlot = document.querySelector('.mh-account-slot');

    menuToggle?.addEventListener('click', () => {
        const open = mobileNav?.classList.toggle('is-open') ?? false;
        menuToggle.setAttribute('aria-expanded', String(open));
    });

    const bindAccountMenu = () => {
        const accountToggle = accountSlot?.querySelector('.mh-account__toggle');
        const account = accountSlot?.querySelector('.mh-account');

        accountToggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = account?.classList.toggle('is-open') ?? false;
            accountToggle.setAttribute('aria-expanded', String(open));
        });
    };

    bindAccountMenu();

    if (accountSlot?.dataset.accountStatusUrl) {
        fetch(accountSlot.dataset.accountStatusUrl, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
            },
        })
            .then((response) => response.json())
            .then((payload) => {
                if (!payload?.success || !payload.data?.html) return;

                accountSlot.innerHTML = payload.data.html;
                window.lucide?.createIcons({
                    attrs: {
                        'stroke-width': 1.8,
                    },
                });
                bindAccountMenu();
            })
            .catch(() => {});
    }

    document.addEventListener('click', () => {
        const account = accountSlot?.querySelector('.mh-account');
        const accountToggle = accountSlot?.querySelector('.mh-account__toggle');

        account?.classList.remove('is-open');
        accountToggle?.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-mh-quantity]');
        if (!button) return;

        const input = button.closest('.mh-quantity-control')?.querySelector('input.qty');
        if (!input) return;

        const step = Number(input.step || 1);
        const min = Number(input.min || 1);
        const max = input.max ? Number(input.max) : Number.MAX_SAFE_INTEGER;
        const direction = button.dataset.mhQuantity === 'increase' ? 1 : -1;
        input.value = Math.min(max, Math.max(min, Number(input.value || min) + direction * step));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
});
