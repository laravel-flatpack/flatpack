export type FlatpackLoginProps = {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
};

export type FlatpackListPageProps = {
    entity: string;
};

export type FlatpackFormPageProps = {
    entity: string;
    record: string | null;
    mode: 'create' | 'edit';
};
