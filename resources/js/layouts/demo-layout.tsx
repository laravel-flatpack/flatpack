import { TooltipProvider } from '@/components/ui/tooltip';

/**
 * Providers-only shell for the components demo page.
 * No sidebar, header, or other Flatpack chrome.
 */
export default function DemoLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return <TooltipProvider delayDuration={0}>{children}</TooltipProvider>;
}
