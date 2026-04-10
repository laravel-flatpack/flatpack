import { TooltipProvider } from '@/components/ui/tooltip';

/**
 * Providers-only shell for the components demo (no sidebar, header, or other
 * Flatpack chrome). Add any shared providers here if demo fields need them.
 */
export default function DemoLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return <TooltipProvider delayDuration={0}>{children}</TooltipProvider>;
}
