import { Head } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { ConfirmDialog } from '@/components/actions/confirm-dialog';
import { FormActions } from '@/components/shell/form/form-actions';
import { FormFields } from '@/components/shell/form/form-fields';
import { FormTopErrors } from '@/components/shell/form/form-top-errors';
import { PageHeader } from '@/components/shell/page-header';
import { useCompositionDebugLog } from '@/hooks/use-composition-debug-log';
import { useFlatpackForm } from '@/hooks/use-flatpack-form';
import { useInertiaLeaveGuard } from '@/hooks/use-inertia-leave-guard';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackFormPageProps } from '@/types/pages/flatpack';

const NoFieldsMessage = ({ entity }: { entity: string }) => (
    <p className="text-sm text-muted-foreground">
        Define fields or tabs in{' '}
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
        tabPanels,
        fieldComponents,
        fieldErrors,
        flatpackTopErrors,
        formActions,
        prepareFormSubmit,
        pendingConfirm,
        setPendingConfirm,
        setFieldValue,
        handleSubmit,
        runAction,
        formProcessing,
    } = useFlatpackForm(props);

    const { leaveGuardOpen, confirmLeave, cancelLeave } =
        useInertiaLeaveGuard(isDirty);

    const displayName = name ?? entity;
    const pageTitle =
        mode === 'create' ? `Create ${displayName}` : `Edit ${displayName}`;
    const formId = `flatpack-form-${entity}-${record ?? 'new'}`;

    return (
        <>
            <Head title={pageTitle} />
            <ConfirmDialog
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
                    runAction(pending.config);
                }}
            />
            <ConfirmDialog
                open={leaveGuardOpen}
                onOpenChange={(open) => {
                    if (!open) {
                        cancelLeave();
                    }
                }}
                title="Discard unsaved changes?"
                description="You have unsaved changes. If you leave this page now, they will not be saved."
                continueLabel="Leave page"
                continueVariant="default"
                onContinue={confirmLeave}
            />
            <div className="flex flex-col gap-2">
                <PageHeader
                    title={pageTitle}
                    actions={
                        <FormActions
                            formActions={formActions}
                            formId={formId}
                            formProcessing={formProcessing}
                            formIsDirty={isDirty}
                            formMode={mode}
                            fieldsLength={fields.length}
                            onFormSubmitIntent={prepareFormSubmit}
                            onFormSubmitConfirmClick={(action) =>
                                setPendingConfirm({ config: action })
                            }
                            onRunAction={runAction}
                        />
                    }
                />

                <form
                    id={formId}
                    className="flex flex-col gap-6"
                    noValidate
                    onSubmit={handleSubmit}
                >
                    <FormTopErrors errors={flatpackTopErrors} />

                    {fields.length > 0 ? (
                        // Root-only fields (YAML `fields` not listed in any tab panel) render above tabs
                        // when both `fields` and `tabs` exist; see FormFields unassignedEntries.
                        <FormFields
                            entity={entity}
                            mode={mode}
                            record={record}
                            tabPanels={tabPanels}
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
