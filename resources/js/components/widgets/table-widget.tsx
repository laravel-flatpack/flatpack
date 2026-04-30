'use client';

import { toast } from 'sonner';
import { DataTable } from '@/components/table/data-table';
import { listYamlColumnsToDataTableColumns } from '@/lib/list-schema';
import {
    bulkDashboardWidgetModelRows,
    updateDashboardWidgetModelRow,
} from '@/lib/model-table-row-update';
import type { FlatpackTableWidget } from '@/types/widgets-composition';

type TableWidgetProps = {
    widgetId: string;
    widget: FlatpackTableWidget;
};

export function TableWidget({ widgetId, widget }: TableWidgetProps) {
    const providerBacked =
        typeof widget.provider === 'string' && widget.provider.trim() !== '';
    const modelBacked =
        typeof widget.model === 'string' && widget.model.trim() !== '';
    const columns = listYamlColumnsToDataTableColumns(widget.columns).map(
        (column) =>
            providerBacked
                ? {
                      ...column,
                      editable: false,
                  }
                : column,
    );
    const rows = Array.isArray(widget.data?.rows) ? widget.data.rows : [];
    const bulkActions = Array.isArray(widget.bulk_actions)
        ? widget.bulk_actions
        : [];
    const resolvedDefaultSort =
        typeof widget.data?.sorting?.sort_by === 'string' &&
        widget.data.sorting.sort_by.trim() !== '' &&
        (widget.data.sorting.sort_direction === 'asc' ||
            widget.data.sorting.sort_direction === 'desc')
            ? {
                  key: widget.data.sorting.sort_by,
                  direction: widget.data.sorting.sort_direction,
              }
            : widget.default_sort;
    const pagination =
        typeof widget.pagination === 'boolean' ? widget.pagination : undefined;
    return (
        <DataTable
            id={`widget-table-${widgetId.toLowerCase().replaceAll(/\s+/g, '-')}`}
            columns={columns}
            data={rows}
            bulkActions={modelBacked ? bulkActions : []}
            pagination={pagination}
            defaultSort={resolvedDefaultSort}
            requireRowIdForActions={providerBacked}
            rowDetailDrawer={modelBacked}
            openDetailDrawerOnRowClick={modelBacked}
            onRowUpdate={
                modelBacked
                    ? async ({ rowId, row }) => {
                          const recordId = String(rowId ?? '').trim();
                          if (recordId === '') {
                              throw new Error('Row id is required');
                          }
                          return await updateDashboardWidgetModelRow({
                              widgetId,
                              rowId: recordId,
                              values: row,
                          });
                      }
                    : undefined
            }
            onBulkAction={
                modelBacked && bulkActions.length > 0
                    ? async (payload) => {
                          await bulkDashboardWidgetModelRows({
                              widgetId,
                              action: payload.action,
                              selection: payload.selection,
                              search: payload.search,
                              filters: payload.filters,
                              sorting: payload.sorting,
                          });
                          const cfg = bulkActions.find(
                              (item) => item.action === payload.action,
                          );
                          if (cfg?.success_message) {
                              toast.success(cfg.success_message);
                          }
                      }
                    : undefined
            }
        />
    );
}
