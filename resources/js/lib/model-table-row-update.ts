import { router } from '@inertiajs/react';
import { firstErrorMessage } from '@/lib/form-errors';
import { route } from '@/lib/route';

async function patchWithInertia(
    url: string,
    values: Record<string, unknown>,
): Promise<Record<string, unknown> | null> {
    return await new Promise<Record<string, unknown> | null>(
        (resolve, reject) => {
            router.patch(url, { values: values as never } as never, {
                preserveState: true,
                preserveScroll: true,
                only: ['composition_debug'],
                onSuccess: () => resolve(null),
                onError: (errors) => {
                    reject(
                        new Error(
                            firstErrorMessage(errors) ?? 'Row update failed',
                        ),
                    );
                },
            });
        },
    );
}

export async function updateDashboardWidgetModelRow(params: {
    widgetId: string;
    rowId: string;
    values: Record<string, unknown>;
}) {
    return await patchWithInertia(
        route('flatpack.dashboard.widgets.update-row', {
            widget: params.widgetId,
            record: params.rowId,
        }),
        params.values,
    );
}

export async function bulkDashboardWidgetModelRows(params: {
    widgetId: string;
    action: string;
    selection: 'all' | string[];
    search: string;
    filters: Record<string, string | string[] | null>;
    sorting: {
        sort_by: string | null;
        sort_direction: 'asc' | 'desc' | null;
    };
}): Promise<void> {
    await new Promise<void>((resolve, reject) => {
        router.post(
            route('flatpack.dashboard.widgets.bulk-action', {
                widget: params.widgetId,
            }),
            {
                action: params.action,
                selection: params.selection,
                search: params.search,
                filters: params.filters,
                sort_by: params.sorting.sort_by,
                sort_direction: params.sorting.sort_direction,
            } as never,
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) =>
                    reject(
                        new Error(
                            firstErrorMessage(errors) ?? 'Bulk action failed',
                        ),
                    ),
            },
        );
    });
}

export async function updateFormTableModelRow(params: {
    entity: string;
    fieldId: string;
    rowId: string;
    values: Record<string, unknown>;
}) {
    return await patchWithInertia(
        route('flatpack.entities.table-fields.update-row', {
            entity: params.entity,
            field: params.fieldId,
            record: params.rowId,
        }),
        params.values,
    );
}
