import { describe, expect, it } from 'vitest';
import { canonicalizeSpan, spanClassFor } from '@/lib/field-span';

describe('canonicalizeSpan', () => {
    it('maps fractions to named tokens', () => {
        expect(canonicalizeSpan('1/2')).toBe('half');
        expect(canonicalizeSpan('2/3')).toBe('two_thirds');
        expect(canonicalizeSpan('1/3')).toBe('third');
        expect(canonicalizeSpan('1/4')).toBe('quarter');
    });

    it('returns undefined for invalid input', () => {
        expect(canonicalizeSpan('wide')).toBeUndefined();
        expect(canonicalizeSpan(1)).toBeUndefined();
    });
});

describe('spanClassFor', () => {
    it('defaults to full row on page when span omitted', () => {
        expect(spanClassFor(undefined, 'page')).toBe('col-span-full');
    });

    it('always uses full width in drawer', () => {
        expect(spanClassFor('quarter', 'drawer')).toBe('col-span-full');
    });

    it('maps page fractional intents to Tailwind col-span classes', () => {
        expect(spanClassFor('half', 'page')).toContain('lg:col-span-2');
        expect(spanClassFor('quarter', 'page')).toContain('2xl:col-span-1');
    });

    it('returns undefined for dashboard when span omitted', () => {
        expect(spanClassFor(undefined, 'dashboard')).toBeUndefined();
    });

    it('maps dashboard span when set', () => {
        expect(spanClassFor('full', 'dashboard')).toContain('xl:col-span-4');
    });
});
