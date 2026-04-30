import { cellControlDomId } from '@/lib/data-table-utils';
import type {
    ListColumnRenderProps,
    ListColumnSharedContext,
} from '@/types/list-columns';

export function buildListColumnSharedContext({
    column,
    rowId,
    onCellChange,
    inlineCellEdit = true,
}: ListColumnRenderProps): ListColumnSharedContext {
    const editable =
        inlineCellEdit &&
        column.editable === true &&
        column.type !== 'actions' &&
        onCellChange != null &&
        column.detailDrawer !== true;

    return {
        editable,
        controlId: cellControlDomId(rowId, column.id),
        commit: (next: unknown) => onCellChange?.(rowId, column.id, next),
    };
}
