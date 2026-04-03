<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useCustomersStore } from './stores/customers';
import type { PropType } from 'vue';
import type { AdminNavItem, CurrentUser } from '../shared/types';
import AdminShell from '../shared/AdminShell.vue';

const props = defineProps({
    customersEndpoint: {
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

const customersStore = useCustomersStore();

const summaryCustomersCount = computed(() => new Intl.NumberFormat('ru-RU').format(customersStore.summaryCustomersCount));
const summaryOrdersCount = computed(() => new Intl.NumberFormat('ru-RU').format(customersStore.summaryOrdersCount));
const summaryTotalSpent = computed(() => new Intl.NumberFormat('ru-RU').format(customersStore.summaryTotalSpent));
const currentPageTotal = computed(() => new Intl.NumberFormat('ru-RU').format(customersStore.total));

const navItems = computed<AdminNavItem[]>(() => [
    { key: 'orders', label: 'Заказы', href: '/order-list', active: false },
    { key: 'customers', label: 'Покупатели', href: '/customer-list', active: true },
    { key: 'remains', label: 'Остатки', href: '/product-remains', active: false },
]);

function onPerPageChange(event: Event): void {
    const target = event.target as HTMLSelectElement | null;

    if (!target) {
        return;
    }

    customersStore.changePerPage(props.customersEndpoint, Number(target.value));
}

function applyFilters(): void {
    customersStore.applyFilters(props.customersEndpoint);
}

function resetFilters(): void {
    customersStore.resetFilters(props.customersEndpoint);
}

function setSorting(sortBy: string): void {
    customersStore.setSorting(props.customersEndpoint, sortBy);
}

function sortLabel(sortBy: string): string {
    if (customersStore.sortBy !== sortBy) {
        return '';
    }

    return customersStore.sortDirection === 'asc' ? ' ▲' : ' ▼';
}

onMounted(() => {
    customersStore.fetchCustomers(props.customersEndpoint);
});
</script>

<template>
    <AdminShell
        title="Покупатели"
        subtitle="Список клиентов CRM с быстрым поиском, количеством заказов, общей суммой покупок и последним заказом."
        :current-user="currentUser"
        :logout-endpoint="logoutEndpoint"
        :nav-items="navItems"
    >
        <template #summary>
            <section class="orders-summary">
                <article class="orders-summary-card">
                    <span>Найдено покупателей</span>
                    <strong>{{ summaryCustomersCount }}</strong>
                </article>
                <article class="orders-summary-card">
                    <span>Всего заказов</span>
                    <strong>{{ summaryOrdersCount }}</strong>
                </article>
                <article class="orders-summary-card">
                    <span>Сумма покупок</span>
                    <strong>{{ summaryTotalSpent }} тг</strong>
                </article>
                <article class="orders-summary-card">
                    <span>В выборке</span>
                    <strong>{{ currentPageTotal }}</strong>
                </article>
            </section>
        </template>

        <section class="orders-panel">
            <div class="orders-toolbar">
                <div class="orders-toolbar-main">
                    <input
                        v-model="customersStore.filters.query"
                        type="search"
                        class="orders-search"
                        placeholder="Поиск по имени, телефону, адресу"
                        autocomplete="off"
                        @keyup.enter="applyFilters"
                    >
                </div>

                <div class="orders-toolbar-actions">
                    <select
                        class="orders-page-size"
                        :value="customersStore.perPage"
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
                        :disabled="customersStore.loading"
                        @click="applyFilters"
                    >
                        {{ customersStore.loading ? 'Загрузка...' : 'Применить' }}
                    </button>

                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="customersStore.loading"
                        @click="resetFilters"
                    >
                        Сбросить
                    </button>
                </div>
            </div>

            <div v-if="customersStore.error" class="orders-error">
                {{ customersStore.error }}
            </div>

            <div v-if="customersStore.loading && !customersStore.loaded" class="orders-state">
                Загружаю список покупателей...
            </div>

            <div v-else class="orders-table-wrap">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th><button type="button" class="table-sort-button" @click="setSorting('id')">ID{{ sortLabel('id') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('customer_name')">Имя{{ sortLabel('customer_name') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('customer_phone')">Телефон{{ sortLabel('customer_phone') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('customer_adres')">Адрес{{ sortLabel('customer_adres') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('orders_count')">Заказов{{ sortLabel('orders_count') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('total_spent')">Сумма покупок{{ sortLabel('total_spent') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('last_order_date')">Последний заказ{{ sortLabel('last_order_date') }}</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!customersStore.items.length">
                            <td colspan="7" class="orders-empty">Покупатели не найдены.</td>
                        </tr>

                        <tr v-for="customer in customersStore.items" :key="customer.id">
                            <td>{{ customer.id }}</td>
                            <td>{{ customer.customer_name }}</td>
                            <td>{{ customer.customer_phone }}</td>
                            <td class="orders-description">{{ customer.customer_adres }}</td>
                            <td>{{ customer.orders_count ?? 0 }}</td>
                            <td>{{ customer.total_spent ?? 0 }}</td>
                            <td>{{ customer.last_order_date ?? 'Нет заказов' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="orders-pagination">
                <p class="orders-pagination-info">
                    Показано
                    <strong>{{ customersStore.from ?? 0 }}</strong>
                    -
                    <strong>{{ customersStore.to ?? 0 }}</strong>
                    из
                    <strong>{{ customersStore.total }}</strong>
                </p>

                <div class="orders-pagination-controls">
                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="customersStore.loading || customersStore.currentPage <= 1"
                        @click="customersStore.goToPage(customersEndpoint, customersStore.currentPage - 1)"
                    >
                        Назад
                    </button>

                    <span class="orders-pagination-page">
                        Страница {{ customersStore.currentPage }} / {{ customersStore.lastPage }}
                    </span>

                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="customersStore.loading || customersStore.currentPage >= customersStore.lastPage"
                        @click="customersStore.goToPage(customersEndpoint, customersStore.currentPage + 1)"
                    >
                        Вперед
                    </button>
                </div>
            </div>
        </section>
    </AdminShell>
</template>
