import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { ComboboxField } from '@/components/form-fields/combobox';

describe('ComboboxField', () => {
    it('allows selecting an option while invalid', async () => {
        const user = userEvent.setup();
        const onValueChange = vi.fn();

        render(
            <ComboboxField
                id="user_id"
                label="User"
                multiple={false}
                items={[
                    { value: '1', label: 'Ada' },
                    { value: '2', label: 'Grace' },
                ]}
                singlePlaceholder="Choose user"
                multiPlaceholder="Choose user"
                value={null}
                invalid
                onValueChange={onValueChange}
            />,
        );

        await user.click(screen.getByRole('combobox', { name: 'User' }));
        await user.click(screen.getByRole('option', { name: 'Ada' }));

        expect(onValueChange).toHaveBeenCalledWith('1');
    });
});
