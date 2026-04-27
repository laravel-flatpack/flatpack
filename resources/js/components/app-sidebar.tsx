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
import { route } from '@/lib/route';
import type {
    FlatpackMenuItem,
    FlatpackSecondaryMenu,
    FlatpackUser,
} from '@/types/flatpack';
import AppLogoIcon from './app-logo-icon';

export function AppSidebar({
    currentPath,
    logo,
    displayName,
    navigation,
    variant,
}: {
    currentPath: string;
    logo: string | null;
    displayName: string;
    navigation: {
        quickAction?: FlatpackMenuItem;
        menu: FlatpackMenuItem[] | null;
        secondaryMenu?: FlatpackSecondaryMenu;
        bottomMenu?: FlatpackSecondaryMenu;
        user?: FlatpackUser;
    };
    variant: 'sidebar' | 'floating' | 'inset';
}) {
    const mainMenuItems = Array.isArray(navigation.menu) ? navigation.menu : [];

    return (
        <Sidebar variant={variant} collapsible="offcanvas">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <a
                            href={route('flatpack.dashboard')}
                            className="flex items-center gap-2 py-1 text-foreground data-[slot=sidebar-menu-button]:p-1.5!"
                        >
                            {logo && <AppLogoIcon src={logo} />}
                            <span className="text-base font-semibold">
                                {displayName}
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
                            url: route('flatpack.dashboard'),
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
                {navigation.user && <NavUser user={navigation.user} />}
            </SidebarFooter>
        </Sidebar>
    );
}
