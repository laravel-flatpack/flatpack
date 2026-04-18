'use client';

import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { isEntityListNavActive } from '@/lib/utils';
import type { FlatpackMenuItem } from '@/types/flatpack';
import { LucideIconByName } from './icons';

export function NavSecondary({
    label,
    items,
    currentPath,
}: {
    label?: string;
    items?: FlatpackMenuItem[];
    currentPath: string;
}) {
    if (items && items.length === 0) {
        return null;
    }

    return (
        <SidebarGroup className="group-data-[collapsible=icon]:hidden">
            {label && <SidebarGroupLabel>{label}</SidebarGroupLabel>}
            {items && (
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.url}>
                            <SidebarMenuButton
                                tooltip={item.name}
                                asChild
                                isActive={isEntityListNavActive(
                                    currentPath,
                                    item.url,
                                )}
                            >
                                <Link href={item.url}>
                                    <LucideIconByName name={item.icon} />
                                    <span>{item.name}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            )}
        </SidebarGroup>
    );
}
