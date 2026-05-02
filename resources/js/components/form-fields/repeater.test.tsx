import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import { RepeaterField } from '@/components/form-fields/repeater';

const itemFields = {
    name: {
        type: 'text' as const,
        label: 'Name',
    },
};

afterEach(() => {
    cleanup();
});

describe('RepeaterField', () => {
    it('does not show drag handle when minItems and maxItems are 1', () => {
        render(
            <RepeaterField
                id="r"
                label="Items"
                minItems={1}
                maxItems={1}
                fields={itemFields}
                value={[{}]}
            />,
        );
        expect(
            screen.queryByRole('button', { name: /drag to reorder row/i }),
        ).toBeNull();
    });

    it('does not show drag handle with a single row when more rows are allowed', () => {
        render(
            <RepeaterField
                id="r"
                label="Items"
                minItems={0}
                maxItems={5}
                fields={itemFields}
                value={[{}]}
            />,
        );
        expect(
            screen.queryByRole('button', { name: /drag to reorder row/i }),
        ).toBeNull();
    });

    it('shows drag handles when two or more rows and reorder is enabled', () => {
        render(
            <RepeaterField
                id="r"
                label="Items"
                fields={itemFields}
                value={[{}, {}]}
            />,
        );
        const grips = screen.getAllByRole('button', {
            name: /drag to reorder row/i,
        });
        expect(grips).toHaveLength(2);
    });

    it('hides drag handles when showReorder is false', () => {
        render(
            <RepeaterField
                id="r"
                label="Items"
                showReorder={false}
                fields={itemFields}
                value={[{}, {}]}
            />,
        );
        expect(
            screen.queryAllByRole('button', { name: /drag to reorder row/i }),
        ).toHaveLength(0);
    });
});
