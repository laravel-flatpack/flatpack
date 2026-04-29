import { usePage } from '@inertiajs/react';
import { ListChecksIcon, TextCursorInputIcon } from 'lucide-react';
import { NavHeader } from '@/components/nav-header';
import { TooltipProvider } from '@/components/ui/tooltip';
import { route } from '@/lib/route';
import type { SchemaPageProps } from '@/types/schema';

/**
 * Providers-only shell for the components docs.
 */
export default function DocsLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { document } = usePage<SchemaPageProps>().props;

    return (
        <TooltipProvider delayDuration={0}>
            <NavHeader
                activeId={document.id}
                items={[
                    {
                        id: 'form',
                        label: 'Form schema',
                        href: route('flatpack.schema.form'),
                        icon: TextCursorInputIcon,
                    },
                    {
                        id: 'list',
                        label: 'List schema',
                        href: route('flatpack.schema.list'),
                        icon: ListChecksIcon,
                    },
                ]}
            />
            {children}
        </TooltipProvider>
    );
}
