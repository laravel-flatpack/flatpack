import { describe, expect, it } from 'vitest';
import {
    getDefaultRelationBulkActions,
    getDefaultRelationRowActions,
    getDefaultRelationToolbarActions,
    parseTableRelationType,
} from '@/lib/embedded-relation-table-defaults';

describe('embedded-relation-table-defaults', () => {
    it('parses table_relation_type from schema', () => {
        expect(parseTableRelationType('has_many')).toBe('has_many');
        expect(parseTableRelationType('not-a-type')).toBeUndefined();
    });

    it('returns create+attach for BTM / morph_to_many', () => {
        expect(getDefaultRelationToolbarActions('belongs_to_many')).toEqual([
            {
                id: 'create',
                label: 'Create',
                action: 'create',
                variant: 'default',
            },
            {
                id: 'attach',
                label: 'Attach',
                action: 'attach',
                variant: 'outline',
            },
        ]);
        expect(getDefaultRelationToolbarActions('morph_to_many')).toHaveLength(
            2,
        );
    });

    it('returns create only for has_many, unknown, has_one', () => {
        expect(getDefaultRelationToolbarActions('has_many')).toEqual([
            {
                id: 'create',
                label: 'Create',
                action: 'create',
                variant: 'default',
            },
        ]);
        expect(getDefaultRelationToolbarActions(undefined)).toEqual([
            {
                id: 'create',
                label: 'Create',
                action: 'create',
                variant: 'default',
            },
        ]);
    });

    it('returns default bulk delete with confirm', () => {
        expect(getDefaultRelationBulkActions('has_many')).toEqual([
            {
                id: 'delete',
                label: 'Delete selected',
                action: 'delete',
                variant: 'destructive',
                confirm: true,
            },
        ]);
    });

    it('labels remove vs detach for row actions', () => {
        const hm = getDefaultRelationRowActions('has_many');
        expect(hm.find((a) => a.action === 'remove')?.label).toBe('Remove');
        const btm = getDefaultRelationRowActions('belongs_to_many');
        expect(btm.find((a) => a.action === 'remove')?.label).toBe('Detach');
    });
});
