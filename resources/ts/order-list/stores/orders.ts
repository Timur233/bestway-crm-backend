import { defineStore } from 'pinia';
import axios from 'axios';
import type {
    OrderListFilters,
    OrderListItem,
    OrdersFilterPayload,
    OrdersMeta,
    OrdersSummary,
} from '../types';

type OrdersResponse = {
    data: OrderListItem[];
    meta: OrdersMeta;
    summary: OrdersSummary;
    filters: OrdersFilterPayload;
};

type OrdersState = {
    items: OrderListItem[];
    loading: boolean;
    loaded: boolean;
    error: string | null;
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
    summaryCount: number;
    summaryTotalAmount: number;
    filters: OrderListFilters;
    availableStatuses: string[];
    sortBy: string;
    sortDirection: 'asc' | 'desc';
};

export const useOrdersStore = defineStore('orders', {
    state: (): OrdersState => ({
        items: [],
        loading: false,
        loaded: false,
        error: null,
        currentPage: 1,
        lastPage: 1,
        perPage: 25,
        total: 0,
        from: null,
        to: null,
        summaryCount: 0,
        summaryTotalAmount: 0,
        filters: {
            query: '',
            status: '',
            date_from: '',
            date_to: '',
        },
        availableStatuses: [],
        sortBy: 'order_date',
        sortDirection: 'desc',
    }),

    getters: {
        pageTotalAmount(state): number {
            return state.items.reduce((sum, order) => sum + Number(order.total ?? 0), 0);
        },
    },

    actions: {
        async fetchOrders(endpoint: string, page?: number): Promise<void> {
            this.loading = true;
            this.error = null;
            const targetPage = page ?? this.currentPage;

            try {
                const response = await axios.get<OrdersResponse>(endpoint, {
                    params: {
                        page: targetPage,
                        per_page: this.perPage,
                        query: this.filters.query || undefined,
                        status: this.filters.status || undefined,
                        date_from: this.filters.date_from || undefined,
                        date_to: this.filters.date_to || undefined,
                        sort_by: this.sortBy,
                        sort_direction: this.sortDirection,
                    },
                });

                this.items = response.data.data ?? [];
                this.currentPage = response.data.meta?.current_page ?? targetPage;
                this.lastPage = response.data.meta?.last_page ?? 1;
                this.perPage = response.data.meta?.per_page ?? this.perPage;
                this.total = response.data.meta?.total ?? 0;
                this.from = response.data.meta?.from ?? null;
                this.to = response.data.meta?.to ?? null;
                this.summaryCount = response.data.summary?.orders_count ?? this.total;
                this.summaryTotalAmount = response.data.summary?.total_amount ?? 0;
                this.availableStatuses = response.data.filters?.statuses ?? [];
                this.loaded = true;
            } catch (error: unknown) {
                if (axios.isAxiosError(error)) {
                    this.error = (error.response?.data as { message?: string } | undefined)?.message
                        ?? error.message
                        ?? 'Не удалось загрузить заказы.';
                } else if (error instanceof Error) {
                    this.error = error.message;
                } else {
                    this.error = 'Не удалось загрузить заказы.';
                }
            } finally {
                this.loading = false;
            }
        },

        async goToPage(endpoint: string, page: number): Promise<void> {
            if (page < 1 || page > this.lastPage || page === this.currentPage) {
                return;
            }

            await this.fetchOrders(endpoint, page);
        },

        async changePerPage(endpoint: string, perPage: number): Promise<void> {
            this.perPage = perPage;
            await this.fetchOrders(endpoint, 1);
        },

        async applyFilters(endpoint: string): Promise<void> {
            await this.fetchOrders(endpoint, 1);
        },

        async resetFilters(endpoint: string): Promise<void> {
            this.filters = {
                query: '',
                status: '',
                date_from: '',
                date_to: '',
            };
            this.sortBy = 'order_date';
            this.sortDirection = 'desc';

            await this.fetchOrders(endpoint, 1);
        },

        async setSorting(endpoint: string, sortBy: string): Promise<void> {
            if (this.sortBy === sortBy) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = sortBy;
                this.sortDirection = sortBy === 'order_date' ? 'desc' : 'asc';
            }

            await this.fetchOrders(endpoint, 1);
        },
    },
});
