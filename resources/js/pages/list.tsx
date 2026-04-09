import { Head } from '@inertiajs/react';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackListPageProps } from '@/types/pages/flatpack';

export default function FlatpackListPage({ entity }: FlatpackListPageProps) {
    return (
        <>
            <Head title={`${entity} list`} />
            <div className="flex flex-col gap-2">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {entity}
                </h1>
                <p className="text-muted-foreground">
                    List view placeholder for the {entity} entity.
                </p>
            </div>
        </>
    );
}

FlatpackListPage.layout = (page: React.ReactNode) => (
    <FlatpackLayout title="Flatpack admin">{page}</FlatpackLayout>
);
