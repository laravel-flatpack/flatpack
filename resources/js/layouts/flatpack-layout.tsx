import { usePage } from '@inertiajs/react';
import { ThemeProvider } from 'next-themes';
import { AppSidebar } from '@/components/shell/app-sidebar';
import { SiteHeader } from '@/components/shell/site-header';
import { KeyboardShortcutsGlobalHotkey } from '@/components/shortcuts/keyboard-shortcuts-global-hotkey';
import { ShellShortcutsRegistration } from '@/components/shortcuts/shell-shortcuts-registration';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { KeyboardShortcutsDialogProvider } from '@/contexts/flatpack-keyboard-shortcuts-dialog';
import { FlatpackShortcutsProvider } from '@/contexts/flatpack-shortcuts-registry';
import { getCurrentPath } from '@/lib/utils';
import type { FlatpackPageProps } from '@/types/flatpack';

export default function FlatpackLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const {
        props: { flatpack },
        url,
    } = usePage<FlatpackPageProps>();
    const {
        logo,
        name: displayName,
        quickAction,
        menu,
        secondaryMenu,
        bottomMenu,
        user,
        breadcrumbs,
    } = flatpack;
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
            <KeyboardShortcutsDialogProvider>
                <SidebarProvider
                    style={
                        {
                            '--sidebar-width': 'calc(var(--spacing) * 64)',
                            '--header-height': 'calc(var(--spacing) * 12)',
                        } as React.CSSProperties
                    }
                >
                    <ShellShortcutsRegistration />
                    <KeyboardShortcutsGlobalHotkey />
                    <ThemeProvider
                        attribute="class"
                        defaultTheme="system"
                        enableSystem
                    >
                        <TooltipProvider delayDuration={0}>
                            <AppSidebar
                                variant="inset"
                                logo={logo}
                                displayName={displayName}
                                navigation={navigation}
                                currentPath={currentPath}
                            />
                            <SidebarInset>
                                <SiteHeader breadcrumbs={breadcrumbs} />
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
            </KeyboardShortcutsDialogProvider>
        </FlatpackShortcutsProvider>
    );
}
