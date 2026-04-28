import { Link, usePage } from '@inertiajs/react';
import { ListChecksIcon, TextCursorInputIcon } from 'lucide-react';
import { TooltipProvider } from '@/components/ui/tooltip';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';
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
            <header className="sticky top-0 z-50 w-full bg-background">
                <nav className="mt-2 container mx-auto items-center gap-0 hidden lg:flex">
                    <Link
                        href={route('flatpack.schema.form')}
                        className={cn(
                            'inline-flex items-center',
                            'text-sm font-medium text-muted-foreground hover:text-foreground',
                            'border-b-2 border-transparent',
                            'px-2 py-2',
                            document.id === 'form'
                                ? 'border-b-primary text-foreground'
                                : 'text-muted-foreground',
                        )}
                    >
                        <TextCursorInputIcon className="size-4 mr-2" />
                        Form schema
                    </Link>
                    <Link
                        href={route('flatpack.schema.list')}
                        className={cn(
                            'inline-flex items-center',
                            'text-sm font-medium text-muted-foreground hover:text-foreground',
                            'border-b-2 border-transparent',
                            'px-2 py-2',
                            document.id === 'list'
                                ? 'border-b-primary text-foreground'
                                : 'text-muted-foreground',
                        )}
                    >
                        <ListChecksIcon className="size-4 mr-2" />
                        List schema
                    </Link>
                </nav>
            </header>
            {children}
        </TooltipProvider>
    );
}
