import { cleanup, render, screen, waitFor } from '@testing-library/react';
import { Suspense } from 'react';
import { afterEach, describe, expect, it } from 'vitest';
import { loadField } from '@/lib/form';

describe('loadField', () => {
    afterEach(() => {
        cleanup();
    });

    it('lazy-loads TextField for type text', async () => {
        const LazyText = loadField('text');
        render(
            <Suspense fallback={<span data-testid="suspense-fb">wait</span>}>
                <LazyText
                    id="f1"
                    label="Label"
                    placeholder="ph"
                    onValueChange={() => {}}
                />
            </Suspense>,
        );

        expect(screen.getByTestId('suspense-fb')).toBeInTheDocument();

        await waitFor(() => {
            expect(screen.queryByTestId('suspense-fb')).not.toBeInTheDocument();
        });

        expect(screen.getByRole('textbox')).toHaveAttribute(
            'placeholder',
            'ph',
        );
    });

    it('lazy-loads CheckboxField for checkbox', async () => {
        const Lazy = loadField('checkbox');
        render(
            <Suspense fallback={<span data-testid="suspense-fb">wait</span>}>
                <Lazy id="c1" label="Accept" onValueChange={() => {}} />
            </Suspense>,
        );

        await waitFor(() => {
            expect(screen.queryByTestId('suspense-fb')).not.toBeInTheDocument();
        });

        expect(screen.getByRole('checkbox')).toBeInTheDocument();
        expect(screen.getByText('Accept')).toBeInTheDocument();
    });

    it('lazy-loads SelectField for select (distinct chunk from text)', async () => {
        const Lazy = loadField('select');
        render(
            <Suspense fallback={<span data-testid="suspense-fb">wait</span>}>
                <Lazy
                    id="s1"
                    label="Pick one"
                    placeholder="Choose"
                    options={[{ value: 'a', label: 'Option A' }]}
                    onValueChange={() => {}}
                />
            </Suspense>,
        );

        await waitFor(() => {
            expect(screen.queryByTestId('suspense-fb')).not.toBeInTheDocument();
        });

        expect(screen.getByText('Pick one')).toBeInTheDocument();
    });
});
