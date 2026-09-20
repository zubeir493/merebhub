import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import './account-invoices.js';
import './account-purchases.js';
import './mini-cart.js';
import './cart.js';
import './home.js';
import './product-detail.js';
import './storefront.js';

createInertiaApp({
    title: (title) => `${title} · MerebHub`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
