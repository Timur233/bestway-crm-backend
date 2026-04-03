export type OrderListItem = {
    id: number;
    order_date: string;
    description: string | null;
    total: string | number | null;
    kaspi_code: string | null;
    status: string | null;
    customer_name: string | null;
    customer_phone: string | null;
    customer_adres: string | null;
};

export type OrdersMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type OrdersSummary = {
    orders_count: number;
    total_amount: number;
};

export type OrderListFilters = {
    query: string;
    status: string;
    date_from: string;
    date_to: string;
};

export type OrdersFilterPayload = {
    applied: Partial<OrderListFilters>;
    statuses: string[];
};
