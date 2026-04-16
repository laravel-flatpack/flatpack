import { describe, expect, it } from 'vitest';
import { CLEAR_SELECT_ITEM_VALUE } from '@/lib/flatpack-select';

describe('CLEAR_SELECT_ITEM_VALUE', () => {
    it('is the word-joiner code point (Radix Select empty sentinel)', () => {
        expect(CLEAR_SELECT_ITEM_VALUE).toBe('\u2060');
        expect(CLEAR_SELECT_ITEM_VALUE.length).toBe(1);
    });
});
