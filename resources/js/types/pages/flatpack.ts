export type FlatpackListPageProps = {
    entity: string;
    name?: string;
    model?: string;
    icon?: string;
};

export type FlatpackFormPageProps = {
    entity: string;
    name?: string;
    model?: string;
    icon?: string;
    record: string | null;
    mode: 'create' | 'edit';
};
