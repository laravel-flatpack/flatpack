import {
    columnEditableInDrawer,
    selectColumnOptions,
} from '@/lib/data-table-utils';
import type { FlatpackDataTableColumn } from '@/types/data-table';
import type { DrawerMappedField } from '@/types/data-table-row-drawer-field-mapper';
import type { FormFieldProps } from '@/types/form-fields';

const supportedDrawerFieldTypes = new Set<FormFieldProps['type']>([
    'text',
    'textarea',
    'select',
    'combobox',
    'date-picker',
    'date-range-picker',
    'time-picker',
    'checkbox',
    'switch',
    'rich-text',
    'block-editor',
    'table',
]);

function mappedTypeFromColumn(
    col: FlatpackDataTableColumn,
): FormFieldProps['type'] | null {
    const override = coerceEditFormFieldType(col.editFormField?.type);
    if (override !== null) {
        return override;
    }

    if (
        col.type === 'relation' &&
        col.relation &&
        col.relationName &&
        col.relationValue
    ) {
        return 'combobox';
    }
    if (col.type === 'date') {
        return 'date-picker';
    }
    if (
        col.type === 'select' ||
        (col.type === 'badge' && selectColumnOptions(col).length > 0)
    ) {
        return 'select';
    }
    if (columnEditableInDrawer(col)) {
        return 'text';
    }
    return null;
}

/**
 * Maps an embedded table column to a drawer render contract.
 */
export function mapDataTableColumnToDrawerField(
    col: FlatpackDataTableColumn,
): DrawerMappedField | null {
    const mappedType = mappedTypeFromColumn(col);
    if (mappedType === null || mappedType === 'table') {
        return null;
    }

    const override = coerceEditFormField(col.editFormField);
    if (override != null) {
        return {
            kind: 'form',
            field: overrideFieldWithColumnDefaults(override, col),
        };
    }

    if (mappedType === 'combobox') {
        return {
            kind: 'form',
            field: {
                type: 'combobox',
                label: col.label,
                placeholder: 'Choose...',
                options: selectColumnOptions(col),
                multiple: false,
                relation:
                    col.type === 'relation' ? (col.relation ?? '') : undefined,
                relation_name:
                    col.type === 'relation'
                        ? (col.relationName ?? '')
                        : undefined,
                relation_value:
                    col.type === 'relation'
                        ? (col.relationValue ?? '')
                        : undefined,
                remote: col.type === 'relation',
                emitObject: col.type === 'relation',
            },
        };
    }

    if (mappedType === 'select') {
        return {
            kind: 'form',
            field: {
                type: 'select',
                label: col.label,
                placeholder: 'Choose...',
                options: selectColumnOptions(col),
            },
        };
    }

    if (mappedType === 'date-picker') {
        return {
            kind: 'form',
            field: {
                type: 'date-picker',
                label: col.label,
                placeholder: 'Pick a date...',
            },
        };
    }

    return {
        kind: 'form',
        field: {
            type: 'text',
            label: col.label,
            placeholder: '',
        },
    };
}

function coerceEditFormFieldType(raw: unknown): FormFieldProps['type'] | null {
    if (typeof raw !== 'string') {
        return null;
    }
    const t = raw.trim() as FormFieldProps['type'];
    return supportedDrawerFieldTypes.has(t) ? t : null;
}

function coerceEditFormField(
    raw: unknown,
): Exclude<FormFieldProps, { type: 'table' }> | null {
    if (raw == null || typeof raw !== 'object') {
        return null;
    }
    const rec = raw as Record<string, unknown>;
    const type = coerceEditFormFieldType(rec.type);
    if (type == null || type === 'table') {
        return null;
    }
    if (!isValidEditFormFieldShape(type, rec)) {
        return null;
    }
    return {
        ...rec,
        type,
    } as Exclude<FormFieldProps, { type: 'table' }>;
}

function isValidEditFormFieldShape(
    type: Exclude<FormFieldProps['type'], 'table'>,
    rec: Record<string, unknown>,
): boolean {
    if ('label' in rec && rec.label != null && typeof rec.label !== 'string') {
        return false;
    }
    if (
        'placeholder' in rec &&
        rec.placeholder != null &&
        typeof rec.placeholder !== 'string'
    ) {
        return false;
    }

    if (type === 'select' || type === 'combobox') {
        if (
            'options' in rec &&
            rec.options != null &&
            !Array.isArray(rec.options)
        ) {
            return false;
        }
    }

    if (type === 'combobox') {
        if ('multiple' in rec && typeof rec.multiple !== 'boolean') {
            return false;
        }
        if ('remote' in rec && typeof rec.remote !== 'boolean') {
            return false;
        }
        if ('emitObject' in rec && typeof rec.emitObject !== 'boolean') {
            return false;
        }
        if (
            'relation' in rec &&
            rec.relation != null &&
            typeof rec.relation !== 'string'
        ) {
            return false;
        }
        if (
            'relation_name' in rec &&
            rec.relation_name != null &&
            typeof rec.relation_name !== 'string'
        ) {
            return false;
        }
        if (
            'relation_value' in rec &&
            rec.relation_value != null &&
            typeof rec.relation_value !== 'string'
        ) {
            return false;
        }
    }

    return true;
}

function overrideFieldWithColumnDefaults(
    override: Exclude<FormFieldProps, { type: 'table' }>,
    col: FlatpackDataTableColumn,
): Exclude<FormFieldProps, { type: 'table' }> {
    if (override.type === 'select') {
        return {
            ...override,
            label: override.label || col.label,
            options: override.options ?? col.options ?? [],
            placeholder: override.placeholder ?? 'Choose...',
        };
    }

    if (override.type === 'combobox') {
        const relation =
            override.relation ??
            (col.type === 'relation' ? col.relation : undefined);
        const relationName =
            override.relation_name ??
            (col.type === 'relation' ? col.relationName : undefined);
        const relationValue =
            override.relation_value ??
            (col.type === 'relation' ? col.relationValue : undefined);
        const hasRelation = typeof relation === 'string' && relation !== '';
        return {
            ...override,
            label: override.label || col.label,
            options: override.options ?? col.options ?? [],
            placeholder: override.placeholder ?? 'Choose...',
            relation,
            relation_name: relationName,
            relation_value: relationValue,
            remote: override.remote ?? hasRelation,
            emitObject:
                typeof override.emitObject === 'boolean'
                    ? override.emitObject
                    : hasRelation,
        };
    }

    return {
        ...override,
        label: override.label || col.label,
    };
}
