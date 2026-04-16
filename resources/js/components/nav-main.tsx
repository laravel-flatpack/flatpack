import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { isEntityListNavActive } from '@/lib/utils';
import type { FlatpackMenuItem } from '@/types/flatpack';
import { LucideIconByName } from './icons';

export function NavMain({
    quickAction,
    items,
    currentPath,
}: {
    quickAction?: FlatpackMenuItem;
    items: FlatpackMenuItem[];
    currentPath: string;
}) {
    return (
        <SidebarGroup>
            <SidebarGroupContent className="flex flex-col gap-2">
                {quickAction && (
                    <SidebarMenu className="py-2 mb-2">
                        <SidebarMenuItem className="flex items-center gap-2">
                            <SidebarMenuButton
                                tooltip={quickAction.name}
                                className="min-w-8 bg-primary text-primary-foreground duration-200 ease-linear hover:bg-primary/90 hover:text-primary-foreground active:bg-primary/90 active:text-primary-foreground"
                                asChild
                            >
                                <Link href={quickAction.route}>
                                    <LucideIconByName name={quickAction.icon} />
                                    <span>{quickAction.name}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                )}
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.route}>
                            <SidebarMenuButton
                                tooltip={item.name}
                                asChild
                                isActive={isEntityListNavActive(
                                    currentPath,
                                    item.route,
                                )}
                            >
                                <Link href={item.route}>
                                    <LucideIconByName name={item.icon} />
                                    <span>{item.name}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
