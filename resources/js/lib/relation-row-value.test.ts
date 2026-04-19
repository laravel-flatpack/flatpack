import { describe, expect, it } from 'vitest';
import { idsToRelationRows, relationRowsToIds } from '@/lib/relation-row-value';

describe('relationRowsToIds', () => {
    it('parses RelationRow objects', () => {
        expect(
            relationRowsToIds([{ id: '1' }, { id: '2', pivot: { x: 1 } }]),
        ).toEqual(['1', '2']);
    });

    it('parses legacy string ids', () => {
        expect(relationRowsToIds(['9', '10'])).toEqual(['9', '10']);
    });

    it('uses relation_value key when set', () => {
        expect(
            relationRowsToIds([{ uuid: 'abc' }, { uuid: 'def' }], 'uuid'),
        ).toEqual(['abc', 'def']);
    });
});

describe('idsToRelationRows', () => {
    it('wraps ids with default key', () => {
        expect(idsToRelationRows(['a', 'b'], 'id')).toEqual([
            { id: 'a' },
            { id: 'b' },
        ]);
    });

    it('wraps ids with custom key', () => {
        expect(idsToRelationRows(['a'], 'uuid')).toEqual([{ uuid: 'a' }]);
    });
});
