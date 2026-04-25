import { describe, expect, it } from 'vitest';
import { summarizeFormValuesForDebug } from '@/lib/flatpack-form-debug';

describe('summarizeFormValuesForDebug', () => {
    it('summarizes table arrays with row shape', () => {
        const s = summarizeFormValuesForDebug({
            title: 'x',
            comments: [{ id: 1, body: 'a', user: { id: '1', name: 'U' } }],
        });
        expect(s.title).toBe('x');
        const c = s.comments as { _type: string; length: number };
        expect(c._type).toBe('array');
        expect(c.length).toBe(1);
    });
});
