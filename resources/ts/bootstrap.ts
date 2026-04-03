import axios from 'axios';
import _ from 'lodash';

declare global {
    interface Window {
        _: typeof _;
        axios: typeof axios;
    }
}

window._ = _;
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
