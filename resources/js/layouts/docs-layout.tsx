import { ThemeProvider } from 'next-themes';
import type { ReactNode } from 'react';
import { TooltipProvider } from '@/components/ui/tooltip';

/**
 * Docs shell: theme + tooltip providers and optional top navigation from the page.
 */
export default function DocsLayout({
    children,
    navigation = null,
}: {
    children: ReactNode;
    navigation?: ReactNode | null;
}) {
    return (
        <ThemeProvider attribute="class" defaultTheme="system" enableSystem>
            <TooltipProvider delayDuration={0}>
                {navigation}
                {children}
            </TooltipProvider>
        </ThemeProvider>
    );
}
