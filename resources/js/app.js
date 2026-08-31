import '../css/app.css';
import '../css/stisla.css';
import './bootstrap';

// Load Stisla app-shell and theme scripts
const loadScript = (src) => {
    const script = document.createElement('script');
    script.src = src;
    document.body.appendChild(script);
};

// Initialize Stisla scripts after DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        loadScript('/assets/js/app-shell.js');
        loadScript('/assets/js/theme.js');
    });
} else {
    loadScript('/assets/js/app-shell.js');
    loadScript('/assets/js/theme.js');
}

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import DataTable from '@/Components/DataTable.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('DataTable', DataTable)
            .mount(el);
    },
    progress: {
        color: '#6777ef',
    },
});
