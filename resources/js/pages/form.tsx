import { Head, usePage } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { useCallback, useState } from 'react';
import { ConfirmDialog } from '@/components/actions/confirm-dialog';
import { FormActions } from '@/components/shell/form/form-actions';
import { FormFields } from '@/components/shell/form/form-fields';
import { FormTopErrors } from '@/components/shell/form/form-top-errors';
import { PageHeader } from '@/components/shell/page-header';
import { TopToolbar } from '@/components/shell/top-toolbar';
import { WidgetsRenderer } from '@/components/widgets/widgets-renderer';
import { FlatpackFormInlineActionsProvider } from '@/contexts/flatpack-form-inline-actions';
import { useFlatpackForm } from '@/hooks/use-flatpack-form';
import { useInertiaLeaveGuard } from '@/hooks/use-inertia-leave-guard';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackPageProps } from '@/types/flatpack';
import type {
    FlatpackFormPageProps,
    FlatpackListHeaderAction,
} from '@/types/pages/flatpack';

const NoFieldsMessage = ({ entity }: { entity: string }) => (
    <p className="text-sm text-muted-foreground">
        Define fields, tabs, or a{' '}
        <code className="font-mono text-xs bg-muted px-1 py-0.5 rounded-md">
            sidebar
        </code>{' '}
        in{' '}
        <code className="font-mono text-xs bg-muted px-1 py-0.5 rounded-md">{`/${entity}/form.yaml`}</code>{' '}
        to render this form.
    </p>
);

