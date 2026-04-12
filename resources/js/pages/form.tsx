import { Head } from '@inertiajs/react';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackFormPageProps } from '@/types/pages/flatpack';

/**
 * When `FormController` supplies a field schema (`FormFieldProps[]`), render each field with
 * `loadField` from `@/lib/form` and `mapFormFieldPropsToComponentProps` from `@/lib/form-field-props`
 * (same approach as the components demo). For `type: 'table'`, row data and `columns` schema map to
 * `@/components/table/data-table` via `TableField`.
 */
export default function FlatpackFormPage({
    entity,
    record,
    mode,
}: FlatpackFormPageProps) {
    return (
        <>
            <Head title={`${entity} form`} />
            <div className="flex flex-col gap-2">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {entity}
                </h1>
                <p className="text-muted-foreground">
                    {mode === 'create'
                        ? `Create form placeholder for ${entity}.`
                        : `Edit form placeholder for ${entity} (${record ?? 'unknown'}).`}
                </p>
            </div>
        </>
    );
}

FlatpackFormPage.layout = (page: React.ReactElement<FlatpackFormPageProps>) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
