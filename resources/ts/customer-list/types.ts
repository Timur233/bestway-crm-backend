export type CustomerListItem = {
    id: number;
    customer_name: string | null;
    customer_phone: string | null;
    customer_adres: string | null;
    orders_count: number | string | null;
    total_spent: number | string | null;
    last_order_date: string | null;
};

export type CustomersMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type CustomersSummary = {
    customers_count: number;
    orders_count: number;
    total_spent: number;
};