export default function FlatpackFormPage(props: FlatpackFormPageProps) {
    const {
        props: { flatpack },
    } = usePage<FlatpackPageProps>();
    const { entity, name, record, mode } = props;
    const {
        form,
        isDirty,
        fields,
        mainFields,
        sidebarFields,
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
        sidebarWidgets,
        sidebarWidgetsSchema,
    } = useFlatpackForm(props);

    const { leaveGuardOpen, confirmLeave, cancelLeave } =
        useInertiaLeaveGuard(isDirty);

    const displayName = name ?? entity;
    const pageTitle =
        mode === 'create' ? `Create ${displayName}` : `Edit ${displayName}`;
    const formId = `flatpack-form-${entity}-${record ?? 'new'}`;
    const [tabsPortalContainer, setTabsPortalContainer] =
        useState<HTMLDivElement | null>(null);

    const hasSidebarWidgets =
        sidebarWidgets != null &&
        typeof sidebarWidgets === 'object' &&
        Object.keys(sidebarWidgets).length > 0;
    const hasSidebarColumn = sidebarFields.length > 0 || hasSidebarWidgets;
    const hasMainColumn = mainFields.length > 0 || tabPanels.length > 0;

    const formValues = form.data.values as Record<string, unknown>;

    const [pendingDirtyAction, setPendingDirtyAction] = useState<
        (FlatpackListHeaderAction & { action: string }) | null
    >(null);

    const requestRunAction = useCallback(
        (action: FlatpackListHeaderAction & { action: string }) => {
            if (isDirty && action.submit !== true) {
                setPendingDirtyAction(action);
                return;
            }
            runAction(action);
        },
        [isDirty, runAction],
    );

    const requestConfirmForAction = useCallback(
        (action: FlatpackListHeaderAction & { action: string }) => {
            if (isDirty && action.submit !== true) {
                setPendingDirtyAction(action);
                return;
            }
            setPendingConfirm({ config: action });
        },
        [isDirty, setPendingConfirm],
    );

    return (
        <>
            <Head title={pageTitle} />
            <ConfirmDialog
                open={pendingDirtyAction !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingDirtyAction(null);
                    }
                }}
                title="Discard unsaved changes?"
                description="You have unsaved changes. Continuing will run this action and your edits will not be saved."
                continueLabel="Continue anyway"
                continueVariant="default"
                onContinue={() => {
                    const action = pendingDirtyAction;
                    setPendingDirtyAction(null);
                    if (action === null) {
                        return;
                    }
                    if (action.confirm === true) {
                        setPendingConfirm({ config: action });
                    } else {
                        runAction(action);
                    }
                }}
            />
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
            <PageHeader
                withPaddingShell
                stickyTitle={<TopToolbar breadcrumbs={flatpack.breadcrumbs} />}
                title={pageTitle}
                actions={
                    <FormActions
                        formActions={formActions}
                        formId={formId}
                        formProcessing={formProcessing}
                        formIsDirty={isDirty}
                        formMode={mode}
                        fieldsLength={fields.length}
                        formValues={formValues}
                        onFormSubmitIntent={prepareFormSubmit}
                        onFormSubmitConfirmClick={requestConfirmForAction}
                        onRunAction={requestRunAction}
                    />
                }
            />
            <div className="flex flex-col gap-4 pb-4 md:gap-6 md:pb-6 px-4 lg:px-6">
                <div className="flex flex-col gap-2">
                    <form
                        id={formId}
                        className="flex flex-col gap-6"
                        noValidate
                        onSubmit={handleSubmit}
                    >
                        <FlatpackFormInlineActionsProvider
                            value={{
                                formId,
                                prepareFormSubmit,
                                requestConfirmForAction,
                                requestRunAction,
                                formProcessing,
                                formIsDirty: isDirty,
                                mode,
                                fieldsLength: fields.length,
                                formValues,
                            }}
                        >
                            <FormTopErrors errors={flatpackTopErrors} />

                            {hasMainColumn || hasSidebarColumn ? (
                                <>
                                    <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
                                        {hasMainColumn ? (
                                            <div className="min-w-0 flex-1 space-y-6">
                                                <FormFields
                                                    entity={entity}
                                                    mode={mode}
                                                    record={record}
                                                    tabPanels={tabPanels}
                                                    tabsFullWidthBelow={
                                                        tabPanels.length > 0
                                                    }
                                                    tabsPortalContainer={
                                                        tabsPortalContainer
                                                    }
                                                    fields={mainFields}
                                                    fieldComponents={
                                                        fieldComponents
                                                    }
                                                    fieldErrors={fieldErrors}
                                                    formValues={
                                                        form.data
                                                            .values as Record<
                                                            string,
                                                            unknown
                                                        >
                                                    }
                                                    setFieldValue={
                                                        setFieldValue
                                                    }
                                                />
                                            </div>
                                        ) : null}
                                        {hasSidebarColumn ? (
                                            <aside className="w-full shrink-0 space-y-4 border-border lg:w-72 lg:pl-6 xl:w-80">
                                                {sidebarFields.length > 0 ? (
                                                    <FormFields
                                                        entity={entity}
                                                        mode={mode}
                                                        record={record}
                                                        fields={sidebarFields}
                                                        fieldComponents={
                                                            fieldComponents
                                                        }
                                                        fieldErrors={
                                                            fieldErrors
                                                        }
                                                        formValues={
                                                            form.data
                                                                .values as Record<
                                                                string,
                                                                unknown
                                                            >
                                                        }
                                                        setFieldValue={
                                                            setFieldValue
                                                        }
                                                    />
                                                ) : null}
                                                {hasSidebarWidgets ? (
                                                    <WidgetsRenderer
                                                        widgets={sidebarWidgets}
                                                        tabPanels={
                                                            sidebarWidgetsSchema?.tab_panels
                                                        }
                                                        className="md:grid-cols-1 xl:grid-cols-1"
                                                    />
                                                ) : null}
                                            </aside>
                                        ) : null}
                                    </div>
                                    {tabPanels.length > 0 ? (
                                        <div
                                            ref={setTabsPortalContainer}
                                            className="w-full min-w-0"
                                        />
                                    ) : null}
                                </>
                            ) : (
                                <NoFieldsMessage entity={entity} />
                            )}
                        </FlatpackFormInlineActionsProvider>
                    </form>
                </div>
            </div>
        </>
    );
}

FlatpackFormPage.layout = (page: ReactElement<FlatpackFormPageProps>) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
