export type FlatpackMenuItem = {
    name: string;
    route: string;
    icon: string;
};

export type FlatpackUser = {
    name: string;
    email: string;
    avatar: string;
};

export type FlatpackPageProps = {
    flatpack: {
        menu: FlatpackMenuItem[];
        secondaryMenu: {
            label?: string;
            items?: FlatpackMenuItem[];
        } | null;
        pages: {
            dashboard: string;
            login: string;
            logout: string;
        };
        user: FlatpackUser | null;
    };
};
