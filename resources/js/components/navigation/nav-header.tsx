import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export type NavHeaderItem = {
    id: string;
    label: string;
    href: string;
    icon?: LucideIcon;
};

type NavHeaderProps = {
    items: NavHeaderItem[];
    activeId: string;
};

export function NavHeader({ items, activeId }: NavHeaderProps) {
    return (
        <header className="w-full">
            <nav className="mt-2 mx-auto flex flex-wrap items-center gap-0">
                {items.map((item) => {
                    const Icon = item.icon;
                    const isActive = item.id === activeId;

                    return (
                        <Link
                            key={item.id}
                            href={item.href}
                            className={cn(
                                'inline-flex items-center',
                                'text-sm font-medium text-muted-foreground hover:text-foreground',
                                'border-b-2 border-transparent',
                                'px-2 py-2',
                                isActive
                                    ? 'border-b-primary text-foreground'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {Icon ? <Icon className="mr-2 size-4" /> : null}
                            {item.label}
                        </Link>
                    );
                })}
            </nav>
        </header>
    );
}
