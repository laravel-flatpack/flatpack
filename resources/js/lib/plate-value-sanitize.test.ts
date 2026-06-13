import type { Value } from 'platejs';
import { Text } from 'slate';
import { describe, expect, it } from 'vitest';
import { sanitizePlateValue } from '@/lib/plate-value-sanitize';

describe('sanitizePlateValue', () => {
    it('coerces text:null leaves to empty string so Slate recognizes text nodes', () => {
        const input = [
            {
                type: 'p',
                children: [{ text: null }],
                id: 'x',
            },
        ] as unknown as Value;

        const out = sanitizePlateValue(input);
        expect(out).toHaveLength(1);
        const leaf = (out[0] as { children: { text: string }[] }).children[0];
        expect(Text.isText(leaf)).toBe(true);
        expect(leaf.text).toBe('');
    });

    it('fills missing children on elements', () => {
        const input = [{ type: 'p', id: 'y' }] as unknown as Value;
        const out = sanitizePlateValue(input);
        expect((out[0] as { children: unknown[] }).children).toEqual([
            { text: '' },
        ]);
    });

    it('uses a single empty paragraph when value is empty', () => {
        expect(sanitizePlateValue([])).toEqual([
            { type: 'p', children: [{ text: '' }] },
        ]);
    });

    it('preserves valid paragraphs', () => {
        const input: Value = [
            {
                type: 'p',
                children: [{ text: 'hello' }],
            },
        ];
        expect(sanitizePlateValue(input)).toEqual(input);
    });
});
