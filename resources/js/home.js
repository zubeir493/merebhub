const initializeHome = () => {
    const home = document.querySelector('[data-home]');

    if (!home || home.dataset.homeInitialized === 'true') {
        return;
    }

    home.dataset.homeInitialized = 'true';

    const localBuild = home.querySelector('[data-home-local-build]');
    const spotlight = localBuild?.querySelector('[data-home-pattern-spotlight]');

    if (localBuild && spotlight && localBuild.dataset.homePatternInitialized !== 'true') {
        localBuild.dataset.homePatternInitialized = 'true';

        let hideTimer;

        const isFinePointer = (event) => event.pointerType === 'mouse' || event.pointerType === 'pen';

        const revealPatternAt = (event) => {
            const bounds = localBuild.getBoundingClientRect();
            const x = Math.min(100, Math.max(0, ((event.clientX - bounds.left) / bounds.width) * 100));
            const y = Math.min(100, Math.max(0, ((event.clientY - bounds.top) / bounds.height) * 100));

            localBuild.style.setProperty('--home-pattern-x', `${x}%`);
            localBuild.style.setProperty('--home-pattern-y', `${y}%`);
            localBuild.dataset.homePatternActive = 'true';
        };

        const hidePattern = () => {
            window.clearTimeout(hideTimer);
            localBuild.dataset.homePatternActive = 'false';
        };

        localBuild.addEventListener('pointerenter', (event) => {
            if (isFinePointer(event)) {
                revealPatternAt(event);
            }
        });

        localBuild.addEventListener('pointermove', (event) => {
            if (isFinePointer(event)) {
                revealPatternAt(event);
            }
        });

        localBuild.addEventListener('pointerleave', (event) => {
            if (isFinePointer(event)) {
                hidePattern();
            }
        });

        localBuild.addEventListener('pointerdown', (event) => {
            if (event.pointerType !== 'touch') {
                return;
            }

            revealPatternAt(event);
            window.clearTimeout(hideTimer);
            hideTimer = window.setTimeout(hidePattern, 900);
        });
    }

    const triggers = home.querySelectorAll('[data-exhibition-trigger]');
    const previews = home.querySelectorAll('[data-exhibition-preview]');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const rotationDuration = 10000;
    let activeIndex = 0;
    let rotationTimer;

    const scheduleNextPreview = () => {
        window.clearTimeout(rotationTimer);

        if (!home.isConnected || prefersReducedMotion || triggers.length < 2) {
            return;
        }

        rotationTimer = window.setTimeout(() => {
            activatePreview((activeIndex + 1) % triggers.length);
        }, rotationDuration);
    };

    const activatePreview = (index) => {
        activeIndex = Number(index);

        triggers.forEach((trigger, triggerIndex) => {
            const isActive = triggerIndex === activeIndex;

            trigger.setAttribute('aria-current', isActive ? 'true' : 'false');
            trigger.removeAttribute('data-progressing');

            if (isActive) {
                void trigger.offsetWidth;
                trigger.setAttribute('data-progressing', 'true');
            }
        });

        previews.forEach((preview) => {
            const isActive = Number(preview.dataset.exhibitionPreview) === activeIndex;
            preview.hidden = !isActive;
            preview.dataset.active = isActive ? 'true' : 'false';
        });

        scheduleNextPreview();
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('pointerenter', () => activatePreview(trigger.dataset.exhibitionTrigger));
        trigger.addEventListener('focus', () => activatePreview(trigger.dataset.exhibitionTrigger));
    });

    activatePreview(0);
};

document.addEventListener('DOMContentLoaded', initializeHome);
document.addEventListener('livewire:navigated', initializeHome);
