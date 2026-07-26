document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.mh-menu-toggle');
    const mobileNav = document.querySelector('.mh-mobile-nav');
    const accountToggle = document.querySelector('.mh-account__toggle');
    const account = document.querySelector('.mh-account');

    menuToggle?.addEventListener('click', () => {
        const open = mobileNav?.classList.toggle('is-open') ?? false;
        menuToggle.setAttribute('aria-expanded', String(open));
    });

    accountToggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = account?.classList.toggle('is-open') ?? false;
        accountToggle.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', () => {
        account?.classList.remove('is-open');
        accountToggle?.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-mh-quantity]');
        if (!button) return;

        const input = button.closest('.quantity')?.querySelector('input.qty');
        if (!input) return;

        const step = Number(input.step || 1);
        const min = Number(input.min || 1);
        const max = input.max ? Number(input.max) : Number.MAX_SAFE_INTEGER;
        const direction = button.dataset.mhQuantity === 'increase' ? 1 : -1;
        input.value = Math.min(max, Math.max(min, Number(input.value || min) + direction * step));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
});
