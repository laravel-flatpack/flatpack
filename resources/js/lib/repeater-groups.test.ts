import { describe, expect, it } from 'vitest';
import {
    mapRepeaterGroupsById,
    parseRepeaterGroups,
} from '@/lib/repeater-groups';

describe('parseRepeaterGroups', () => {
    it('parses object map of groups', () => {
        const r = parseRepeaterGroups({
            hero: {
                label: 'Hero',
                fields: {
                    title: { type: 'text', label: 'Title' },
                },
            },
        });
        expect(r.status).toBe('inline');
        if (r.status !== 'inline') {
            return;
        }
        expect(r.groups).toHaveLength(1);
        expect(r.groups[0]?.id).toBe('hero');
        expect(r.groups[0]?.fields.title?.type).toBe('text');
    });

    it('uses explicit key over map key when set', () => {
        const r = parseRepeaterGroups({
            hero: {
                key: 'hero-block',
                label: 'Hero',
                fields: {
                    title: { type: 'text', label: 'Title' },
                },
            },
        });
        expect(r.status).toBe('inline');
        if (r.status !== 'inline') {
            return;
        }
        expect(r.groups[0]?.id).toBe('hero-block');
    });

    it('parses array form with explicit keys', () => {
        const r = parseRepeaterGroups([
            {
                key: 'a',
                label: 'A',
                fields: { x: { type: 'text', label: 'X' } },
            },
            {
                key: 'b',
                label: 'B',
                fields: { y: { type: 'text', label: 'Y' } },
            },
        ]);
        expect(r.status).toBe('inline');
        if (r.status !== 'inline') {
            return;
        }
        expect(r.groups.map((g) => g.id)).toEqual(['a', 'b']);
    });

    it('detects yaml path string', () => {
        const r = parseRepeaterGroups('groups.yaml');
        expect(r.status).toBe('yaml-path');
        if (r.status !== 'yaml-path') {
            return;
        }
        expect(r.path).toBe('groups.yaml');
    });

    it('rejects duplicate ids in map', () => {
        const r = parseRepeaterGroups({
            a: {
                key: 'dup',
                label: 'A',
                fields: { x: { type: 'text', label: 'X' } },
            },
            b: {
                key: 'dup',
                label: 'B',
                fields: { y: { type: 'text', label: 'Y' } },
            },
        });
        expect(r.status).toBe('invalid');
    });

    it('maps by id', () => {
        const r = parseRepeaterGroups({
            q: {
                label: 'Quote',
                fields: { body: { type: 'textarea', label: 'Body' } },
            },
        });
        expect(r.status).toBe('inline');
        if (r.status !== 'inline') {
            return;
        }
        const byId = mapRepeaterGroupsById(r.groups);
        expect(byId.get('q')?.label).toBe('Quote');
    });
});
