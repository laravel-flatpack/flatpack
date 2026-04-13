export type FlatpackListPageProps = {
    entity: string;
    name?: string;
    model?: string;
    icon?: string;
    order?: number;
    schema?: Record<string, unknown> | null;
    /** Rows for the list table (Eloquent attributes matching list column keys). */
    records?: Record<string, unknown>[];
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
