import type { FlatpackDataTableColumn } from '@/types/data-table';

const COLUMN_TRUNCATE_MAX = 1_000_000;

/** Coerces YAML/JSON `truncate` to a positive integer, or `undefined` when absent/invalid. */
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

/**
 * Shortens `text` when longer than `maxChars`. Prefer {@link readOnlyTruncatedDisplay} when the
 * limit comes from an optional column setting.
 */
export function truncateDisplayString(text: string, maxChars: number): string {
    if (text.length <= maxChars) {
        return text;
    }
    return `${text.slice(0, maxChars)}…`;
}

/**
 * Read-only table cells: when `maxChars` (from column `truncate`, optional) is a positive number,
 * truncates with an ellipsis and returns the full `text` for use as `title`. Accepts the raw
 * YAML/JSON value (number or numeric string).
 */
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

/**
 * Display string for `type: relation` columns using the eager-loaded payload on `row[relation]`
 * (single model shape or list for many-to-many).
 */
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
