import { describe, expect, it } from 'vitest';
import { firstErrorMessage } from '@/lib/form-errors';

describe('firstErrorMessage', () => {
    it('returns undefined for empty bag', () => {
        expect(firstErrorMessage({})).toBeUndefined();
    });

    it('returns first non-empty string value', () => {
        expect(
            firstErrorMessage({
                a: '   ',
                b: 'First',
                c: 'Second',
            }),
        ).toBe('First');
    });

    it('returns first string from first non-empty array entry', () => {
        expect(
            firstErrorMessage({
                x: ['', '  ', 'From array'],
            }),
        ).toBe('From array');
    });

    it('skips arrays with no non-empty strings', () => {
        expect(
            firstErrorMessage({
                a: ['', '  '],
                b: 'Later',
            }),
        ).toBe('Later');
    });

    it('ignores non-string primitives in bag', () => {
        expect(
            firstErrorMessage({
                n: 1,
                ok: 'OK',
            }),
        ).toBe('OK');
    });
});
