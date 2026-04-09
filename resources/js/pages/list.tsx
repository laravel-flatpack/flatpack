import { Head } from '@inertiajs/react';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackListPageProps } from '@/types/pages/flatpack';

export default function FlatpackListPage({ entity }: FlatpackListPageProps) {
    const pageTitle = entity ? `${entity} list` : '';

    return (
        <>
            {entity ? <Head title={pageTitle} /> : null}
            <div className="flex flex-col gap-2">
                {entity ? (
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {entity}
                    </h1>
                ) : null}
                <p className="text-muted-foreground">
                    {entity
                        ? `List view placeholder for the ${entity} entity.`
                        : 'List view placeholder.'}
                </p>
            </div>
        </>
    );
}

FlatpackListPage.layout = (page: React.ReactElement<FlatpackListPageProps>) => {
    return <FlatpackLayout>{page}</FlatpackLayout>;
};
