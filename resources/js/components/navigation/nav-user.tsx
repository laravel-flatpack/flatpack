import { router } from '@inertiajs/react';
import {
    EllipsisVerticalIcon,
    KeyboardIcon,
    LogOutIcon,
    MoonIcon,
    SunIcon,
} from 'lucide-react';
import { useTheme } from 'next-themes';
import { useEffect, useState } from 'react';
import { KeyboardShortcutsDialog } from '@/components/shortcuts/keyboard-shortcuts-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useKeyboardShortcutsDialog } from '@/contexts/flatpack-keyboard-shortcuts-dialog';
import { route } from '@/lib/route';
import type { FlatpackUser } from '@/types/flatpack';

function userInitials(name: string): string {
    const trimmed = name.trim();
    if (!trimmed) {
        return '?';
    }

    const parts = trimmed.split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
        return `${parts[0]?.[0] ?? ''}${parts[parts.length - 1]?.[0] ?? ''}`.toUpperCase();
    }

    return trimmed.slice(0, 2).toUpperCase();
}

export function NavUser({ user }: { user: FlatpackUser }) {
    const { isMobile } = useSidebar();
    const { resolvedTheme, setTheme } = useTheme();
    const [mounted, setMounted] = useState(false);
    const { shortcutsDialogOpen, setShortcutsDialogOpen } =
        useKeyboardShortcutsDialog();

    useEffect(() => {
        setMounted(true);
    }, []);

    const themeLabel = !mounted
        ? 'Switch theme'
        : resolvedTheme === 'dark'
          ? 'Switch to light theme'
          : 'Switch to dark theme';

    const ThemeIcon = !mounted
        ? MoonIcon
        : resolvedTheme === 'dark'
          ? SunIcon
          : MoonIcon;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <Avatar className="h-8 w-8 rounded-lg">
                                <AvatarImage
                                    src={user.avatar}
                                    alt={user.name}
                                />
                                <AvatarFallback className="rounded-lg">
                                    {userInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">
                                    {user.name}
                                </span>
                                <span className="truncate text-xs text-muted-foreground">
                                    {user.email}
                                </span>
                            </div>
                            <EllipsisVerticalIcon className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        side={isMobile ? 'bottom' : 'right'}
                        align="end"
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="p-0 font-normal">
                            <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <Avatar className="h-8 w-8 rounded-lg">
                                    <AvatarImage
                                        src={user.avatar}
                                        alt={user.name}
                                    />
                                    <AvatarFallback className="rounded-lg">
                                        {userInitials(user.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-medium">
                                        {user.name}
                                    </span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        {user.email}
                                    </span>
                                </div>
                            </div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuItem
                                onClick={() =>
                                    setTheme(
                                        resolvedTheme === 'dark'
                                            ? 'light'
                                            : 'dark',
                                    )
                                }
                            >
                                <ThemeIcon />
                                {themeLabel}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                onClick={() => setShortcutsDialogOpen(true)}
                            >
                                <KeyboardIcon />
                                Keyboard shortcuts
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            onClick={() =>
                                router.post(route('flatpack.logout'))
                            }
                        >
                            <LogOutIcon />
                            Logout
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
                <KeyboardShortcutsDialog
                    open={shortcutsDialogOpen}
                    onOpenChange={setShortcutsDialogOpen}
                />
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
