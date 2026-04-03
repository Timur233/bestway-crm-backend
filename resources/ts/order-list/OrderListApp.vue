<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useOrdersStore } from './stores/orders';
import type { PropType } from 'vue';
import type { AdminNavItem, CurrentUser } from '../shared/types';
import AdminShell from '../shared/AdminShell.vue';

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
const navItems = computed<AdminNavItem[]>(() => [
    { key: 'orders', label: 'Заказы', href: '/order-list', active: true },
    { key: 'customers', label: 'Покупатели', href: '/customer-list', active: false },
    { key: 'remains', label: 'Остатки', href: '/product-remains', active: false },
    { key: 'whatsapp', label: 'WhatsApp', href: '/whatsapp', active: false },
]);

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

onMounted(() => {
    ordersStore.fetchOrders(props.ordersEndpoint);
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
                            <th><button type="button" class="table-sort-button" @click="setSorting('status')">Статус{{ sortLabel('status') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('customer_name')">Имя{{ sortLabel('customer_name') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('customer_phone')">Телефон{{ sortLabel('customer_phone') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('customer_adres')">Адрес{{ sortLabel('customer_adres') }}</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!ordersStore.items.length">
                            <td colspan="9" class="orders-empty">Заказы не найдены.</td>
                        </tr>

                        <tr v-for="order in ordersStore.items" :key="order.id">
                            <td>{{ order.id }}</td>
                            <td>{{ order.order_date }}</td>
                            <td class="orders-description">{{ order.description }}</td>
                            <td>{{ order.total }}</td>
                            <td>{{ order.kaspi_code }}</td>
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
    </AdminShell>
</template>
