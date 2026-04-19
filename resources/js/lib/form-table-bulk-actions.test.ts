import { describe, expect, it } from 'vitest';
import { normalizeFormTableBulkActionsInput } from '@/lib/form-table-bulk-actions';

describe('normalizeFormTableBulkActionsInput', () => {
    it('maps bulk_actions map to bulk action list with ids', () => {
        const out = normalizeFormTableBulkActionsInput({
            bulk_actions: {
                remove: {
                    label: 'Delete',
                    action: 'remove',
                    variant: 'destructive',
                    confirm: true,
                    icon: 'trash',
                },
            },
        });
        expect(out).toEqual([
            {
                id: 'remove',
                label: 'Delete',
                action: 'remove',
                variant: 'destructive',
                confirm: true,
                icon: 'trash',
            },
        ]);
    });

    it('reads camelCase bulkActions array', () => {
        const out = normalizeFormTableBulkActionsInput({
            bulkActions: [
                {
                    id: 'a',
                    label: 'L',
                    action: 'remove',
                },
            ],
        });
        expect(out?.[0]?.id).toBe('a');
        expect(out?.[0]?.action).toBe('remove');
    });
});
