<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import QRCode from 'qrcode';
import { useOrdersStore } from './stores/orders';
import type { PropType } from 'vue';
import type { AdminNavItem, CurrentUser } from '../shared/types';
import AdminShell from '../shared/AdminShell.vue';
import type { OrderListItem, OrderQuickActions } from './types';

const props = defineProps({
    ordersEndpoint: {
        type: String,
        required: true,
    },
    logoutEndpoint: {
        type: String,
        required: true,
    },
    currentUser: {
        type: Object as PropType<CurrentUser>,
        required: true,
    },
});

const ordersStore = useOrdersStore();

const pageOrdersCount = computed(() => ordersStore.items.length);
const summaryTotalAmount = computed(() => new Intl.NumberFormat('ru-RU').format(ordersStore.summaryTotalAmount));
const pageTotalAmount = computed(() => new Intl.NumberFormat('ru-RU').format(ordersStore.pageTotalAmount));
const totalOrders = computed(() => new Intl.NumberFormat('ru-RU').format(ordersStore.total));
const summaryCount = computed(() => new Intl.NumberFormat('ru-RU').format(ordersStore.summaryCount));
const selectedOrderCode = ref('');
const isQrModalOpen = ref(false);
const qrImageDataUrl = ref('');
const qrModalLoading = ref(false);
const qrModalError = ref('');
const navItems = computed<AdminNavItem[]>(() => [
    { key: 'orders', label: 'Заказы', href: '/order-list', active: true },
    { key: 'customers', label: 'Покупатели', href: '/customer-list', active: false },
    { key: 'remains', label: 'Остатки', href: '/product-remains', active: false },
    { key: 'whatsapp', label: 'WhatsApp', href: '/whatsapp', active: false },
]);

const selectedOrder = computed<OrderListItem | null>(() => {
    if (selectedOrderCode.value === '') {
        return null;
    }

    return ordersStore.items.find((order) => (order.kaspi_code ?? '') === selectedOrderCode.value) ?? null;
});

const selectedOrderActions = computed<OrderQuickActions | null>(() => buildQuickActions(selectedOrder.value));

function onPerPageChange(event: Event): void {
    const target = event.target as HTMLSelectElement | null;

    if (!target) {
        return;
    }

    ordersStore.changePerPage(props.ordersEndpoint, Number(target.value));
}

function applyFilters(): void {
    ordersStore.applyFilters(props.ordersEndpoint);
}

function resetFilters(): void {
    ordersStore.resetFilters(props.ordersEndpoint);
}

function setSorting(sortBy: string): void {
    ordersStore.setSorting(props.ordersEndpoint, sortBy);
}

function sortLabel(sortBy: string): string {
    if (ordersStore.sortBy !== sortBy) {
        return '';
    }

    return ordersStore.sortDirection === 'asc' ? ' ▲' : ' ▼';
}

function buildQuickActions(order: OrderListItem | null): OrderQuickActions | null {
    const code = order?.kaspi_code?.trim();

    if (!code) {
        return null;
    }

    const contactUrl = `https://pay.kaspi.kz/chat?threadId=${encodeURIComponent(code)}&type=CLIENT_SELLER_BY_ORDER&from=orderInfo_pay_web`;
    const currentUrl = new URL(window.location.href);

    currentUrl.searchParams.set('query', code);
    currentUrl.searchParams.set('selected', code);

    return {
        contact_url: contactUrl,
        crm_url: currentUrl.toString(),
        kaspi_order_url: `https://kaspi.kz/merchantcabinet/#/orders/details/${encodeURIComponent(code)}`,
    };
}

function selectOrder(order: OrderListItem): void {
    selectedOrderCode.value = order.kaspi_code ?? '';
}

function isSelectedOrder(order: OrderListItem): boolean {
    return selectedOrderCode.value !== '' && (order.kaspi_code ?? '') === selectedOrderCode.value;
}

function syncSelectedOrderFromUrl(): void {
    const params = new URLSearchParams(window.location.search);
    const query = params.get('query')?.trim() ?? '';
    const selected = params.get('selected')?.trim() ?? '';

    if (query !== '') {
        ordersStore.filters.query = query;
    }

    selectedOrderCode.value = selected;
}

function ensureSelectedOrder(): void {
    if (!ordersStore.items.length) {
        selectedOrderCode.value = '';
        return;
    }

    if (selectedOrder.value) {
        return;
    }

    const exactMatch = selectedOrderCode.value !== ''
        ? ordersStore.items.find((order) => (order.kaspi_code ?? '') === selectedOrderCode.value)
        : null;

    if (exactMatch) {
        selectedOrderCode.value = exactMatch.kaspi_code ?? '';
        return;
    }

    const query = ordersStore.filters.query.trim();
    const queryMatch = query !== ''
        ? ordersStore.items.find((order) => (order.kaspi_code ?? '') === query)
        : null;

    selectedOrderCode.value = (queryMatch ?? ordersStore.items[0]).kaspi_code ?? '';
}

