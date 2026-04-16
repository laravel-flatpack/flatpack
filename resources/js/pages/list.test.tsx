import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';

const { routerGet } = vi.hoisted(() => ({
    routerGet: vi.fn(),
}));

vi.mock('@/layouts/flatpack-layout', () => ({
    default: ({ children }: { children: ReactNode }) => <>{children}</>,
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Link: ({
        children,
        href,
        className,
    }: {
        children: ReactNode;
        href: string;
        className?: string;
    }) => (
        <a href={href} className={className}>
            {children}
        </a>
    ),
    router: {
        get: routerGet,
    },
}));

import FlatpackListPage from '@/pages/list';

describe('FlatpackListPage', () => {
    afterEach(() => {
        cleanup();
        routerGet.mockReset();
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

    it('shows row and header selection checkboxes when bulk actions are present', () => {
        render(
            <FlatpackListPage
                entity="posts"
                bulk_actions={[
                    { id: 'delete', label: 'Delete', action: 'delete' },
                ]}
                schema={{
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

    it('navigates to edit route when row_click_edit is true', () => {
        render(
            <FlatpackListPage
                entity="posts"
                flatpack_prefix="flatpack"
                schema={{
                    row_click_edit: true,
                    columns: {
                        id: { label: 'ID' },
                        title: { label: 'Title' },
                    },
                }}
                records={[{ id: 1234567, title: 'Hello' }]}
            />,
        );

        fireEvent.click(screen.getByRole('cell', { name: 'Hello' }));

        expect(routerGet).toHaveBeenCalledWith('/flatpack/posts/1234567/edit');
    });

    it('navigates to edit route using configured key when row_click_edit is string', () => {
        render(
            <FlatpackListPage
                entity="posts"
                flatpack_prefix="flatpack"
                schema={{
                    row_click_edit: 'uuid',
                    columns: {
                        id: { label: 'ID' },
                        title: { label: 'Title' },
                    },
                }}
                records={[{ id: 1, uuid: 'abc-123', title: 'Hello' }]}
            />,
        );

        fireEvent.click(screen.getByRole('cell', { name: 'Hello' }));

        expect(routerGet).toHaveBeenCalledWith('/flatpack/posts/abc-123/edit');
    });

    it('defaults row click edit to id when row_click_edit is omitted', () => {
        render(
            <FlatpackListPage
                entity="posts"
                flatpack_prefix="flatpack"
                schema={{
                    columns: {
                        id: { label: 'ID' },
                        title: { label: 'Title' },
                    },
                }}
                records={[{ id: 42, title: 'Hello' }]}
            />,
        );

        fireEvent.click(screen.getByRole('cell', { name: 'Hello' }));

        expect(routerGet).toHaveBeenCalledWith('/flatpack/posts/42/edit');
    });

    it('defaults row click edit key to model_key when row_click_edit is omitted', () => {
        render(
            <FlatpackListPage
                entity="posts"
                flatpack_prefix="flatpack"
                model_key="uuid"
                schema={{
                    columns: {
                        title: { label: 'Title' },
                    },
                }}
                records={[{ uuid: 'abc-123', title: 'Hello' }]}
            />,
        );

        fireEvent.click(screen.getByRole('cell', { name: 'Hello' }));

        expect(routerGet).toHaveBeenCalledWith('/flatpack/posts/abc-123/edit');
    });

    it('does not navigate on row click when row_click_edit is false', () => {
        render(
            <FlatpackListPage
                entity="posts"
                flatpack_prefix="flatpack"
                schema={{
                    row_click_edit: false,
                    columns: {
                        id: { label: 'ID' },
                        title: { label: 'Title' },
                    },
                }}
                records={[{ id: 42, title: 'Hello' }]}
            />,
        );

        fireEvent.click(screen.getByRole('cell', { name: 'Hello' }));

        expect(routerGet).not.toHaveBeenCalled();
    });
});
