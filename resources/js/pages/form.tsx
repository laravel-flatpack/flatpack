import { Head } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { FlatpackConfirmDialog } from '@/components/flatpack/flatpack-confirm-dialog';
import { FlatpackFormActions } from '@/components/flatpack-form/flatpack-form-actions';
import { FlatpackFormFields } from '@/components/flatpack-form/flatpack-form-fields';
import { FlatpackFormTopErrors } from '@/components/flatpack-form/flatpack-form-top-errors';
import { useFlatpackForm } from '@/hooks/use-flatpack-form';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackFormPageProps } from '@/types/pages/flatpack';

export default function FlatpackFormPage(props: FlatpackFormPageProps) {
    const { entity, name, record, mode } = props;
    const {
        form,
        fields,
        fieldComponents,
        fieldErrors,
        flatpackTopErrors,
        formActions,
        saveActionConfig,
        pendingConfirm,
        setPendingConfirm,
        setFieldValue,
        handleSubmit,
        runSubmit,
        executeNamedAction,
        noFieldsMessage,
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
                title={
                    pendingConfirm?.kind === 'named'
                        ? pendingConfirm.config.label
                        : (saveActionConfig?.label ?? 'Confirm')
                }
                onContinue={() => {
                    const pending = pendingConfirm;
                    setPendingConfirm(null);
                    if (pending?.kind === 'save') {
                        runSubmit();
                        return;
                    }
                    if (pending?.kind === 'named') {
                        void executeNamedAction(pending.config);
                    }
                }}
            />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-2">
                    <div className="mb-2 flex w-full flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                        <div className="min-w-0 flex-1">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {displayName}
                            </h1>
                            <p className="text-muted-foreground">
                                {mode === 'create'
                                    ? `Create a new ${displayName}.`
                                    : `Edit ${displayName} (${record ?? 'unknown'}).`}
                            </p>
                        </div>
                        <FlatpackFormActions
                            formActions={formActions}
                            formId={formId}
                            formProcessing={form.processing}
                            fieldsLength={fields.length}
                            record={record}
                            onSaveConfirmClick={() =>
                                setPendingConfirm({ kind: 'save' })
                            }
                            onNamedActionConfirm={(action) =>
                                setPendingConfirm({
                                    kind: 'named',
                                    config: action,
                                })
                            }
                            runNamedAction={executeNamedAction}
                        />
                    </div>
                </div>

                <form
                    id={formId}
                    className="flex flex-col gap-6"
                    noValidate
                    onSubmit={handleSubmit}
                >
                    <FlatpackFormTopErrors errors={flatpackTopErrors} />

                    {noFieldsMessage ? (
                        <p className="text-sm text-muted-foreground">
                            {noFieldsMessage}
                        </p>
                    ) : (
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
                    )}
                </form>
            </div>
        </>
    );
}

FlatpackFormPage.layout = (page: ReactElement<FlatpackFormPageProps>) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
