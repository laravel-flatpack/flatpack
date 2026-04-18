import { Head, Link } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
                <div className="w-full max-w-md">
                    <Card size="sm" className="mx-auto w-full max-w-md">
                        <CardHeader>
                            <CardTitle>{title}</CardTitle>
                            <CardDescription>
                                The {entityName} you are looking for does not
                                exist.
                            </CardDescription>
                            <CardContent className="flex flex-col items-center gap-2 !px-0">
                                <p className="my-10">
                                    We could not find that {entityName}. It may
                                    have been removed, or you might not have
                                    permission to view it.
                                </p>
                            </CardContent>
                            <CardFooter className="flex-col gap-2 !px-0">
                                <Button
                                    variant="default"
                                    className="w-full"
                                    asChild
                                >
                                    <Link
                                        href={route('flatpack.entities.index', {
                                            entity,
                                        })}
                                    >
                                        Back to {entityName} list
                                    </Link>
                                </Button>
                            </CardFooter>
                        </CardHeader>
                    </Card>
                </div>
            </div>
        </>
    );
}
FlatpackRecordNotFoundPage.layout = (
    page: ReactElement<FlatpackRecordNotFoundPageProps>,
) => <FlatpackLayout>{page}</FlatpackLayout>;
