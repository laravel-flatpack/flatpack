import { describe, expect, it } from 'vitest';
import {
    isEmbeddedTableAddToolbarAction,
    isEmbeddedTableBelongsToManyAttachToolbarAction,
    isEmbeddedTableCreateDraftToolbarAction,
} from '@/lib/data-table-action-semantics';

describe('embedded table toolbar action semantics', () => {
    it('treats create and add as draft-new-row drawer', () => {
        expect(isEmbeddedTableCreateDraftToolbarAction('create')).toBe(true);
        expect(isEmbeddedTableCreateDraftToolbarAction('CREATE')).toBe(true);
        expect(isEmbeddedTableCreateDraftToolbarAction('add')).toBe(true);
        expect(isEmbeddedTableCreateDraftToolbarAction('ADD')).toBe(true);
        expect(isEmbeddedTableCreateDraftToolbarAction('append')).toBe(false);
        expect(isEmbeddedTableCreateDraftToolbarAction('new')).toBe(false);
        expect(isEmbeddedTableCreateDraftToolbarAction(undefined)).toBe(false);
    });

    it('recognizes add for attach-existing flow', () => {
        expect(isEmbeddedTableAddToolbarAction('add')).toBe(true);
        expect(isEmbeddedTableAddToolbarAction('ADD')).toBe(true);
        expect(isEmbeddedTableAddToolbarAction('create')).toBe(false);
    });

    it('treats attach as BTM attach drawer mode keyword', () => {
        expect(isEmbeddedTableBelongsToManyAttachToolbarAction('attach')).toBe(
            true,
        );
        expect(isEmbeddedTableBelongsToManyAttachToolbarAction('ATTACH')).toBe(
            true,
        );
        expect(isEmbeddedTableBelongsToManyAttachToolbarAction('create')).toBe(
            false,
        );
    });
});
