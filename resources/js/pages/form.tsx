import { Head } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { FlatpackConfirmDialog } from '@/components/flatpack/flatpack-confirm-dialog';
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
        prepareSaveSubmit,
        pendingConfirm,
        setPendingConfirm,
        setFieldValue,
        handleSubmit,
        runSubmit,
        executeNamedAction,
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
                    if (pending.config.action === 'save') {
                        runSubmit();
                        return;
                    }
                    void executeNamedAction(pending.config);
                }}
            />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-2">
                    <div className="mb-2 flex w-full flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                        <div className="min-w-0 flex-1">
                            <h1 className="text-2xl font-semibold tracking-tight capitalize">
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
                            formIsDirty={isDirty}
                            fieldsLength={fields.length}
                            record={record}
                            onSaveIntent={prepareSaveSubmit}
                            onSaveConfirmClick={(action) =>
                                setPendingConfirm({ config: action })
                            }
                            onNamedActionConfirm={(action) =>
                                setPendingConfirm({ config: action })
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
