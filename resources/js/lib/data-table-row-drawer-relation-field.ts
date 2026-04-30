import { relationComboboxRemoteProps } from '@/lib/relation-combobox-remote';
import type { FlatpackDataTableColumn } from '@/types/data-table';

type RelationComboboxValue = {
    value: string;
    label: string;
};

export function isRelationComboboxDrawerField(
    column: FlatpackDataTableColumn,
    fieldType: string,
): boolean {
    return (
        fieldType === 'combobox' &&
        column.type === 'relation' &&
        Boolean(column.relation) &&
        Boolean(column.relationName) &&
        Boolean(column.relationValue)
    );
}

export function relationComboboxExtraProps(
    column: FlatpackDataTableColumn,
    flatpackEntity?: string,
    flatpackTableFieldId?: string,
    flatpackWidgetId?: string,
    portalContainer?: HTMLElement | null,
): Record<string, unknown> {
    const entity = flatpackEntity?.trim() ?? '';
    const tableField = flatpackTableFieldId?.trim() ?? '';
    const widgetId = flatpackWidgetId?.trim() ?? '';
    if (entity !== '' && tableField !== '') {
        return relationComboboxRemoteProps(
            {
                kind: 'embedded-table-column',
                entity,
                tableFieldId: tableField,
                columnId: column.id,
            },
            portalContainer,
        );
    }
    if (widgetId !== '') {
        return relationComboboxRemoteProps(
            {
                kind: 'widget-table-column',
                widgetId,
                columnId: column.id,
            },
            portalContainer,
        );
    }

    return { remote: true, portalContainer };
}

export function applyRelationComboboxDraftPatch(
    column: FlatpackDataTableColumn,
    nextSerializedValue: unknown,
    patchDraft: (patch: Record<string, unknown>) => void,
): boolean {
    if (
        nextSerializedValue !== null &&
        typeof nextSerializedValue === 'object' &&
        'value' in (nextSerializedValue as Record<string, unknown>) &&
        'label' in (nextSerializedValue as Record<string, unknown>) &&
        column.relation &&
        column.relationName &&
        column.relationValue
    ) {
        const typedValue = nextSerializedValue as RelationComboboxValue;
        const value = String(typedValue.value);
        const label = String(typedValue.label);
        patchDraft({
            [column.id]: value,
            [column.relation]: {
                [column.relationValue]: value,
                [column.relationName]: label,
            },
        });
        return true;
    }
    if (nextSerializedValue == null || nextSerializedValue === '') {
        if (!column.relation) {
            return false;
        }
        patchDraft({
            [column.id]: '',
            [column.relation]: null,
        });
        return true;
    }

    return false;
}
