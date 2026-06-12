import axios from 'axios';
window.axios = axios;

const appUrl = document.querySelector('meta[name="app-url"]')?.getAttribute('content') || '';
const subFolder = appUrl.includes('//') ? new URL(appUrl).pathname.replace(/\/$/, '') : '';

window.axios.defaults.baseURL = subFolder;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.interceptors.request.use(config => {
    const lat = localStorage.getItem('pos_user_lat')
    const lng = localStorage.getItem('pos_user_lng')
    if (lat && lng) {
        config.headers['X-Latitude'] = lat
        config.headers['X-Longitude'] = lng
    }
    return config
})


