export type ProductRemainItem = {
    id: number;
    title: string;
    quantity: number;
    sku_list: string;
};

export type ProductRemainsMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type ProductRemainsSummary = {
    items_count: number;
    total_quantity: number;
};
