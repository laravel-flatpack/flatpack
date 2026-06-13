import type {
    FlatpackDataTableActionButton,
    FlatpackDataTableBulkAction,
    FlatpackFormTableToolbarAction,
    FlatpackTableRelationType,
} from '@/types/data-table';

/** Appended when a relation `type: table` has no `type: actions` column. */
export const EMBEDDED_RELATION_DEFAULT_ACTIONS_COLUMN_ID =
    '_row_actions' as const;

export function parseTableRelationType(
    value: unknown,
): FlatpackTableRelationType | undefined {
    return parseTableRelationTypeString(value);
}

function parseTableRelationTypeString(
    value: unknown,
): FlatpackTableRelationType | undefined {
    if (typeof value !== 'string') {
        return undefined;
    }
    if (!TABLE_RELATION_TYPES.includes(value as FlatpackTableRelationType)) {
        return undefined;
    }
    return value as FlatpackTableRelationType;
}

const TABLE_RELATION_TYPES: readonly FlatpackTableRelationType[] = [
    'belongs_to_many',
    'has_many',
    'morph_many',
    'morph_to_many',
    'has_one',
    'unknown',
];

function effectiveRelationType(
    t: FlatpackTableRelationType | undefined,
): FlatpackTableRelationType {
    return t ?? 'unknown';
}

export function getDefaultRelationToolbarActions(
    type: FlatpackTableRelationType | undefined,
): FlatpackFormTableToolbarAction[] {
    const t = effectiveRelationType(type);
    const create: FlatpackFormTableToolbarAction = {
        id: 'create',
        label: 'Create',
        action: 'create',
        variant: 'default',
    };
    const attach: FlatpackFormTableToolbarAction = {
        id: 'attach',
        label: 'Attach',
        action: 'attach',
        variant: 'outline',
    };
    if (t === 'belongs_to_many' || t === 'morph_to_many') {
        return [create, attach];
    }
    return [create];
}

export function getDefaultRelationBulkActions(
    _type: FlatpackTableRelationType | undefined,
): FlatpackDataTableBulkAction[] {
    return [
        {
            id: 'delete',
            label: 'Delete selected',
            action: 'delete',
            variant: 'destructive',
            confirm: true,
        },
    ];
}

function rowRemoveLabel(type: FlatpackTableRelationType | undefined): string {
    const t = effectiveRelationType(type);
    if (t === 'belongs_to_many' || t === 'morph_to_many') {
        return 'Detach';
    }
    return 'Remove';
}

export function getDefaultRelationRowActions(
    type: FlatpackTableRelationType | undefined,
): FlatpackDataTableActionButton[] {
    return [
        { label: 'Edit', action: 'edit', icon: 'edit' },
        {
            label: rowRemoveLabel(type),
            action: 'remove',
            confirm: true,
            variant: 'destructive',
        },
    ];
}
