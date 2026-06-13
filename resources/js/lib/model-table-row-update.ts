import {
    inertiaPatchMutation,
    inertiaPostMutation,
} from '@/lib/inertia-mutation';
import { route } from '@/lib/route';

async function patchWithInertia(
    url: string,
    values: Record<string, unknown>,
    options?: {
        successMessage?: string;
        errorMessage?: string;
    },
): Promise<Record<string, unknown> | null> {
    await inertiaPatchMutation(
        url,
        { values },
        {
            only: ['widgets', 'schema', 'composition_debug'],
            successMessage: options?.successMessage,
            errorMessage: options?.errorMessage ?? 'Row update failed',
        },
    );

    return null;
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
        {
            successMessage: 'Row updated successfully',
            errorMessage: 'Row update failed',
        },
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
    await inertiaPostMutation(
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
        },
        {
            errorMessage: 'Bulk action failed',
        },
    );
}

export async function runDashboardWidgetModelRowAction(params: {
    widgetId: string;
    rowId: string;
    action: string;
    successMessage?: string;
}): Promise<void> {
    await inertiaPostMutation(
        route('flatpack.dashboard.widgets.row-action', {
            widget: params.widgetId,
            record: params.rowId,
        }),
        {
            action: params.action,
        },
        {
            errorMessage: 'Row action failed',
            successMessage: params.successMessage,
        },
    );
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
