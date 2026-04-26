import { describe, expect, it } from 'vitest';
import {
    emptyValueForField,
    evaluateFieldTrigger,
    triggerConditionMatches,
} from '@/lib/form-field-trigger';

describe('triggerConditionMatches', () => {
    it('matches checked and unchecked conditions', () => {
        expect(triggerConditionMatches(true, 'checked')).toBe(true);
        expect(triggerConditionMatches(false, 'checked')).toBe(false);
        expect(triggerConditionMatches(false, 'unchecked')).toBe(true);
    });

    it('matches value[x] conditions for scalar and arrays', () => {
        expect(triggerConditionMatches('draft', 'value[draft]')).toBe(true);
        expect(triggerConditionMatches(['draft', 'published'], 'value[draft]')).toBe(
            true,
        );
        expect(triggerConditionMatches('published', 'value[draft]')).toBe(false);
    });
});

describe('evaluateFieldTrigger', () => {
    it('maps show/hide and enable/disable actions', () => {
        expect(
            evaluateFieldTrigger(
                { action: 'show', field: 'a', condition: 'checked' },
                { a: true },
            ),
        ).toEqual({ visible: true, disabled: false, shouldEmpty: false });
        expect(
            evaluateFieldTrigger(
                { action: 'hide', field: 'a', condition: 'checked' },
                { a: true },
            ),
        ).toEqual({ visible: false, disabled: false, shouldEmpty: false });
        expect(
            evaluateFieldTrigger(
                { action: 'enable', field: 'a', condition: 'checked' },
                { a: false },
            ),
        ).toEqual({ visible: true, disabled: true, shouldEmpty: false });
        expect(
            evaluateFieldTrigger(
                { action: 'disable', field: 'a', condition: 'checked' },
                { a: true },
            ),
        ).toEqual({ visible: true, disabled: true, shouldEmpty: false });
    });

    it('marks empty action when condition is true', () => {
        expect(
            evaluateFieldTrigger(
                { action: 'empty', field: 'state', condition: 'value[draft]' },
                { state: 'draft' },
            ),
        ).toEqual({ visible: true, disabled: false, shouldEmpty: true });
    });
});

describe('emptyValueForField', () => {
    it('returns type-appropriate empty values', () => {
        expect(emptyValueForField({ type: 'text', label: 'T' })).toBe('');
        expect(
            emptyValueForField({
                type: 'combobox',
                label: 'Tags',
                options: [],
                multiple: true,
            }),
        ).toEqual([]);
        expect(
            emptyValueForField({ type: 'checkbox', label: 'Enabled' }),
        ).toBe(false);
    });
});
