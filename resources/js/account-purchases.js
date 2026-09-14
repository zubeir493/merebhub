document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-reveal-credential], [data-download-asset]');

    if (!button) {
        return;
    }

    const errorMessage = document.querySelector('[data-account-action-error]');

    if (button.dataset.revealed === 'true') {
        const output = document.getElementById(button.dataset.outputId);

        if (output) {
            output.textContent = '';
            output.classList.add('hidden');
        }

        delete button.dataset.revealed;
        button.textContent = 'Reveal license key';

        return;
    }

    button.disabled = true;
    errorMessage?.classList.add('hidden');

    try {
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
            throw new Error('This account action could not be completed. Please try again.');
        }

        const result = await response.json();

        if (isCredentialReveal) {
            const output = document.getElementById(button.dataset.outputId);
            output.textContent = result.credential.secret;
            output.classList.remove('hidden');
            button.dataset.revealed = 'true';
            button.textContent = 'Hide license key';
        } else {
            window.location.assign(result.url);
        }
    } catch {
        if (errorMessage) {
            errorMessage.textContent = 'This account action could not be completed. Please refresh the page and try again.';
            errorMessage.classList.remove('hidden');
        }
    } finally {
        button.disabled = false;
    }
});
