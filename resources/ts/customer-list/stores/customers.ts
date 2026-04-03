import { defineStore } from 'pinia';
import axios from 'axios';
import type { CustomerListItem, CustomersMeta, CustomersSummary } from '../types';

type CustomersResponse = {
    data: CustomerListItem[];
    meta: CustomersMeta;
    summary: CustomersSummary;
};

type CustomersState = {
    items: CustomerListItem[];
    loading: boolean;
    loaded: boolean;
    error: string | null;
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
    filters: {
        query: string;
    };
    summaryCustomersCount: number;
    summaryOrdersCount: number;
    summaryTotalSpent: number;
    sortBy: string;
    sortDirection: 'asc' | 'desc';
};

export const useCustomersStore = defineStore('customers', {
    state: (): CustomersState => ({
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
        filters: {
            query: '',
        },
        summaryCustomersCount: 0,
        summaryOrdersCount: 0,
        summaryTotalSpent: 0,
        sortBy: 'id',
        sortDirection: 'desc',
    }),

    actions: {
        async fetchCustomers(endpoint: string, page?: number): Promise<void> {
            this.loading = true;
            this.error = null;
            const targetPage = page ?? this.currentPage;

            try {
                const response = await axios.get<CustomersResponse>(endpoint, {
                    params: {
                        page: targetPage,
                        per_page: this.perPage,
                        query: this.filters.query || undefined,
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
                this.summaryCustomersCount = response.data.summary?.customers_count ?? this.total;
                this.summaryOrdersCount = response.data.summary?.orders_count ?? 0;
                this.summaryTotalSpent = response.data.summary?.total_spent ?? 0;
                this.loaded = true;
            } catch (error: unknown) {
                if (axios.isAxiosError(error)) {
                    this.error = (error.response?.data as { message?: string } | undefined)?.message
                        ?? error.message
                        ?? 'Не удалось загрузить покупателей.';
                } else if (error instanceof Error) {
                    this.error = error.message;
                } else {
                    this.error = 'Не удалось загрузить покупателей.';
                }
            } finally {
                this.loading = false;
            }
        },

        async goToPage(endpoint: string, page: number): Promise<void> {
            if (page < 1 || page > this.lastPage || page === this.currentPage) {
                return;
            }

            await this.fetchCustomers(endpoint, page);
        },

        async changePerPage(endpoint: string, perPage: number): Promise<void> {
            this.perPage = perPage;
            await this.fetchCustomers(endpoint, 1);
        },

        async applyFilters(endpoint: string): Promise<void> {
            await this.fetchCustomers(endpoint, 1);
        },

        async resetFilters(endpoint: string): Promise<void> {
            this.filters = {
                query: '',
            };
            this.sortBy = 'id';
            this.sortDirection = 'desc';

            await this.fetchCustomers(endpoint, 1);
        },

        async setSorting(endpoint: string, sortBy: string): Promise<void> {
            if (this.sortBy === sortBy) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = sortBy;
                this.sortDirection = sortBy === 'id' || sortBy === 'last_order_date' ? 'desc' : 'asc';
            }

            await this.fetchCustomers(endpoint, 1);
        },
    },
});
