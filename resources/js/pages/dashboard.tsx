import { Head } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import FlatpackLayout from '@/layouts/flatpack-layout';

export default function FlatpackDashboard() {
    return (
        <>
            <Head title="Flatpack" />
            <div className="flex flex-col gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="text-muted-foreground">
                        Flatpack admin area is ready. This is a placeholder.
                    </p>
                </div>
                <div className="relative min-h-[40vh] overflow-hidden rounded-xl border border-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </>
    );
}

FlatpackDashboard.layout = (page: React.ReactNode) => (
    <FlatpackLayout title="Flatpack admin">{page}</FlatpackLayout>
);
