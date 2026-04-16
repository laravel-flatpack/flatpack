export type FlatpackMenuItem = {
    name: string;
    route: string;
    icon: string;
};

export type FlatpackSecondaryMenu = {
    label?: string;
    items?: FlatpackMenuItem[];
};

export type FlatpackUser = {
    name: string;
    email: string;
    avatar: string;
};

export type FlatpackPageProps = {
    flatpack: {
        quickAction?: FlatpackMenuItem;
        menu: FlatpackMenuItem[] | null;
        secondaryMenu?: FlatpackSecondaryMenu;
        bottomMenu?: FlatpackSecondaryMenu;
        user?: FlatpackUser;
    };
};
