export type CurrentUser = {
    name: string;
    email: string;
};

export type AdminNavItem = {
    key: string;
    label: string;
    href: string;
    active?: boolean;
};
