import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import OrderListApp from './order-list/OrderListApp.vue';
import CustomerListApp from './customer-list/CustomerListApp.vue';
import ProductRemainsApp from './product-remains/ProductRemainsApp.vue';
import WhatsappApp from './whatsapp/WhatsappApp.vue';
import WhatsappBotBuilderApp from './whatsapp/WhatsappBotBuilderApp.vue';
import type { CurrentUser } from './shared/types';

const orderListRoot = document.getElementById('order-list-app');

if (orderListRoot) {
    const app = createApp(OrderListApp, {
        ordersEndpoint: orderListRoot.dataset.ordersEndpoint ?? '',
        logoutEndpoint: orderListRoot.dataset.logoutEndpoint ?? '',
        currentUser: JSON.parse(orderListRoot.dataset.currentUser ?? '{}') as CurrentUser,
    });

    app.use(createPinia());
    app.mount(orderListRoot);
}

const customerListRoot = document.getElementById('customer-list-app');

if (customerListRoot) {
    const app = createApp(CustomerListApp, {
        customersEndpoint: customerListRoot.dataset.customersEndpoint ?? '',
        logoutEndpoint: customerListRoot.dataset.logoutEndpoint ?? '',
        currentUser: JSON.parse(customerListRoot.dataset.currentUser ?? '{}') as CurrentUser,
    });

    app.use(createPinia());
    app.mount(customerListRoot);
}

const productRemainsRoot = document.getElementById('product-remains-app');

if (productRemainsRoot) {
    const app = createApp(ProductRemainsApp, {
        remainsEndpoint: productRemainsRoot.dataset.remainsEndpoint ?? '',
        logoutEndpoint: productRemainsRoot.dataset.logoutEndpoint ?? '',
        currentUser: JSON.parse(productRemainsRoot.dataset.currentUser ?? '{}') as CurrentUser,
    });

    app.use(createPinia());
    app.mount(productRemainsRoot);
}

const whatsappRoot = document.getElementById('whatsapp-app');

if (whatsappRoot) {
    const app = createApp(WhatsappApp, {
        conversationsEndpoint: whatsappRoot.dataset.conversationsEndpoint ?? '',
        botStepsEndpoint: whatsappRoot.dataset.botStepsEndpoint ?? '',
        botBuilderPageEndpoint: whatsappRoot.dataset.botBuilderPageEndpoint ?? '',
        logoutEndpoint: whatsappRoot.dataset.logoutEndpoint ?? '',
        currentUser: JSON.parse(whatsappRoot.dataset.currentUser ?? '{}') as CurrentUser,
    });

    app.use(createPinia());
    app.mount(whatsappRoot);
}

const whatsappBotBuilderRoot = document.getElementById('whatsapp-bot-builder-app');

if (whatsappBotBuilderRoot) {
    const app = createApp(WhatsappBotBuilderApp, {
        whatsappPageEndpoint: whatsappBotBuilderRoot.dataset.whatsappPageEndpoint ?? '',
        botStepsEndpoint: whatsappBotBuilderRoot.dataset.botStepsEndpoint ?? '',
        logoutEndpoint: whatsappBotBuilderRoot.dataset.logoutEndpoint ?? '',
        currentUser: JSON.parse(whatsappBotBuilderRoot.dataset.currentUser ?? '{}') as CurrentUser,
    });

    app.use(createPinia());
    app.mount(whatsappBotBuilderRoot);
}
