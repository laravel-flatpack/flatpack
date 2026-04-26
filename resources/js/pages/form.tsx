import { Head } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { FlatpackConfirmDialog } from '@/components/flatpack/flatpack-confirm-dialog';
import { FlatpackPageHeader } from '@/components/flatpack/flatpack-page-header';
import { FlatpackFormActions } from '@/components/flatpack-form/flatpack-form-actions';
import { FlatpackFormFields } from '@/components/flatpack-form/flatpack-form-fields';
import { FlatpackFormTopErrors } from '@/components/flatpack-form/flatpack-form-top-errors';
import { useCompositionDebugLog } from '@/hooks/use-composition-debug-log';
import { useFlatpackForm } from '@/hooks/use-flatpack-form';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackFormPageProps } from '@/types/pages/flatpack';

const NoFieldsMessage = ({ entity }: { entity: string }) => (
    <p className="text-sm text-muted-foreground">
        Define fields in{' '}
        <code className="font-mono text-xs bg-muted px-1 py-0.5 rounded-md">{`/${entity}/form.yaml`}</code>{' '}
        to render this form.
    </p>
);

export default function FlatpackFormPage(props: FlatpackFormPageProps) {
    useCompositionDebugLog(props.composition_debug);
    const { entity, name, record, mode } = props;
    const {
        form,
        isDirty,
        fields,
        fieldComponents,
        fieldErrors,
        flatpackTopErrors,
        formActions,
        prepareFormSubmit,
        pendingConfirm,
        setPendingConfirm,
        setFieldValue,
        handleSubmit,
        runSubmit,
    } = useFlatpackForm(props);

    const displayName = name ?? entity;
    const pageTitle =
        mode === 'create' ? `Create ${displayName}` : `Edit ${displayName}`;
    const formId = `flatpack-form-${entity}-${record ?? 'new'}`;

    return (
        <>
            <Head title={pageTitle} />
            <FlatpackConfirmDialog
                open={pendingConfirm !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingConfirm(null);
                    }
                }}
                title={pendingConfirm?.config.label ?? 'Confirm'}
                continueVariant={
                    pendingConfirm?.config.variant === 'destructive'
                        ? 'destructive'
                        : 'default'
                }
                onContinue={() => {
                    const pending = pendingConfirm;
                    setPendingConfirm(null);
                    if (pending === null) {
                        return;
                    }
                    prepareFormSubmit(pending.config);
                    runSubmit();
                }}
            />
            <div className="flex flex-col gap-2">
                <FlatpackPageHeader
                    title={pageTitle}
                    actions={
                        <FlatpackFormActions
                            formActions={formActions}
                            formId={formId}
                            formProcessing={form.processing}
                            formIsDirty={isDirty}
                            formMode={mode}
                            fieldsLength={fields.length}
                            onFormSubmitIntent={prepareFormSubmit}
                            onFormSubmitConfirmClick={(action) =>
                                setPendingConfirm({ config: action })
                            }
                        />
                    }
                />

                <form
                    id={formId}
                    className="flex flex-col gap-6"
                    noValidate
                    onSubmit={handleSubmit}
                >
                    <FlatpackFormTopErrors errors={flatpackTopErrors} />

                    {fields.length > 0 ? (
                        <FlatpackFormFields
                            entity={entity}
                            mode={mode}
                            record={record}
                            fields={fields}
                            fieldComponents={fieldComponents}
                            fieldErrors={fieldErrors}
                            formValues={
                                form.data.values as Record<string, unknown>
                            }
                            setFieldValue={setFieldValue}
                        />
                    ) : (
                        <NoFieldsMessage entity={entity} />
                    )}
                </form>
            </div>
        </>
    );
}

FlatpackFormPage.layout = (page: ReactElement<FlatpackFormPageProps>) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
