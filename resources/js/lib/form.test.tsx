import { cleanup, render, screen } from '@testing-library/react';
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

        expect(
            await screen.findByRole('textbox', undefined, { timeout: 15000 }),
        ).toHaveAttribute('placeholder', 'ph');
    }, 20000);

    it('lazy-loads CheckboxField for checkbox', async () => {
        const Lazy = loadField('checkbox');
        render(
            <Suspense fallback={<span data-testid="suspense-fb">wait</span>}>
                <Lazy id="c1" label="Accept" onValueChange={() => {}} />
            </Suspense>,
        );

        expect(
            await screen.findByRole('checkbox', undefined, { timeout: 50000 }),
        ).toBeInTheDocument();
        expect(screen.getByText('Accept')).toBeInTheDocument();
    }, 60000);

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

        expect(
            await screen.findByText('Pick one', undefined, { timeout: 15000 }),
        ).toBeInTheDocument();
    }, 20000);
});
