import { cleanup, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/layouts/flatpack-layout', () => ({
    default: ({ children }: { children: ReactNode }) => <>{children}</>,
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
}));

import FlatpackListPage from '@/pages/list';

describe('FlatpackListPage', () => {
    afterEach(() => {
        cleanup();
    });

    it('uses entity for heading and list title when name is omitted', () => {
        render(<FlatpackListPage entity="posts" />);

        expect(
            screen.getByRole('heading', { name: 'posts' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('List view placeholder for the posts entity.'),
        ).toBeInTheDocument();
        expect(document.querySelector('title')?.textContent).toBe('posts list');
    });

    it('prefers name over entity for heading and page title', () => {
        render(<FlatpackListPage entity="posts" name="Blog posts" />);

        expect(
            screen.getByRole('heading', { name: 'Blog posts' }),
        ).toBeInTheDocument();
        expect(document.querySelector('title')?.textContent).toBe(
            'Blog posts list',
        );
    });

    it('omits Head and h1 when there is no display name', () => {
        render(<FlatpackListPage entity="" />);

        expect(screen.queryAllByRole('heading')).toHaveLength(0);
        expect(document.querySelector('title')).toBeNull();
        expect(screen.getByText('List view placeholder.')).toBeInTheDocument();
    });
});
