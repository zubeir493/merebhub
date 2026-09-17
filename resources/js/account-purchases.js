const copyToClipboard = async (value) => {
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
};

const showError = (message) => {
    const errorMessage = document.querySelector('[data-account-action-error]');

    if (!errorMessage) {
        return;
    }

    errorMessage.textContent = message;
    errorMessage.classList.remove('hidden');
};

const setCopiedState = (button) => {
    button.dataset.originalMarkup ??= button.innerHTML;
    button.innerHTML = '<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>';
    button.classList.add('border-teal-200', 'bg-teal-50', 'text-teal-700');
    button.setAttribute('aria-label', 'License key copied');

    window.setTimeout(() => {
        button.innerHTML = button.dataset.originalMarkup;
        button.classList.remove('border-teal-200', 'bg-teal-50', 'text-teal-700');
        button.setAttribute('aria-label', 'Copy license key');
    }, 1800);
};

const openOfflineModal = (trigger) => {
    const modal = document.querySelector('[data-offline-modal]');

    if (!modal) {
        return;
    }

    modal.__lastFocus = trigger;
    modal.removeAttribute('data-mh-hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    modal.querySelector('input[type="file"], [data-offline-close]')?.focus();
};

const closeOfflineModal = () => {
    const modal = document.querySelector('[data-offline-modal]');

    if (!modal) {
        return;
    }

    modal.setAttribute('data-mh-hidden', '');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    modal.__lastFocus?.focus();
};

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-reveal-credential], [data-download-asset], [data-copy-credential], [data-offline-open], [data-offline-close]');

    if (!button) {
        if (event.target.matches('[data-offline-modal]')) {
            closeOfflineModal();
        }

        return;
    }

    if (button.hasAttribute('data-offline-open')) {
        openOfflineModal(button);

        return;
    }

    if (button.hasAttribute('data-offline-close')) {
        closeOfflineModal();

        return;
    }

    const errorMessage = document.querySelector('[data-account-action-error]');
    errorMessage?.classList.add('hidden');
    button.disabled = true;

    try {
        if (button.dataset.licenseKey) {
            await copyToClipboard(button.dataset.licenseKey);
            setCopiedState(button);

            return;
        }

        const isCredentialReveal = button.hasAttribute('data-reveal-credential');
        const response = await fetch(isCredentialReveal ? button.dataset.revealCredential : button.dataset.downloadAsset, {
            method: isCredentialReveal ? 'POST' : 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                ...(isCredentialReveal ? {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                } : {}),
            },
        });

        if (!response.ok) {
            throw new Error('account-action-failed');
        }

        const result = await response.json();

        if (isCredentialReveal) {
            const output = button.closest('td')?.querySelector('[data-license-value]');
            const secret = result.credential.secret;

            if (output) {
                output.textContent = secret;
                output.title = secret;
                output.classList.add('break-all');
            }

            button.dataset.licenseKey = secret;
            button.removeAttribute('data-reveal-credential');
            button.setAttribute('data-copy-credential', '');
            button.setAttribute('aria-label', 'Copy license key');
            button.setAttribute('title', 'Copy license key');

            try {
                await copyToClipboard(secret);
                setCopiedState(button);
            } catch {
                showError('The license key was revealed but could not be copied automatically. Please copy it manually.');
            }

            return;
        }

        window.location.assign(result.url);
    } catch {
        showError('This account action could not be completed. Please refresh the page and try again.');
    } finally {
        button.disabled = false;
    }
});

document.addEventListener('keydown', (event) => {
    const modal = document.querySelector('[data-offline-modal]');

    if (!modal || modal.hasAttribute('data-mh-hidden')) {
        return;
    }

    if (event.key === 'Escape') {
        closeOfflineModal();

        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const focusable = [...modal.querySelectorAll('button, input, [href], select, textarea, [tabindex]:not([tabindex="-1"])')]
        .filter((element) => !element.hasAttribute('disabled'));

    if (focusable.length === 0) {
        return;
    }

    const first = focusable[0];
    const last = focusable.at(-1);

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (! event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
});
