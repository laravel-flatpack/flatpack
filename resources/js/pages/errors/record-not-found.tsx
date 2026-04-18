import { Head, Link } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import type { ReactElement } from 'react';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import FlatpackLayout from '@/layouts/flatpack-layout';

export type FlatpackRecordNotFoundPageProps = {
    entity: string;
    entityName: string;
};

export default function FlatpackRecordNotFoundPage({
    entity,
    entityName,
}: FlatpackRecordNotFoundPageProps) {
    const title = `Record not found`;
    return (
        <>
            <Head title={title} />
            <div className="flex flex-col items-center justify-center py-6 px-2 md:p-10">
                <div className="w-full max-w-md text-center border rounded-lg p-10">
                    <TriangleAlertIcon className="size-10 text-muted-foreground mx-auto" />
                    <h4 className="text-2xl font-semibold">{title}</h4>
                    <p className="mb-5 text-muted-foreground text-sm">
                        The {entityName} you are looking for does not exist.
                    </p>
                    <p className="my-10 text-sm">
                        We could not find that {entityName}. It may have been
                        removed, or you might not have permission to view it.
                    </p>
                    <Button
                        variant="default"
                        size="lg"
                        className="w-full"
                        asChild
                    >
                        <Link
                            href={route('flatpack.entities.index', { entity })}
                        >
                            Back to {entityName} list
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
FlatpackRecordNotFoundPage.layout = (
    page: ReactElement<FlatpackRecordNotFoundPageProps>,
) => <FlatpackLayout>{page}</FlatpackLayout>;
