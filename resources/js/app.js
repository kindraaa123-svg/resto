// import './boost-logger';
import './bootstrap';
import './echo';
import '../css/app.css';
import Layout from '@/Layouts/Layout.vue';

import { createApp, h } from 'vue';
import { createInertiaApp, usePage, router } from '@inertiajs/vue3'
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { route } from 'ziggy-js';
import VueApexCharts from 'vue3-apexcharts';

window.route = route;

router.on('before', (event) => {
    const lat = localStorage.getItem('pos_user_lat')
    const lng = localStorage.getItem('pos_user_lng')
    if (lat && lng) {
        event.detail.visit.headers = event.detail.visit.headers || {}
        event.detail.visit.headers['X-Latitude'] = lat
        event.detail.visit.headers['X-Longitude'] = lng
    }
})

createInertiaApp({
    title: title => `${title} - ${usePage().props.system?.name || 'POS'}`,
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        const page = pages[`./Pages/${name}.vue`];
        if (!page) return;
        page.default.layout = page.default.layout || Layout;
        return page;
    },
    setup({ el, App, props, plugin }) {
        window.Ziggy = props.initialPage.props.ziggy;
        
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, props.initialPage.props.ziggy)
            .use(VueApexCharts)
            .mount(el)
    },
})