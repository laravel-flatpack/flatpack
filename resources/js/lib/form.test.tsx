import { cleanup, render, screen } from '@testing-library/react';
import { Suspense } from 'react';
import { afterEach, describe, expect, it } from 'vitest';
import { loadField } from '@/lib/form';

const testFieldModules = {
    '../components/form-fields/text.tsx': async () => ({
        TextField: (props: { placeholder?: string }) => (
            <input placeholder={props.placeholder} />
        ),
    }),
    '../components/form-fields/checkbox.tsx': async () => ({
        CheckboxField: (props: { label: string }) => (
            <label>
                <input type="checkbox" />
                {props.label}
            </label>
        ),
    }),
    '../components/form-fields/select.tsx': async () => ({
        SelectField: (props: { label: string }) => <span>{props.label}</span>,
    }),
};

describe('loadField', () => {
    afterEach(() => {
        cleanup();
    });

    it('lazy-loads TextField for type text', async () => {
        const LazyText = loadField('text', testFieldModules);
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

        expect(await screen.findByRole('textbox')).toHaveAttribute(
            'placeholder',
            'ph',
        );
    });

    it('lazy-loads CheckboxField for checkbox', async () => {
        const Lazy = loadField('checkbox', testFieldModules);
        render(
            <Suspense fallback={<span data-testid="suspense-fb">wait</span>}>
                <Lazy id="c1" label="Accept" onValueChange={() => {}} />
            </Suspense>,
        );

        expect(await screen.findByRole('checkbox')).toBeInTheDocument();
        expect(screen.getByText('Accept')).toBeInTheDocument();
    });

    it('lazy-loads SelectField for select (distinct chunk from text)', async () => {
        const Lazy = loadField('select', testFieldModules);
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

        expect(await screen.findByText('Pick one')).toBeInTheDocument();
    });
});
