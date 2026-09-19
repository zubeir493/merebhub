function initProductGalleries() {
    document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
        const slides = Array.from(gallery.querySelectorAll('[data-gallery-slide]'));
        const thumbnails = Array.from(gallery.querySelectorAll('[data-gallery-thumb]'));

        if (slides.length === 0) {
            return;
        }

        let activeIndex = 0;

        const render = (nextIndex) => {
            activeIndex = (nextIndex + slides.length) % slides.length;

            slides.forEach((slide, index) => {
                const active = index === activeIndex;
                slide.hidden = !active;
                slide.setAttribute('aria-hidden', String(!active));
            });

            thumbnails.forEach((thumbnail, index) => {
                const active = index === activeIndex;
                thumbnail.setAttribute('aria-pressed', String(active));
                thumbnail.classList.toggle('border-teal-500', active);
                thumbnail.classList.toggle('border-transparent', !active);
                thumbnail.classList.toggle('ring-2', active);
                thumbnail.classList.toggle('ring-teal-500/20', active);
            });

            const counter = gallery.querySelector('[data-gallery-counter]');
            if (counter) {
                counter.textContent = `${activeIndex + 1} / ${slides.length}`;
            }
        };

        gallery.querySelector('[data-gallery-prev]')?.addEventListener('click', () => render(activeIndex - 1));
        gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => render(activeIndex + 1));
        thumbnails.forEach((thumbnail, index) => thumbnail.addEventListener('click', () => render(index)));
        gallery.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                render(activeIndex - 1);
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                render(activeIndex + 1);
            }
        });

        render(0);
    });
}

function initProductTabs() {
    document.querySelectorAll('[data-product-tabs]').forEach((tabGroup) => {
        const tabs = Array.from(tabGroup.querySelectorAll('[data-product-tab]'));
        const panels = Array.from(tabGroup.querySelectorAll('[data-product-panel]'));

        const activate = (name, shouldScroll = false) => {
            tabs.forEach((tab) => {
                const active = tab.dataset.productTab === name;
                tab.setAttribute('aria-selected', String(active));
                tab.classList.toggle('border-teal-500', active);
                tab.classList.toggle('border-transparent', !active);
                tab.classList.toggle('text-zinc-950', active);
                tab.classList.toggle('text-zinc-500', !active);
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.productPanel !== name;
            });

            if (shouldScroll) {
                tabGroup.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        tabs.forEach((tab) => tab.addEventListener('click', () => activate(tab.dataset.productTab)));
        document.querySelectorAll('[data-product-tab-trigger]').forEach((trigger) => {
            trigger.addEventListener('click', () => activate(trigger.dataset.productTabTrigger, true));
        });

        activate(tabGroup.querySelector('[aria-selected="true"]')?.dataset.productTab ?? tabs[0]?.dataset.productTab);
    });
}

initProductGalleries();
initProductTabs();
