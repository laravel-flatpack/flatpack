import { describe, expect, it } from 'vitest';
import {
    rowValidationFieldErrorsByStableId,
    rowValidationMessagesByStableId,
    tableFieldErrorState,
} from '@/lib/form-table-errors';

describe('tableFieldErrorState', () => {
    it('parses nested table validation keys for one table field', () => {
        const result = tableFieldErrorState(
            {
                'values.comments.0.user_id': 'User id is required.',
                'values.comments.0.content': ['Content is required.'],
                'values.comments.2.user_id': 'User id is invalid.',
                title: 'Title is required.',
            },
            'comments',
        );

        expect(result).not.toBeNull();
        expect(result?.rowIndexesWithErrors).toEqual([0, 2]);
        expect(result?.messagesByRowIndex[0]).toEqual([
            'User id is required.',
            'Content is required.',
        ]);
    });

    it('returns null when there are no nested keys for field', () => {
        expect(
            tableFieldErrorState(
                { 'values.tags.0.name': 'Name is required.' },
                'comments',
            ),
        ).toBeNull();
    });

    it('accepts nested keys without values prefix', () => {
        const result = tableFieldErrorState(
            {
                'comments.0.user_id': 'User id is required.',
            },
            'comments',
        );
        expect(result?.rowIndexesWithErrors).toEqual([0]);
        expect(result?.messagesByRowIndex[0]).toEqual(['User id is required.']);
    });
});

describe('rowValidationMessagesByStableId', () => {
    it('maps nested row index errors to stable row ids', () => {
        const errorState = tableFieldErrorState(
            {
                'values.comments.0.user_id': 'User id is required.',
                'values.comments.1.content': 'Content is required.',
            },
            'comments',
        );
        const byId = rowValidationMessagesByStableId(
            [{ name: 'new row' }, { id: '12', name: 'persisted' }],
            errorState,
        );

        expect(byId['row-0']).toEqual(['User id is required.']);
        expect(byId['12']).toEqual(['Content is required.']);
    });
});

describe('rowValidationFieldErrorsByStableId', () => {
    it('maps row and column messages by stable row id', () => {
        const errorState = tableFieldErrorState(
            {
                'values.comments.0.user_id': 'User id is required.',
                'values.comments.1.content': 'Content is required.',
            },
            'comments',
        );
        const byId = rowValidationFieldErrorsByStableId(
            [{ name: 'new row' }, { id: '12', name: 'persisted' }],
            errorState,
        );

        expect(byId['row-0'].user_id).toEqual(['User id is required.']);
        expect(byId['12'].content).toEqual(['Content is required.']);
    });
});
