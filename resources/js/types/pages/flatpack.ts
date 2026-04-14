import type { FlatpackListServerPagination } from '@/types/data-table';

export type FlatpackListHeaderAction = {
    id: string;
    label: string;
    href: string;
    icon?: string;
    variant?:
        | 'default'
        | 'outline'
        | 'secondary'
        | 'ghost'
        | 'destructive'
        | 'link';
};

export type FlatpackListPageProps = {
    entity: string;
    name?: string;
    model?: string;
    model_key?: string;
    icon?: string;
    order?: number;
    schema?: Record<string, unknown> | null;
    records?: Record<string, unknown>[];
    pagination?: FlatpackListServerPagination;
    search_term?: string;
    flatpack_prefix?: string;
    list_actions?: FlatpackListHeaderAction[];
};

export type FlatpackFormPageProps = {
    entity: string;
    name?: string;
    model?: string;
    icon?: string;
    record: string | null;
    mode: 'create' | 'edit';
    schema?: Record<string, unknown> | null;
};
