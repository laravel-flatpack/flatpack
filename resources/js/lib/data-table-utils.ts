import type {
    FlatpackDataTableColumn,
    FlatpackDataTableColumnImageOptions,
    FlatpackDataTableColumnOption,
} from '@/types/data-table';

const COLUMN_TRUNCATE_MAX = 1_000_000;

/**
 * Parses list schema `columnImageOptions` from a column's `options` field (`type: image` only).
 */
export function parseImageColumnOptions(
    raw: unknown,
): FlatpackDataTableColumnImageOptions {
    if (raw == null || typeof raw !== 'object' || Array.isArray(raw)) {
        return {};
    }
    const rec = raw as Record<string, unknown>;
    const out: FlatpackDataTableColumnImageOptions = {};
    const w = rec.width;
    if (typeof w === 'number' && Number.isFinite(w) && w >= 1) {
        out.width = Math.floor(w);
    }
    const h = rec.height;
    if (typeof h === 'number' && Number.isFinite(h) && h >= 1) {
        out.height = Math.floor(h);
    }
    const ar = rec.aspect_ratio;
    if (typeof ar === 'number' && Number.isFinite(ar) && ar > 0) {
        out.aspect_ratio = ar;
    } else if (typeof ar === 'string' && ar.trim() !== '') {
        out.aspect_ratio = ar.trim();
    }
    return out;
}

/** Select/badge option list; `type: image` columns use a different `options` shape. */
export function selectColumnOptions(
    col: FlatpackDataTableColumn,
): FlatpackDataTableColumnOption[] {
    const o = col.options;
    return Array.isArray(o) ? o : [];
}

export function normalizeColumnTruncate(raw: unknown): number | undefined {
    if (raw == null) {
        return undefined;
    }
    const n = typeof raw === 'number' ? raw : Number(String(raw).trim());
    if (!Number.isFinite(n) || n <= 0) {
        return undefined;
    }
    return Math.min(Math.floor(n), COLUMN_TRUNCATE_MAX);
}

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

export function truncateDisplayString(text: string, maxChars: number): string {
    if (text.length <= maxChars) {
        return text;
    }
    return `${text.slice(0, maxChars)}…`;
}

export function readOnlyTruncatedDisplay(
    text: string,
    maxChars: unknown,
): { shown: string; title: string | undefined } {
    const n = normalizeColumnTruncate(maxChars);
    if (n == null) {
        return { shown: text, title: undefined };
    }
    if (text.length <= n) {
        return { shown: text, title: undefined };
    }
    return { shown: truncateDisplayString(text, n), title: text };
}

export function formatRelationCellDisplay(
    row: Record<string, unknown>,
    col: Pick<FlatpackDataTableColumn, 'relation' | 'relationName'>,
): string {
    const rel = col.relation;
    const nameKey = col.relationName;
    if (!rel || !nameKey) {
        return '';
    }
    const payload = row[rel];
    if (payload == null) {
        return '';
    }
    if (Array.isArray(payload)) {
        const parts: string[] = [];
        for (const p of payload) {
            if (p && typeof p === 'object' && nameKey in p) {
                const v = (p as Record<string, unknown>)[nameKey];
                if (v != null && String(v) !== '') {
                    parts.push(String(v));
                }
            }
        }
        return parts.join(', ');
    }
    if (
        typeof payload === 'object' &&
        !Array.isArray(payload) &&
        nameKey in payload
    ) {
        const v = (payload as Record<string, unknown>)[nameKey];
        return v == null ? '' : String(v);
    }
    return '';
}

export function dateInputSegment(raw: unknown): string {
    const s = formatCellValue(raw);
    const m = s.match(/^(\d{4}-\d{2}-\d{2})/);
    return m ? m[1] : '';
}

export function localDateSegment(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
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
    if (col.type === 'actions' || col.type === 'relation') {
        return false;
    }
    if (col.editable === false) {
        return false;
    }
    if (col.editable === true) {
        return true;
    }
    if (col.type === 'select' && selectColumnOptions(col).length > 0) {
        return true;
    }
    if (col.type === 'badge' && selectColumnOptions(col).length > 0) {
        return true;
    }
    if (col.type === 'date') {
        return true;
    }
    // Row drawer is for editing: plain text columns are editable unless opted out above.
    if (col.type === undefined || col.type === 'text') {
        return true;
    }
    return false;
}

/**
 * Notifies the parent form ({@code onValueChange}) after the current React update completes.
 * Calling parent setState synchronously inside a {@code setData} functional updater triggers
 * "Cannot update FlatpackFormPage while rendering DataTable".
 */
export function deferNotifyParentFormValues(
    onValueChange: ((value: unknown) => void) | undefined,
    nextValues: unknown,
): void {
    if (onValueChange == null) {
        return;
    }
    queueMicrotask(() => {
        onValueChange(nextValues);
    });
}
