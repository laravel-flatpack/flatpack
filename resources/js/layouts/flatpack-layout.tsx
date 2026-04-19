import { usePage } from '@inertiajs/react';
import { ThemeProvider } from 'next-themes';
import { AppSidebar } from '@/components/app-sidebar';
import { FlatpackKeyboardShortcutsGlobalHotkey } from '@/components/flatpack-keyboard-shortcuts-global-hotkey';
import { FlatpackShellShortcutsRegistration } from '@/components/flatpack-shell-shortcuts-registration';
import { SiteHeader } from '@/components/site-header';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { FlatpackKeyboardShortcutsDialogProvider } from '@/contexts/flatpack-keyboard-shortcuts-dialog';
import { FlatpackShortcutsProvider } from '@/contexts/flatpack-shortcuts-registry';
import { getCurrentPath } from '@/lib/utils';
import type { FlatpackPageProps } from '@/types/flatpack';

export default function FlatpackLayout({
    title,
    children,
}: {
    title?: string;
    children: React.ReactNode;
}) {
    const {
        props: { flatpack },
        url,
    } = usePage<FlatpackPageProps>();
    const { quickAction, menu, secondaryMenu, bottomMenu, user, breadcrumbs } =
        flatpack;
    const navigation = {
        quickAction,
        menu: Array.isArray(menu) ? menu : [],
        secondaryMenu,
        bottomMenu,
        user,
    };
    const currentPath = getCurrentPath(url);

    return (
        <FlatpackShortcutsProvider>
            <FlatpackKeyboardShortcutsDialogProvider>
                <SidebarProvider
                    style={
                        {
                            '--sidebar-width': 'calc(var(--spacing) * 64)',
                            '--header-height': 'calc(var(--spacing) * 12)',
                        } as React.CSSProperties
                    }
                >
                    <FlatpackShellShortcutsRegistration />
                    <FlatpackKeyboardShortcutsGlobalHotkey />
                    <ThemeProvider
                        attribute="class"
                        defaultTheme="system"
                        enableSystem
                    >
                        <TooltipProvider delayDuration={0}>
                            <AppSidebar
                                variant="inset"
                                navigation={navigation}
                                currentPath={currentPath}
                            />
                            <SidebarInset>
                                <SiteHeader
                                    title={title}
                                    breadcrumbs={breadcrumbs}
                                />
                                <div className="flex flex-1 flex-col">
                                    <div className="@container/main flex flex-1 flex-col gap-2">
                                        <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6 px-4 lg:px-6">
                                            {children}
                                        </div>
                                    </div>
                                </div>
                            </SidebarInset>
                            <Toaster richColors />
                        </TooltipProvider>
                    </ThemeProvider>
                </SidebarProvider>
            </FlatpackKeyboardShortcutsDialogProvider>
        </FlatpackShortcutsProvider>
    );
}