async function openQrModal(): Promise<void> {
    if (!selectedOrderActions.value?.contact_url) {
        return;
    }

    isQrModalOpen.value = true;
    qrModalLoading.value = true;
    qrModalError.value = '';

    try {
        qrImageDataUrl.value = await QRCode.toDataURL(selectedOrderActions.value.contact_url, {
            width: 320,
            margin: 1,
        });
    } catch (error: unknown) {
        qrModalError.value = error instanceof Error ? error.message : 'Не удалось сгенерировать QR.';
    } finally {
        qrModalLoading.value = false;
    }
}

function closeQrModal(): void {
    isQrModalOpen.value = false;
}

onMounted(() => {
    syncSelectedOrderFromUrl();
    ordersStore.fetchOrders(props.ordersEndpoint).then(() => {
        ensureSelectedOrder();
    });
});

watch(() => ordersStore.items, () => {
    ensureSelectedOrder();
});

watch(selectedOrderCode, () => {
    qrImageDataUrl.value = '';
    qrModalError.value = '';
    isQrModalOpen.value = false;
});
</script>

<template>
    <AdminShell
        title="Список заказов"
        subtitle="Временная админ-панель для контроля заказов. Дальше сюда сможем добавить действия по заказу, карточку клиента и статусы."
        :current-user="currentUser"
        :logout-endpoint="logoutEndpoint"
        :nav-items="navItems"
    >
        <template #summary>
            <section class="orders-summary">
            <article class="orders-summary-card">
                <span>Найдено заказов</span>
                <strong>{{ summaryCount }}</strong>
            </article>
            <article class="orders-summary-card">
                <span>На странице</span>
                <strong>{{ pageOrdersCount }}</strong>
            </article>
            <article class="orders-summary-card">
                <span>Сумма найденных</span>
                <strong>{{ summaryTotalAmount }} тг</strong>
            </article>
            <article class="orders-summary-card">
                <span>Сумма на странице</span>
                <strong>{{ pageTotalAmount }} тг</strong>
            </article>
            </section>
        </template>

        <section class="orders-panel">
            <section v-if="selectedOrder && selectedOrderActions" class="orders-focus-card">
                <div class="orders-focus-copy">
                    <div class="orders-focus-header">
                        <div>
                            <p class="orders-focus-kicker">Выбранный заказ</p>
                            <h2>Код {{ selectedOrder.kaspi_code }}</h2>
                        </div>
                        <span class="orders-focus-status">{{ selectedOrder.status || 'Без статуса' }}</span>
                    </div>

                    <div class="orders-focus-meta">
                        <div>
                            <span>Клиент</span>
                            <strong>{{ selectedOrder.customer_name || 'Не указан' }}</strong>
                        </div>
                        <div>
                            <span>Телефон</span>
                            <strong>{{ selectedOrder.customer_phone || 'Не указан' }}</strong>
                        </div>
                        <div>
                            <span>Магазин</span>
                            <strong>{{ selectedOrder.shop_title || 'Не указан' }}</strong>
                        </div>
                        <div>
                            <span>Сумма</span>
                            <strong>{{ selectedOrder.total || '0' }} тг</strong>
                        </div>
                        <div>
                            <span>Дата</span>
                            <strong>{{ selectedOrder.order_date }}</strong>
                        </div>
                    </div>

                    <p v-if="selectedOrder.customer_adres" class="orders-focus-address">
                        {{ selectedOrder.customer_adres }}
                    </p>

                    <div class="orders-focus-actions">
                        <a
                            :href="selectedOrderActions.contact_url || '#'"
                            class="orders-primary-button orders-link-button"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Связаться с клиентом
                        </a>
                        <a
                            :href="selectedOrderActions.kaspi_order_url || '#'"
                            class="orders-secondary-button orders-link-button"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Открыть заказ в Kaspi
                        </a>
                        <button
                            type="button"
                            class="orders-secondary-button"
                            @click="openQrModal"
                        >
                            Показать QR
                        </button>
                    </div>
                </div>
            </section>

            <div class="orders-toolbar">
                <div class="orders-toolbar-main">
                    <input
                        v-model="ordersStore.filters.query"
                        type="search"
                        class="orders-search"
                        placeholder="Поиск по заказам, клиенту, телефону, статусу, коду"
                        autocomplete="off"
                        @keyup.enter="applyFilters"
                    >

                    <select
                        v-model="ordersStore.filters.status"
                        class="orders-page-size"
                    >
                        <option value="">Все статусы</option>
                        <option
                            v-for="status in ordersStore.availableStatuses"
                            :key="status"
                            :value="status"
                        >
                            {{ status }}
                        </option>
                    </select>

                    <input
                        v-model="ordersStore.filters.date_from"
                        type="date"
                        class="orders-page-size"
                    >

                    <input
                        v-model="ordersStore.filters.date_to"
                        type="date"
                        class="orders-page-size"
                    >
                </div>

                <div class="orders-toolbar-actions">
                    <select
                        class="orders-page-size"
                        :value="ordersStore.perPage"
                        @change="onPerPageChange"
                    >
                        <option :value="10">10 / стр</option>
                        <option :value="25">25 / стр</option>
                        <option :value="50">50 / стр</option>
                        <option :value="100">100 / стр</option>
                        <option :value="500">500 / стр</option>
                    </select>

                    <button
                        type="button"
                        class="orders-primary-button"
                        :disabled="ordersStore.loading"
                        @click="applyFilters"
                    >
                        {{ ordersStore.loading ? 'Загрузка...' : 'Применить' }}
                    </button>

                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="ordersStore.loading"
                        @click="resetFilters"
                    >
                        Сбросить
                    </button>
                </div>
            </div>

            <div v-if="ordersStore.error" class="orders-error">
                {{ ordersStore.error }}
            </div>

            <div v-if="ordersStore.loading && !ordersStore.loaded" class="orders-state">
                Загружаю список заказов...
            </div>

            <div v-else class="orders-table-wrap">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th><button type="button" class="table-sort-button" @click="setSorting('id')">ID{{ sortLabel('id') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('order_date')">Дата{{ sortLabel('order_date') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('description')">Описание{{ sortLabel('description') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('total')">Сумма{{ sortLabel('total') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('kaspi_code')">Код{{ sortLabel('kaspi_code') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('shop_title')">Магазин{{ sortLabel('shop_title') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('status')">Статус{{ sortLabel('status') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('customer_name')">Имя{{ sortLabel('customer_name') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('customer_phone')">Телефон{{ sortLabel('customer_phone') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('customer_adres')">Адрес{{ sortLabel('customer_adres') }}</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!ordersStore.items.length">
                            <td colspan="10" class="orders-empty">Заказы не найдены.</td>
                        </tr>

                        <tr
                            v-for="order in ordersStore.items"
                            :key="order.id"
                            :class="{ 'is-selected': isSelectedOrder(order) }"
                            @click="selectOrder(order)"
                        >
                            <td>{{ order.id }}</td>
                            <td>{{ order.order_date }}</td>
                            <td class="orders-description">{{ order.description }}</td>
                            <td>{{ order.total }}</td>
                            <td>{{ order.kaspi_code }}</td>
                            <td>{{ order.shop_title }}</td>
                            <td>{{ order.status }}</td>
                            <td>{{ order.customer_name }}</td>
                            <td>{{ order.customer_phone }}</td>
                            <td>{{ order.customer_adres }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="orders-pagination">
                <p class="orders-pagination-info">
                    Показано
                    <strong>{{ ordersStore.from ?? 0 }}</strong>
                    -
                    <strong>{{ ordersStore.to ?? 0 }}</strong>
                    из
                    <strong>{{ totalOrders }}</strong>
                </p>

                <div class="orders-pagination-controls">
                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="ordersStore.loading || ordersStore.currentPage <= 1"
                        @click="ordersStore.goToPage(ordersEndpoint, ordersStore.currentPage - 1)"
                    >
                        Назад
                    </button>

                    <span class="orders-pagination-page">
                        Страница {{ ordersStore.currentPage }} / {{ ordersStore.lastPage }}
                    </span>

                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="ordersStore.loading || ordersStore.currentPage >= ordersStore.lastPage"
                        @click="ordersStore.goToPage(ordersEndpoint, ordersStore.currentPage + 1)"
                    >
                        Вперед
                    </button>
                </div>
            </div>
        </section>

        <div
            v-if="isQrModalOpen && selectedOrder"
            class="orders-modal-backdrop"
            @click.self="closeQrModal"
        >
            <section class="orders-qr-modal">
                <div class="orders-qr-modal-header">
                    <div>
                        <p class="orders-focus-kicker">QR для заказа</p>
                        <h3>{{ selectedOrder.kaspi_code }}</h3>
                    </div>
                    <button type="button" class="orders-secondary-button" @click="closeQrModal">
                        Закрыть
                    </button>
                </div>

                <div v-if="qrModalLoading" class="orders-state">
                    Генерирую QR...
                </div>
                <div v-else-if="qrModalError" class="orders-error">
                    {{ qrModalError }}
                </div>
                <div v-else class="orders-qr-modal-body">
                    <img :src="qrImageDataUrl" :alt="`QR для заказа ${selectedOrder.kaspi_code}`">
                    <p>Сканируйте QR, если уведомление открыто на компьютере.</p>
                </div>
            </section>
        </div>
    </AdminShell>
</template>
