import { defineStore } from 'pinia';
import axios from 'axios';
import type { ProductRemainItem, ProductRemainsMeta, ProductRemainsSummary } from '../types';

type ProductRemainsResponse = {
    data: ProductRemainItem[];
    meta: ProductRemainsMeta;
    summary: ProductRemainsSummary;
};

type ProductRemainPayload = {
    title: string;
    quantity: number;
    sku_list: string;
};

type ProductRemainsState = {
    items: ProductRemainItem[];
    loading: boolean;
    loaded: boolean;
    saving: boolean;
    error: string | null;
    successMessage: string | null;
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
    summaryItemsCount: number;
    summaryTotalQuantity: number;
    filters: {
        query: string;
    };
    sortBy: string;
    sortDirection: 'asc' | 'desc';
    drafts: Record<number, ProductRemainPayload>;
    newItem: ProductRemainPayload;
};

const createEmptyItem = (): ProductRemainPayload => ({
    title: '',
    quantity: 0,
    sku_list: '',
});

export const useProductRemainsStore = defineStore('product-remains', {
    state: (): ProductRemainsState => ({
        items: [],
        loading: false,
        loaded: false,
        saving: false,
        error: null,
        successMessage: null,
        currentPage: 1,
        lastPage: 1,
        perPage: 25,
        total: 0,
        from: null,
        to: null,
        summaryItemsCount: 0,
        summaryTotalQuantity: 0,
        filters: {
            query: '',
        },
        sortBy: 'title',
        sortDirection: 'asc',
        drafts: {},
        newItem: createEmptyItem(),
    }),

    actions: {
        syncDrafts(): void {
            const nextDrafts: Record<number, ProductRemainPayload> = {};

            this.items.forEach((item) => {
                nextDrafts[item.id] = {
                    title: item.title,
                    quantity: Number(item.quantity ?? 0),
                    sku_list: item.sku_list,
                };
            });

            this.drafts = nextDrafts;
        },

        async fetchItems(endpoint: string, page?: number): Promise<void> {
            this.loading = true;
            this.error = null;
            const targetPage = page ?? this.currentPage;

            try {
                const response = await axios.get<ProductRemainsResponse>(endpoint, {
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
                this.summaryItemsCount = response.data.summary?.items_count ?? this.total;
                this.summaryTotalQuantity = response.data.summary?.total_quantity ?? 0;
                this.syncDrafts();
                this.loaded = true;
            } catch (error: unknown) {
                this.error = this.resolveErrorMessage(error, 'Не удалось загрузить остатки.');
            } finally {
                this.loading = false;
            }
        },

        async goToPage(endpoint: string, page: number): Promise<void> {
            if (page < 1 || page > this.lastPage || page === this.currentPage) {
                return;
            }

            await this.fetchItems(endpoint, page);
        },

        async changePerPage(endpoint: string, perPage: number): Promise<void> {
            this.perPage = perPage;
            await this.fetchItems(endpoint, 1);
        },

        async applyFilters(endpoint: string): Promise<void> {
            await this.fetchItems(endpoint, 1);
        },

        async resetFilters(endpoint: string): Promise<void> {
            this.filters = {
                query: '',
            };
            this.sortBy = 'title';
            this.sortDirection = 'asc';

            await this.fetchItems(endpoint, 1);
        },

        async setSorting(endpoint: string, sortBy: string): Promise<void> {
            if (this.sortBy === sortBy) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = sortBy;
                this.sortDirection = sortBy === 'quantity' ? 'desc' : 'asc';
            }

            await this.fetchItems(endpoint, 1);
        },

        async createItem(endpoint: string): Promise<void> {
            this.saving = true;
            this.error = null;
            this.successMessage = null;

            try {
                const payload = {
                    ...this.newItem,
                    quantity: Number(this.newItem.quantity ?? 0),
                };

                await axios.post(endpoint, payload);
                this.newItem = createEmptyItem();
                this.successMessage = 'Остаток создан.';
                await this.fetchItems(endpoint, 1);
            } catch (error: unknown) {
                this.error = this.resolveErrorMessage(error, 'Не удалось создать остаток.');
            } finally {
                this.saving = false;
            }
        },

        async saveItem(endpoint: string, id: number): Promise<void> {
            this.saving = true;
            this.error = null;
            this.successMessage = null;

            try {
                const draft = this.drafts[id];
                await axios.put(`${endpoint}/${id}`, {
                    ...draft,
                    quantity: Number(draft.quantity ?? 0),
                });
                this.successMessage = `Остаток #${id} обновлен.`;
                await this.fetchItems(endpoint, this.currentPage);
            } catch (error: unknown) {
                this.error = this.resolveErrorMessage(error, 'Не удалось сохранить остаток.');
            } finally {
                this.saving = false;
            }
        },

        resolveErrorMessage(error: unknown, fallback: string): string {
            if (axios.isAxiosError(error)) {
                return (error.response?.data as { message?: string } | undefined)?.message
                    ?? error.message
                    ?? fallback;
            }

            if (error instanceof Error) {
                return error.message;
            }

            return fallback;
        },
    },
});
