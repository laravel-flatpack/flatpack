export type FlatpackMenuItem = {
    name: string;
    url: string;
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

export type FlatpackBreadcrumb = {
    label: string;
    href: string | null;
};

export type FlatpackPageProps = {
    flatpack: {
        quickAction?: FlatpackMenuItem;
        menu: FlatpackMenuItem[] | null;
        secondaryMenu?: FlatpackSecondaryMenu;
        bottomMenu?: FlatpackSecondaryMenu;
        /** When true, header action buttons show shortcut chips inline. Default from config `flatpack.ui.show_action_shortcut_hints`. */
        showActionShortcutHints?: boolean;
        /** Current page trail: last item is the active page (`href` null). */
        breadcrumbs?: FlatpackBreadcrumb[];
        user?: FlatpackUser;
    };
};
