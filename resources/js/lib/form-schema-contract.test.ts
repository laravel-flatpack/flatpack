import { describe, expect, it } from 'vitest';
import { SUPPORTED_FORM_FIELD_TYPES } from '@/lib/form-schema-contract';
import { FORM_FIELD_TYPES_CANONICAL } from '@/lib/generated/composition-schema-keys';

describe('form-schema-contract', () => {
    it('re-exports generated canonical form field types', () => {
        expect(SUPPORTED_FORM_FIELD_TYPES).toBe(FORM_FIELD_TYPES_CANONICAL);
    });

    it('includes common types', () => {
        expect(new Set(SUPPORTED_FORM_FIELD_TYPES).has('text')).toBe(true);
        expect(new Set(SUPPORTED_FORM_FIELD_TYPES).has('combobox')).toBe(true);
    });
});
