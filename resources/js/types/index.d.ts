export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    avatar_url?: string | null;
    roles: string[];
    permissions: string[];
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string;
        error?: string;
        order?: {
            id: number;
            order_number: string;
            total_amount: number;
            paid_amount: number;
            change_amount: number;
        };
    };
};
