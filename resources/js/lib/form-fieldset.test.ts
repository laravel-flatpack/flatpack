import { describe, expect, it } from 'vitest';
import {
    groupEntriesByFieldset,
    normalizeFieldsetIconKey,
    parseFieldsetFromField,
} from '@/lib/form-fieldset';
import type { FormFieldProps } from '@/types/form-fields';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

function entry(
    id: string,
    field: Record<string, unknown>,
): SchemaFieldRenderEntry {
    return {
        id,
        field: field as SchemaFieldRenderEntry['field'],
        value: undefined,
        onValueChange: () => {},
    };
}

describe('groupEntriesByFieldset', () => {
    it('returns a single plain block when no fieldset is set', () => {
        const blocks = groupEntriesByFieldset([
            entry('a', { type: 'text', label: 'A' }),
            entry('b', { type: 'text', label: 'B' }),
        ]);
        expect(blocks).toHaveLength(1);
        expect(blocks[0]?.kind).toBe('plain');
        if (blocks[0]?.kind === 'plain') {
            expect(blocks[0].entries.map((e) => e.id)).toEqual(['a', 'b']);
        }
    });

    it('groups consecutive entries with the same fieldset', () => {
        const blocks = groupEntriesByFieldset([
            entry('a', {
                type: 'text',
                label: 'A',
                fieldset: 'Contact',
            }),
            entry('b', {
                type: 'text',
                label: 'B',
                fieldset: 'Contact',
            }),
        ]);
        expect(blocks).toHaveLength(1);
        expect(blocks[0]?.kind).toBe('fieldset');
        if (blocks[0]?.kind === 'fieldset') {
            expect(blocks[0].label).toBe('Contact');
            expect(blocks[0].variant).toBe('card');
            expect(blocks[0].entries.map((e) => e.id)).toEqual(['a', 'b']);
        }
    });

    it('starts a new card when the same fieldset appears after ungrouped fields', () => {
        const blocks = groupEntriesByFieldset([
            entry('x', {
                type: 'text',
                label: 'X',
                fieldset: 'Meta',
            }),
            entry('y', { type: 'text', label: 'Y' }),
            entry('z', {
                type: 'text',
                label: 'Z',
                fieldset: 'Meta',
            }),
        ]);
        expect(blocks.map((b) => b.kind)).toEqual([
            'fieldset',
            'plain',
            'fieldset',
        ]);
        if (blocks[0]?.kind === 'fieldset' && blocks[2]?.kind === 'fieldset') {
            expect(blocks[0].variant).toBe('card');
            expect(blocks[2].variant).toBe('card');
            expect(blocks[0].entries.map((e) => e.id)).toEqual(['x']);
            expect(blocks[2].entries.map((e) => e.id)).toEqual(['z']);
        }
    });

    it('splits groups when the same label uses different variants', () => {
        const blocks = groupEntriesByFieldset([
            entry('a', {
                type: 'text',
                label: 'A',
                fieldset: { label: 'Section', variant: 'minimal' },
            }),
            entry('b', {
                type: 'text',
                label: 'B',
                fieldset: { label: 'Section', variant: 'card' },
            }),
        ]);
        expect(blocks).toHaveLength(2);
        expect(blocks[0]?.kind).toBe('fieldset');
        expect(blocks[1]?.kind).toBe('fieldset');
        if (blocks[0]?.kind === 'fieldset' && blocks[1]?.kind === 'fieldset') {
            expect(blocks[0].variant).toBe('minimal');
            expect(blocks[1].variant).toBe('card');
        }
    });

    it('groups object fieldsets by label and keeps first icon', () => {
        const blocks = groupEntriesByFieldset([
            entry('a', {
                type: 'text',
                label: 'A',
                fieldset: { label: 'Section', icon: 'clock' },
            }),
            entry('b', {
                type: 'text',
                label: 'B',
                fieldset: { label: 'Section', icon: 'file-text' },
            }),
        ]);
        expect(blocks).toHaveLength(1);
        expect(blocks[0]?.kind).toBe('fieldset');
        if (blocks[0]?.kind === 'fieldset') {
            expect(blocks[0].label).toBe('Section');
            expect(blocks[0].variant).toBe('card');
            expect(blocks[0].icon).toBe('clock');
        }
    });

    it('splits groups when same label and variant differ on collapsed', () => {
        const blocks = groupEntriesByFieldset([
            entry('a', {
                type: 'text',
                label: 'A',
                fieldset: { label: 'Section', collapsed: false },
            }),
            entry('b', {
                type: 'text',
                label: 'B',
                fieldset: { label: 'Section', collapsed: true },
            }),
        ]);
        expect(blocks).toHaveLength(2);
        if (blocks[0]?.kind === 'fieldset' && blocks[1]?.kind === 'fieldset') {
            expect(blocks[0].collapsed).toBe(false);
            expect(blocks[0].entries.map((e) => e.id)).toEqual(['a']);
            expect(blocks[1].collapsed).toBe(true);
            expect(blocks[1].entries.map((e) => e.id)).toEqual(['b']);
        }
    });

    it('groups when collapsed flag matches', () => {
        const blocks = groupEntriesByFieldset([
            entry('a', {
                type: 'text',
                label: 'A',
                fieldset: { label: 'Section', collapsed: true },
            }),
            entry('b', {
                type: 'text',
                label: 'B',
                fieldset: { label: 'Section', collapsed: true },
            }),
        ]);
        expect(blocks).toHaveLength(1);
        if (blocks[0]?.kind === 'fieldset') {
            expect(blocks[0].collapsed).toBe(true);
            expect(blocks[0].entries.map((e) => e.id)).toEqual(['a', 'b']);
        }
    });
});

describe('parseFieldsetFromField', () => {
    it('parses object fieldset and normalizes icon', () => {
        const parsed = parseFieldsetFromField({
            type: 'text',
            label: 'X',
            fieldset: { label: 'Content', icon: 'file_text' },
        });
        expect(parsed).toEqual({
            label: 'Content',
            icon: 'file-text',
            variant: 'card',
        });
    });

    it('parses variant on object fieldset', () => {
        expect(
            parseFieldsetFromField({
                type: 'text',
                label: 'X',
                fieldset: { label: 'Y', variant: 'plain' },
            }),
        ).toEqual({ label: 'Y', variant: 'plain' });

        expect(
            parseFieldsetFromField({
                type: 'text',
                label: 'X',
                fieldset: { label: 'Y', variant: 'bogus' },
            } as unknown as FormFieldProps),
        ).toEqual({ label: 'Y', variant: 'card' });
    });

    it('normalizes icon keys', () => {
        expect(normalizeFieldsetIconKey('Clock')).toBe('clock');
        expect(normalizeFieldsetIconKey('file_text')).toBe('file-text');
    });

    it('includes collapsed when boolean and variant is not none', () => {
        expect(
            parseFieldsetFromField({
                type: 'text',
                label: 'X',
                fieldset: { label: 'Z', collapsed: true },
            }),
        ).toEqual({
            label: 'Z',
            variant: 'card',
            collapsed: true,
        });
    });

    it('ignores collapsed when variant is none', () => {
        expect(
            parseFieldsetFromField({
                type: 'text',
                label: 'X',
                fieldset: { label: 'Z', variant: 'none', collapsed: true },
            }),
        ).toEqual({
            label: 'Z',
            variant: 'none',
        });
    });
});
