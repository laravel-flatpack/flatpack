export type FlatpackMenuItem = {
    slug: string;
    name: string;
    route: string;
    icon: string;
};

export type FlatpackPageProps = {
    flatpack: {
        menu: FlatpackMenuItem[];
        pages: {
            dashboard: string;
            login: string;
            logout: string;
        };
    };
};
