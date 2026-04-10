import { CommandIcon } from 'lucide-react';
import { NavMain } from '@/components/nav-main';
import { NavSecondary } from '@/components/nav-secondary';
import { NavSettings } from '@/components/nav-settings';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type {
    FlatpackMenuItem,
    FlatpackSecondaryMenu,
    FlatpackUser,
} from '@/types/flatpack';

export function AppSidebar({
    variant,
    navigation,
    currentPath,
}: {
    variant: 'sidebar' | 'floating' | 'inset';
    navigation: {
        quickAction?: FlatpackMenuItem;
        menu: FlatpackMenuItem[] | null;
        secondaryMenu?: FlatpackSecondaryMenu;
        bottomMenu?: FlatpackSecondaryMenu;
        pages: {
            dashboard: string;
            login: string;
            logout: string;
        };
        user?: FlatpackUser;
    };
    currentPath: string;
}) {
    const mainMenuItems = Array.isArray(navigation.menu) ? navigation.menu : [];

    return (
        <Sidebar variant={variant} collapsible="offcanvas">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <a
                            href={navigation.pages.dashboard}
                            className="flex items-center gap-2 py-1 data-[slot=sidebar-menu-button]:p-1.5!"
                        >
                            <CommandIcon className="size-5!" />
                            <span className="text-base font-semibold">
                                Flatpack
                            </span>
                        </a>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>
            <SidebarContent>
                <NavMain
                    quickAction={navigation.quickAction}
                    items={[
                        {
                            name: 'Dashboard',
                            route: navigation.pages.dashboard,
                            icon: 'layout-dashboard',
                        },
                        ...mainMenuItems,
                    ]}
                    currentPath={currentPath}
                />
                <NavSecondary
                    label={navigation.secondaryMenu?.label}
                    items={navigation.secondaryMenu?.items}
                    currentPath={currentPath}
                />
                <NavSettings
                    label={navigation.bottomMenu?.label}
                    items={navigation.bottomMenu?.items}
                    currentPath={currentPath}
                    className="mt-auto"
                />
            </SidebarContent>
            <SidebarFooter>
                {navigation.user && (
                    <NavUser
                        user={navigation.user}
                        logoutRoute={navigation.pages.logout}
                    />
                )}
            </SidebarFooter>
        </Sidebar>
    );
}
