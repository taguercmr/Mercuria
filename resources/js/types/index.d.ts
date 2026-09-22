export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    is_platform_admin?: boolean;
    current_tenant_id?: number | null;
}

export interface Tenant {
    id: number;
    name: string;
    slug?: string;
    plan: string;
    currency: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    tenant?: Tenant | null;
    flash?: {
        success?: string | null;
        error?: string | null;
    };
    app?: {
        name: string;
        mercurialeYear: number;
    };
};
