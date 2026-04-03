<script setup lang="ts">
import { computed, onMounted } from 'vue';
import type { PropType } from 'vue';
import AdminShell from '../shared/AdminShell.vue';
import type { AdminNavItem, CurrentUser } from '../shared/types';
import { useProductRemainsStore } from './stores/productRemains';

const props = defineProps({
    remainsEndpoint: {
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

const remainsStore = useProductRemainsStore();

const summaryItemsCount = computed(() => new Intl.NumberFormat('ru-RU').format(remainsStore.summaryItemsCount));
const summaryTotalQuantity = computed(() => new Intl.NumberFormat('ru-RU').format(remainsStore.summaryTotalQuantity));
const currentPageCount = computed(() => new Intl.NumberFormat('ru-RU').format(remainsStore.total));
const navItems = computed<AdminNavItem[]>(() => [
    { key: 'orders', label: 'Заказы', href: '/order-list', active: false },
    { key: 'customers', label: 'Покупатели', href: '/customer-list', active: false },
    { key: 'remains', label: 'Остатки', href: '/product-remains', active: true },
    { key: 'whatsapp', label: 'WhatsApp', href: '/whatsapp', active: false },
]);

function onPerPageChange(event: Event): void {
    const target = event.target as HTMLSelectElement | null;

    if (!target) {
        return;
    }

    remainsStore.changePerPage(props.remainsEndpoint, Number(target.value));
}

function applyFilters(): void {
    remainsStore.applyFilters(props.remainsEndpoint);
}

function resetFilters(): void {
    remainsStore.resetFilters(props.remainsEndpoint);
}

function createItem(): void {
    remainsStore.createItem(props.remainsEndpoint);
}

function saveItem(id: number): void {
    remainsStore.saveItem(props.remainsEndpoint, id);
}

function setSorting(sortBy: string): void {
    remainsStore.setSorting(props.remainsEndpoint, sortBy);
}

function sortLabel(sortBy: string): string {
    if (remainsStore.sortBy !== sortBy) {
        return '';
    }

    return remainsStore.sortDirection === 'asc' ? ' ▲' : ' ▼';
}

onMounted(() => {
    remainsStore.fetchItems(props.remainsEndpoint);
});
</script>

<template>
    <AdminShell
        title="Остатки"
        subtitle="Быстрое редактирование групп остатков по SKU. Здесь можно поддерживать quantity и sku_list до переноса каталога товаров в CRM."
        :current-user="currentUser"
        :logout-endpoint="logoutEndpoint"
        :nav-items="navItems"
    >
        <template #summary>
            <section class="orders-summary">
                <article class="orders-summary-card">
                    <span>Найдено позиций</span>
                    <strong>{{ summaryItemsCount }}</strong>
                </article>
                <article class="orders-summary-card">
                    <span>Общий остаток</span>
                    <strong>{{ summaryTotalQuantity }}</strong>
                </article>
                <article class="orders-summary-card">
                    <span>В выборке</span>
                    <strong>{{ currentPageCount }}</strong>
                </article>
            </section>
        </template>

        <section class="orders-panel">
            <div class="orders-toolbar">
                <div class="orders-toolbar-main">
                    <input
                        v-model="remainsStore.filters.query"
                        type="search"
                        class="orders-search"
                        placeholder="Поиск по названию, SKU, ID"
                        autocomplete="off"
                        @keyup.enter="applyFilters"
                    >
                </div>

                <div class="orders-toolbar-actions">
                    <select
                        class="orders-page-size"
                        :value="remainsStore.perPage"
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
                        :disabled="remainsStore.loading"
                        @click="applyFilters"
                    >
                        {{ remainsStore.loading ? 'Загрузка...' : 'Применить' }}
                    </button>

                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="remainsStore.loading"
                        @click="resetFilters"
                    >
                        Сбросить
                    </button>
                </div>
            </div>

            <div v-if="remainsStore.error" class="orders-error">
                {{ remainsStore.error }}
            </div>

            <div v-else-if="remainsStore.successMessage" class="orders-state orders-state-success">
                {{ remainsStore.successMessage }}
            </div>

            <div class="editable-card">
                <div class="editable-card-header">
                    <strong>Новая позиция</strong>
                    <span>Добавляется сразу в таблицу `product_remainds`.</span>
                </div>

                <div class="editable-grid">
                    <input
                        v-model="remainsStore.newItem.title"
                        type="text"
                        class="orders-search"
                        placeholder="Название"
                    >
                    <input
                        v-model.number="remainsStore.newItem.quantity"
                        type="number"
                        min="0"
                        class="orders-page-size"
                        placeholder="Количество"
                    >
                    <textarea
                        v-model="remainsStore.newItem.sku_list"
                        class="editable-textarea"
                        rows="3"
                        placeholder="SKU через запятую, Enter или ;"
                    />
                    <button
                        type="button"
                        class="orders-primary-button"
                        :disabled="remainsStore.saving"
                        @click="createItem"
                    >
                        Создать
                    </button>
                </div>
            </div>

            <div v-if="remainsStore.loading && !remainsStore.loaded" class="orders-state">
                Загружаю остатки...
            </div>

            <div v-else class="orders-table-wrap">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th><button type="button" class="table-sort-button" @click="setSorting('id')">ID{{ sortLabel('id') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('title')">Название{{ sortLabel('title') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('quantity')">Количество{{ sortLabel('quantity') }}</button></th>
                            <th><button type="button" class="table-sort-button" @click="setSorting('sku_list')">SKU{{ sortLabel('sku_list') }}</button></th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!remainsStore.items.length">
                            <td colspan="5" class="orders-empty">Остатки не найдены.</td>
                        </tr>

                        <tr v-for="item in remainsStore.items" :key="item.id">
                            <td>{{ item.id }}</td>
                            <td>
                                <input
                                    v-model="remainsStore.drafts[item.id].title"
                                    type="text"
                                    class="orders-search compact-input"
                                >
                            </td>
                            <td>
                                <input
                                    v-model.number="remainsStore.drafts[item.id].quantity"
                                    type="number"
                                    min="0"
                                    class="orders-page-size compact-input"
                                >
                            </td>
                            <td class="orders-description">
                                <textarea
                                    v-model="remainsStore.drafts[item.id].sku_list"
                                    class="editable-textarea compact-textarea"
                                    rows="3"
                                />
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="orders-primary-button compact-button"
                                    :disabled="remainsStore.saving"
                                    @click="saveItem(item.id)"
                                >
                                    Сохранить
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="orders-pagination">
                <p class="orders-pagination-info">
                    Показано
                    <strong>{{ remainsStore.from ?? 0 }}</strong>
                    -
                    <strong>{{ remainsStore.to ?? 0 }}</strong>
                    из
                    <strong>{{ remainsStore.total }}</strong>
                </p>

                <div class="orders-pagination-controls">
                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="remainsStore.loading || remainsStore.currentPage <= 1"
                        @click="remainsStore.goToPage(remainsEndpoint, remainsStore.currentPage - 1)"
                    >
                        Назад
                    </button>

                    <span class="orders-pagination-page">
                        Страница {{ remainsStore.currentPage }} / {{ remainsStore.lastPage }}
                    </span>

                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="remainsStore.loading || remainsStore.currentPage >= remainsStore.lastPage"
                        @click="remainsStore.goToPage(remainsEndpoint, remainsStore.currentPage + 1)"
                    >
                        Вперед
                    </button>
                </div>
            </div>
        </section>
    </AdminShell>
</template>
