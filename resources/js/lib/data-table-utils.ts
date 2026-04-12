import type { FlatpackDataTableColumn } from '@/types/data-table';

export function stableRowId(
    row: Record<string, unknown>,
    index: number,
): string {
    const idVal = row.id;
    if (idVal !== undefined && idVal !== null) {
        return String(idVal);
    }
    return `row-${index}`;
}

export function reindexReorderColumn(
    rows: Record<string, unknown>[],
    reorderKey: string,
): Record<string, unknown>[] {
    return rows.map((row, i) => ({
        ...row,
        [reorderKey]: i + 1,
    }));
}

export function interpolateRowPlaceholders(
    template: string,
    row: Record<string, unknown>,
): string {
    return template.replace(/\{([^}]+)\}/g, (_, rawKey: string) => {
        const key = rawKey.trim();
        const v = row[key];
        if (v == null) {
            return '';
        }
        return String(v);
    });
}

export function formatCellValue(raw: unknown): string {
    if (raw == null) {
        return '';
    }
    if (typeof raw === 'object') {
        return JSON.stringify(raw);
    }
    return String(raw);
}

export function dateInputSegment(raw: unknown): string {
    const s = formatCellValue(raw);
    const m = s.match(/^(\d{4}-\d{2}-\d{2})/);
    return m ? m[1] : '';
}

export function mergeCommittedDate(isoDay: string, previous: unknown): string {
    const prev = formatCellValue(previous);
    if (isoDay === '') {
        return '';
    }
    if (prev.length > 10 && (prev[10] === ' ' || prev[10] === 'T')) {
        return isoDay + prev.slice(10);
    }
    return isoDay;
}

export function cellControlDomId(rowId: string, columnId: string): string {
    return `dt-${String(rowId).replace(/[^a-zA-Z0-9_-]/g, '-')}-${columnId}`;
}

export function columnEditableInDrawer(col: FlatpackDataTableColumn): boolean {
    if (col.type === 'actions') {
        return false;
    }
    if (col.editable === true) {
        return true;
    }
    if (col.type === 'select' && col.options?.length) {
        return true;
    }
    if (col.type === 'badge' && col.options?.length) {
        return true;
    }
    if (col.type === 'date') {
        return true;
    }
    return false;
}
