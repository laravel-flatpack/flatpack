import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { getCurrentPath, getRoutePathname } from '@/lib/utils';
import type { FlatpackPageProps } from '@/types/flatpack';

export default function FlatpackLayout({
    title = 'Flatpack',
    children,
}: {
    title?: string;
    children: React.ReactNode;
}) {
    const {
        props: { flatpack },
        url,
    } = usePage<FlatpackPageProps>();
    const menu = flatpack?.menu ?? [];
    const dashboardRoute = flatpack?.dashboardRoute ?? '/';
    const currentPath = getCurrentPath(url);

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="border-b border-border bg-card">
                <div className="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                        </div>
                        <span className="font-medium">{title}</span>
                    </div>
                    <Link
                        href={dashboardRoute}
                        className="text-sm text-muted-foreground hover:text-foreground"
                    >
                        Dashboard
                    </Link>
                </div>
            </header>
            {menu.length > 0 && (
                <nav className="border-b border-border bg-card/60">
                    <div className="mx-auto flex h-11 max-w-6xl items-center gap-4 overflow-x-auto px-4">
                        {menu.map((item) => (
                            <Link
                                key={item.slug}
                                href={item.route}
                                className={
                                    currentPath === getRoutePathname(item.route)
                                        ? 'text-sm text-foreground'
                                        : 'text-sm text-muted-foreground hover:text-foreground'
                                }
                            >
                                {item.name}
                            </Link>
                        ))}
                    </div>
                </nav>
            )}
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col p-4 md:p-6">
                {children}
            </main>
        </div>
    );
}
