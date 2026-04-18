'use client';

import { Link } from '@inertiajs/react';
import type * as React from 'react';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { FlatpackMenuItem } from '@/types/flatpack';
import { LucideIconByName } from './icons';

export function NavSettings({
    label,
    items,
    currentPath,
    ...props
}: {
    label?: string;
    items?: FlatpackMenuItem[];
    currentPath: string;
} & React.ComponentPropsWithoutRef<typeof SidebarGroup>) {
    if (items && items.length === 0) {
        return null;
    }

    return (
        <SidebarGroup {...props}>
            <SidebarGroupContent>
                {label && <SidebarGroupLabel>{label}</SidebarGroupLabel>}
                {items && (
                    <SidebarMenu>
                        {items.map((item) => (
                            <SidebarMenuItem key={item.url}>
                                <SidebarMenuButton asChild>
                                    <Link href={item.url}>
                                        <LucideIconByName name={item.icon} />
                                        <span>{item.name}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                )}
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
