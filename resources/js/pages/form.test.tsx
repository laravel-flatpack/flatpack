import { cleanup, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/layouts/flatpack-layout', () => ({
    default: ({ children }: { children: ReactNode }) => <>{children}</>,
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
}));

import FlatpackFormPage from '@/pages/form';

describe('FlatpackFormPage', () => {
    afterEach(() => {
        cleanup();
    });

    it('renders create placeholder copy', () => {
        render(
            <FlatpackFormPage entity="Article" mode="create" record={null} />,
        );

        expect(
            screen.getByRole('heading', { name: 'Article' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Create form placeholder for Article.'),
        ).toBeInTheDocument();
        expect(document.querySelector('title')?.textContent).toBe(
            'Article form',
        );
    });

    it('renders edit placeholder with record id', () => {
        render(
            <FlatpackFormPage entity="Article" mode="edit" record="rec-42" />,
        );

        expect(
            screen.getByText('Edit form placeholder for Article (rec-42).'),
        ).toBeInTheDocument();
    });

    it('uses unknown when record is null in edit mode', () => {
        render(<FlatpackFormPage entity="Tag" mode="edit" record={null} />);

        expect(
            screen.getByText('Edit form placeholder for Tag (unknown).'),
        ).toBeInTheDocument();
    });
});
