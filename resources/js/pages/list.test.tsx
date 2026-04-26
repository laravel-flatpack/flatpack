import {
    cleanup,
    fireEvent,
    render as rtlRender,
    screen,
    waitFor,
} from '@testing-library/react';
import type { ReactElement, ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';

const { routerGet, routerPost, routeMock } = vi.hoisted(() => ({
    routerGet: vi.fn(),
    routerPost: vi.fn(),
    routeMock: vi.fn(
        (
            name: string,
            params?: {
                entity?: string;
                record?: string;
            },
        ) => {
            const entity = params?.entity ?? 'posts';
            const record = params?.record;

            switch (name) {
                case 'flatpack.entities.edit':
                    return `/flatpack/${entity}/${record}/edit`;
                case 'flatpack.entities.index':
                    return `/flatpack/${entity}`;
                case 'flatpack.entities.bulk-action':
                    return `/flatpack/${entity}/bulk`;
                case 'flatpack.entities.row-action':
                    return `/flatpack/${entity}/${record}/action`;
                case 'flatpack.entities.action':
                    return `/flatpack/${entity}/action`;
                case 'flatpack.entities.update':
                    return `/flatpack/${entity}/${record}`;
                default:
                    return '/flatpack';
            }
        },
    ),
}));

vi.mock('@/layouts/flatpack-layout', () => ({
    default: ({ children }: { children: ReactNode }) => <>{children}</>,
}));

vi.mock('@/lib/route', () => ({
    route: routeMock,
}));

vi.mock('@/hooks/use-is-mac-platform', () => ({
    useIsMacPlatform: () => false,
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
        post: routerPost,
    },
}));

import { FlatpackShortcutsProvider } from '@/contexts/flatpack-shortcuts-registry';
import FlatpackListPage from '@/pages/list';

function render(page: ReactElement) {
    return rtlRender(page, {
        wrapper: ({ children }) => (
            <FlatpackShortcutsProvider>{children}</FlatpackShortcutsProvider>
        ),
    });
}

describe('FlatpackListPage', () => {
    afterEach(() => {
        cleanup();
        routerGet.mockReset();
        routerPost.mockReset();
        routeMock.mockClear();
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

    it('renders header href actions as links', () => {
        render(
            <FlatpackListPage
                entity="posts"
                name="Posts"
                list_actions={[
                    {
                        id: 'create',
                        label: 'Create',
                        href: '/posts/create',
                    },
                ]}
            />,
        );

        expect(screen.getByRole('link', { name: 'Create' })).toHaveAttribute(
            'href',
            '/posts/create',
        );
    });

    it('posts header actions to the list action endpoint', () => {
        render(
            <FlatpackListPage
                entity="posts"
                name="Posts"
                list_actions={[
                    {
                        id: 'create',
                        label: 'Create',
                        action: 'create',
                    },
                ]}
            />,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Create' }));

        expect(routerPost).toHaveBeenCalledWith(
            '/flatpack/posts/action',
            { action: 'create' },
            expect.objectContaining({
                preserveState: true,
                preserveScroll: true,
            }),
        );
    });

    it('triggers list action shortcut for non-confirm actions', async () => {
        render(
            <FlatpackListPage
                entity="posts"
                name="Posts"
                list_actions={[
                    {
                        id: 'create',
                        label: 'Create',
                        action: 'create',
                        shortcut: 'mod+k',
                    },
                ]}
            />,
        );

        fireEvent.keyDown(window, { key: 'k', ctrlKey: true });

        await waitFor(() => {
            expect(routerPost).toHaveBeenCalledWith(
                '/flatpack/posts/action',
                { action: 'create' },
                expect.objectContaining({
                    preserveState: true,
                    preserveScroll: true,
                }),
            );
        });
    });

    it('opens confirm dialog for shortcut on confirm list action', async () => {
        render(
            <FlatpackListPage
                entity="posts"
                name="Posts"
                list_actions={[
                    {
                        id: 'delete',
                        label: 'Delete',
                        action: 'delete',
                        confirm: true,
                        shortcut: 'mod+d',
                    },
                ]}
            />,
        );

        fireEvent.keyDown(window, { key: 'd', ctrlKey: true });

        await waitFor(() => {
            expect(screen.getByRole('alertdialog')).toBeInTheDocument();
        });
        expect(routerPost).not.toHaveBeenCalled();

        fireEvent.click(screen.getByRole('button', { name: 'Continue' }));

        await waitFor(() => {
            expect(routerPost).toHaveBeenCalledWith(
                '/flatpack/posts/action',
                { action: 'delete' },
                expect.objectContaining({
                    preserveState: true,
                    preserveScroll: true,
                }),
            );
        });
    });
});
