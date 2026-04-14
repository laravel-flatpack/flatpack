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
            screen.getByText(
                /Define columns in list\.yaml to render this table/i,
            ),
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
        expect(screen.getByText('Nothing to list yet.')).toBeInTheDocument();
    });

    it('renders a data table when list schema defines columns', () => {
        render(
            <FlatpackListPage
                entity="posts"
                name="Posts"
                schema={{
                    columns: {
                        id: { label: 'ID', sortable: true },
                        title: {
                            label: 'Title',
                            searchable: true,
                        },
                    },
                }}
                records={[]}
            />,
        );

        expect(screen.getByRole('table')).toBeInTheDocument();
        expect(screen.getByText('No results.')).toBeInTheDocument();
    });

    it('passes server data through to the table', () => {
        render(
            <FlatpackListPage
                entity="posts"
                schema={{
                    columns: {
                        id: { label: 'ID' },
                        title: { label: 'Title' },
                    },
                }}
                records={[{ id: 1, title: 'Hello' }]}
            />,
        );

        expect(screen.getByRole('cell', { name: 'Hello' })).toBeInTheDocument();
    });

    it('shows row and header selection checkboxes when schema has checkboxes true', () => {
        render(
            <FlatpackListPage
                entity="posts"
                schema={{
                    checkboxes: true,
                    columns: {
                        id: { label: 'ID' },
                        title: { label: 'Title' },
                    },
                }}
                records={[{ id: 1, title: 'Hello' }]}
            />,
        );

        expect(
            screen.getByRole('checkbox', { name: 'Select all' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('checkbox', { name: 'Select row' }),
        ).toBeInTheDocument();
    });

    it('shows drag-and-drop reorder column when schema has reorderable true', () => {
        render(
            <FlatpackListPage
                entity="posts"
                schema={{
                    reorderable: true,
                    columns: {
                        id: { label: 'ID' },
                        title: { label: 'Title' },
                    },
                }}
                records={[{ id: 1, title: 'Hello' }]}
            />,
        );

        expect(
            screen.getByRole('columnheader', { name: 'Reorder' }),
        ).toBeInTheDocument();
    });

    it('shows drag-and-drop reorder column when schema has reorderable as a string column id', () => {
        render(
            <FlatpackListPage
                entity="posts"
                schema={{
                    reorderable: 'my_column',
                    columns: {
                        id: { label: 'ID' },
                        title: { label: 'Title' },
                    },
                }}
                records={[{ id: 1, title: 'Hello', my_column: 1 }]}
            />,
        );

        expect(
            screen.getByRole('columnheader', { name: 'Reorder' }),
        ).toBeInTheDocument();
    });

    it('does not show reorder column when schema has reorderable false', () => {
        render(
            <FlatpackListPage
                entity="posts"
                schema={{
                    reorderable: false,
                    columns: {
                        id: { label: 'ID' },
                        title: { label: 'Title' },
                    },
                }}
                records={[{ id: 1, title: 'Hello' }]}
            />,
        );

        expect(
            screen.queryByRole('columnheader', { name: 'Reorder' }),
        ).not.toBeInTheDocument();
    });
});
