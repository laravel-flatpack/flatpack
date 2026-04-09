import { Link } from '@inertiajs/react';
import { CirclePlusIcon } from 'lucide-react';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { isSamePath } from '@/lib/utils';
import type { FlatpackMenuItem } from '@/types/flatpack';
import { icons } from './icons';
import { Icon } from './ui/icon';

export function NavMain({
    items,
    currentPath,
}: {
    items: FlatpackMenuItem[];
    currentPath: string;
}) {
    return (
        <SidebarGroup>
            <SidebarGroupContent className="flex flex-col gap-2">
                <SidebarMenu className="py-2">
                    <SidebarMenuItem className="flex items-center gap-2">
                        <SidebarMenuButton
                            tooltip="Quick Create"
                            className="min-w-8 bg-primary text-primary-foreground duration-200 ease-linear hover:bg-primary/90 hover:text-primary-foreground active:bg-primary/90 active:text-primary-foreground"
                        >
                            <CirclePlusIcon />
                            <span>Quick Create</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.slug}>
                            <SidebarMenuButton
                                tooltip={item.name}
                                asChild
                                isActive={isSamePath(currentPath, item.route)}
                            >
                                <Link href={item.route}>
                                    <Icon iconNode={icons[item.icon]} />
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
