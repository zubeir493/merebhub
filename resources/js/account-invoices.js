document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    if (event.target.closest('[data-print-invoice]')) {
        window.print();
    }
});
