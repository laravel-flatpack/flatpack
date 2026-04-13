import type { FlatpackListServerPagination } from '@/types/data-table';

export type FlatpackListPageProps = {
    entity: string;
    name?: string;
    model?: string;
    icon?: string;
    order?: number;
    schema?: Record<string, unknown> | null;
    /** Rows for the list table (Eloquent attributes matching list column keys). */
    records?: Record<string, unknown>[];
    /** Present on entity list routes with server-side pagination. */
    pagination?: FlatpackListServerPagination;
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
