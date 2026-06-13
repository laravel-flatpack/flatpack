import { describe, expect, it } from 'vitest';
import {
    CODE_BLOCK_LANGUAGE_OPTIONS,
    codeBlockLowlight,
} from '@/lib/code-block-lowlight';

describe('codeBlockLowlight', () => {
    it('lists registered grammar names', () => {
        const names = codeBlockLowlight.listLanguages();
        expect(names).toEqual(
            expect.arrayContaining(['javascript', 'php', 'xml']),
        );
    });

    it('highlights canonical language ids', () => {
        const tree = codeBlockLowlight.highlight('javascript', 'const x = 1');
        expect(tree.type).toBe('root');
        expect(tree.children.length).toBeGreaterThan(0);
    });

    it('resolves registered aliases (e.g. ts → typescript)', () => {
        const tree = codeBlockLowlight.highlight('ts', 'const n: number = 1');
        expect(tree.data?.language).toBe('ts');
        expect(tree.children.length).toBeGreaterThan(0);
    });

    it('highlights HTML using the xml grammar', () => {
        const tree = codeBlockLowlight.highlight('html', '<p>hi</p>');
        expect(tree.data?.language).toBe('html');
    });
});

describe('CODE_BLOCK_LANGUAGE_OPTIONS', () => {
    it('puts Auto first', () => {
        expect(CODE_BLOCK_LANGUAGE_OPTIONS[0]).toEqual({
            label: 'Auto',
            value: 'auto',
        });
    });

    it('uses unique values', () => {
        const values = CODE_BLOCK_LANGUAGE_OPTIONS.map((o) => o.value);
        expect(new Set(values).size).toBe(values.length);
    });

    it('only references languages that lowlight can highlight (except auto)', () => {
        for (const { value } of CODE_BLOCK_LANGUAGE_OPTIONS) {
            if (value === 'auto') continue;
            expect(() => codeBlockLowlight.highlight(value, 'x')).not.toThrow();
        }
    });
});
