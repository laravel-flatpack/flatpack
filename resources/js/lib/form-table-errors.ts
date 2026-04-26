import { stableRowId } from '@/lib/data-table-utils';

export type TableFieldRowError = {
    key: string;
    rowIndex: number;
    columnId: string;
    message: string;
};

export type TableFieldErrorState = {
    rowIndexesWithErrors: number[];
    messagesByRowIndex: Record<number, string[]>;
    rowErrors: TableFieldRowError[];
};

function errorMessagesForValue(value: unknown): string[] {
    if (typeof value === 'string' && value.trim() !== '') {
        return [value];
    }
    if (!Array.isArray(value)) {
        return [];
    }
    return value.filter(
        (item): item is string =>
            typeof item === 'string' && item.trim() !== '',
    );
}

export function tableFieldErrorState(
    errors: Record<string, unknown>,
    tableFieldId: string,
): TableFieldErrorState | null {
    const escapedFieldId = tableFieldId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const nestedKey = new RegExp(
        `^(?:values\\.)?${escapedFieldId}\\.(\\d+)\\.([^.]*)$`,
    );

    const rowErrors: TableFieldRowError[] = [];
    const messagesByRowIndex: Record<number, string[]> = {};

    for (const [key, value] of Object.entries(errors)) {
        const match = nestedKey.exec(key);
        if (match == null) {
            continue;
        }
        const rowIndex = Number(match[1]);
        if (!Number.isInteger(rowIndex) || rowIndex < 0) {
            continue;
        }
        const columnId = match[2];
        for (const message of errorMessagesForValue(value)) {
            rowErrors.push({ key, rowIndex, columnId, message });
            const current = messagesByRowIndex[rowIndex] ?? [];
            if (!current.includes(message)) {
                current.push(message);
                messagesByRowIndex[rowIndex] = current;
            }
        }
    }

    if (rowErrors.length === 0) {
        return null;
    }

    const rowIndexesWithErrors = Object.keys(messagesByRowIndex)
        .map((index) => Number(index))
        .filter((index) => Number.isInteger(index))
        .sort((a, b) => a - b);

    return {
        rowIndexesWithErrors,
        messagesByRowIndex,
        rowErrors,
    };
}

export function rowValidationMessagesByStableId(
    rows: unknown,
    errorState: TableFieldErrorState | null,
): Record<string, string[]> {
    if (!Array.isArray(rows) || errorState == null) {
        return {};
    }
    const byId: Record<string, string[]> = {};
    for (const rowIndex of errorState.rowIndexesWithErrors) {
        const row = rows[rowIndex];
        if (typeof row !== 'object' || row === null || Array.isArray(row)) {
            continue;
        }
        const stableId = stableRowId(row as Record<string, unknown>, rowIndex);
        const messages = errorState.messagesByRowIndex[rowIndex];
        if (messages != null && messages.length > 0) {
            byId[stableId] = messages;
        }
    }
    return byId;
}

export function rowValidationFieldErrorsByStableId(
    rows: unknown,
    errorState: TableFieldErrorState | null,
): Record<string, Record<string, string[]>> {
    if (!Array.isArray(rows) || errorState == null) {
        return {};
    }
    const byId: Record<string, Record<string, string[]>> = {};

    for (const rowError of errorState.rowErrors) {
        const row = rows[rowError.rowIndex];
        if (typeof row !== 'object' || row === null || Array.isArray(row)) {
            continue;
        }
        const stableId = stableRowId(
            row as Record<string, unknown>,
            rowError.rowIndex,
        );
        const rowErrorsByColumn = byId[stableId] ?? {};
        const current = rowErrorsByColumn[rowError.columnId] ?? [];
        if (!current.includes(rowError.message)) {
            rowErrorsByColumn[rowError.columnId] = [
                ...current,
                rowError.message,
            ];
        }
        byId[stableId] = rowErrorsByColumn;
    }

    return byId;
}
