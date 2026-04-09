export type FlatpackMenuItem = {
    slug: string;
    name: string;
    route: string;
    icon: string;
};

export type FlatpackPageProps = {
    flatpack?: {
        dashboardRoute?: string;
        loginStoreRoute?: string;
        menu?: FlatpackMenuItem[];
    };
};
