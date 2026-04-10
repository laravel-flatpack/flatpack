import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';

export function SiteHeader({ title }: { title?: string }) {
    return (
        <header className="flex h-(--header-height) shrink-0 items-center gap-2 border-b transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-(--header-height)">
            <div className="flex w-full items-center gap-1 px-4 lg:gap-2 lg:px-6">
                <SidebarTrigger className="-ml-1" />
                {title && (
                    <>
                        <Separator
                            orientation="vertical"
                            className="ml-2 mr-3 my-1 data-[orientation=vertical]:h-6"
                        />
                        <h1 className="text-sm font-medium">{title}</h1>
                    </>
                )}
            </div>
            {/*<div className="flex px-4 lg:px-6">
                SearchBar
            </div>*/}
        </header>
    );
}
